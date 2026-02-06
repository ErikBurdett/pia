/**
 * Cloudflare Worker: Vimeo video list + oEmbed proxy (no PHP needed)
 *
 * Endpoints:
 *   GET /list?user=patriotsinactiontv&sort=date
 *   GET /oembed?url=https://vimeo.com/123456789
 */
export default {
    async fetch(request, env, ctx) {
      const url = new URL(request.url);
  
      // CORS preflight
      if (request.method === "OPTIONS") {
        return new Response(null, { headers: CORS_HEADERS });
      }
  
      const path = url.pathname.replace(/\/+$/, "");
  
      if (path === "/list") return handleList(request, url, ctx);
      if (path === "/oembed") return handleOembed(request, url, ctx);
  
      return new Response("Not found", { status: 404 });
    },
  };
  
  const CORS_HEADERS = {
    "Access-Control-Allow-Origin": "*",
    "Access-Control-Allow-Methods": "GET, OPTIONS",
    "Access-Control-Allow-Headers": "Content-Type",
  };
  
  async function handleList(request, url, ctx) {
    const username = (url.searchParams.get("user") || "patriotsinactiontv").trim();
    const sort = (url.searchParams.get("sort") || "date").trim(); // date|alphabetical|duration (Vimeo UI sorts)
    const cacheTtlSeconds = 60 * 10; // 10 minutes
  
    // Cache the list response
    const cacheKey = new Request(url.toString(), { method: "GET" });
    const cached = await caches.default.match(cacheKey);
    if (cached) return withCors(cached);
  
    const baseUrl = `https://vimeo.com/${encodeURIComponent(username)}/videos`;
  
    const seen = new Set();
    const ordered = [];
  
    const firstHtml = await fetchHtml(baseUrl);
    const totalPages = detectTotalPages(firstHtml);
  
    extractVideoUrls(firstHtml, ordered, seen);
  
    // Build URLs for remaining pages
    const pageUrls = [];
    for (let p = 2; p <= totalPages; p++) {
      pageUrls.push(
        `https://vimeo.com/${encodeURIComponent(username)}/videos/page%3A${p}/sort%3A${encodeURIComponent(sort)}`
      );
    }
  
    // Fetch pages with a small concurrency limit
    const concurrency = 3;
    for (let i = 0; i < pageUrls.length; i += concurrency) {
      const batch = pageUrls.slice(i, i + concurrency);
      const htmlBatch = await Promise.all(batch.map(fetchHtml));
      for (const html of htmlBatch) extractVideoUrls(html, ordered, seen);
    }
  
    const payload = {
      user: username,
      sort,
      total_pages: totalPages,
      count: ordered.length,
      videos: ordered, // ordered newest->oldest when sort=date
      fetched_at: new Date().toISOString(),
    };
  
    const response = new Response(JSON.stringify(payload), {
      headers: {
        ...CORS_HEADERS,
        "Content-Type": "application/json; charset=utf-8",
        "Cache-Control": `public, max-age=${cacheTtlSeconds}`,
      },
    });
  
    ctx.waitUntil(caches.default.put(cacheKey, response.clone()));
    return response;
  }
  
  async function handleOembed(request, url, ctx) {
    const videoUrl = url.searchParams.get("url");
    if (!videoUrl) {
      return new Response(JSON.stringify({ error: "Missing url param" }), {
        status: 400,
        headers: { ...CORS_HEADERS, "Content-Type": "application/json" },
      });
    }
  
    // Basic allowlist: only numeric vimeo video pages
    if (!/^https?:\/\/(www\.)?vimeo\.com\/\d+/.test(videoUrl)) {
      return new Response(JSON.stringify({ error: "Only vimeo.com/{id} urls allowed" }), {
        status: 400,
        headers: { ...CORS_HEADERS, "Content-Type": "application/json" },
      });
    }
  
    // Vimeo oEmbed (recommended by Vimeo for embeddable metadata) :contentReference[oaicite:8]{index=8}
    const oembedUrl = `https://vimeo.com/api/oembed.json?url=${encodeURIComponent(videoUrl)}&responsive=1`;
  
    // Cache oEmbed per video
    const cacheKey = new Request(oembedUrl, { method: "GET" });
    const cached = await caches.default.match(cacheKey);
    if (cached) return withCors(cached);
  
    const resp = await fetch(oembedUrl, {
      headers: {
        "User-Agent": "Mozilla/5.0",
        "Accept": "application/json",
      },
    });
  
    const body = await resp.text();
  
    const out = new Response(body, {
      status: resp.status,
      headers: {
        ...CORS_HEADERS,
        "Content-Type": "application/json; charset=utf-8",
        "Cache-Control": "public, max-age=86400", // 1 day
      },
    });
  
    if (resp.ok) ctx.waitUntil(caches.default.put(cacheKey, out.clone()));
    return out;
  }
  
  function withCors(response) {
    const headers = new Headers(response.headers);
    for (const [k, v] of Object.entries(CORS_HEADERS)) headers.set(k, v);
    return new Response(response.body, {
      status: response.status,
      statusText: response.statusText,
      headers,
    });
  }
  
  async function fetchHtml(url) {
    const resp = await fetch(url, {
      headers: {
        "User-Agent": "Mozilla/5.0",
        "Accept": "text/html",
      },
    });
    if (!resp.ok) throw new Error(`Failed to fetch ${url}: ${resp.status}`);
    return await resp.text();
  }
  
  function detectTotalPages(html) {
    // Finds links like ".../videos/page%3A10/sort%3Adate"
    let max = 1;
    const re = /videos\/page%3A(\d+)\/sort%3A/gi;
    let m;
    while ((m = re.exec(html)) !== null) {
      const n = parseInt(m[1], 10);
      if (!Number.isNaN(n)) max = Math.max(max, n);
    }
    return max;
  }
  
  function extractVideoUrls(html, ordered, seen) {
    // Capture only numeric Vimeo video URLs
    const re = /https:\/\/vimeo\.com\/(\d+)/g;
    let m;
    while ((m = re.exec(html)) !== null) {
      const url = `https://vimeo.com/${m[1]}`;
      if (!seen.has(url)) {
        seen.add(url);
        ordered.push(url);
      }
    }
  }
  