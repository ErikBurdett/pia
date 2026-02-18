<div id="pia-vimeo-feed">
  <h2 class="pia-feed-title">Patriots In Action TV</h2>
  <div class="pia-status">Loading videos...</div>
  <div class="pia-list"></div>
</div>

<style>
  /* Layout + safe spacing from sticky headers on mobile */
  #pia-vimeo-feed{
    max-width: 980px;
    margin: 0 auto;
    width: 100%;
    padding: 12px 16px 0;         /* side padding keeps it centered on narrow screens */
    box-sizing: border-box;
    scroll-margin-top: 140px;      /* helps when jumping to #video-* anchors */
  }

  /* Title: center + add top spacing so it won't kiss the header on mobile */
  #pia-vimeo-feed .pia-feed-title{
    margin: 22px 0 12px;           /* pushes title down */
    padding-top: 6px;              /* extra breathing room */
    text-align: center;
    line-height: 1.2;
  }

  #pia-vimeo-feed .pia-status{
    text-align:center;
    opacity:.85;
    margin: 8px 0 14px;
  }

  #pia-vimeo-feed .pia-list{
    display:flex;
    flex-direction:column;
    gap:22px;
    align-items: center;           /* ensures cards stay centered */
  }

  /* Cards always centered + full-width on mobile */
  #pia-vimeo-feed .pia-card{
    width: 100%;
    max-width: 980px;
    border: 1px solid rgba(0,0,0,.10);
    border-radius: 12px;
    padding: 18px;
    background: transparent !important;
    box-shadow: none !important;
    box-sizing: border-box;
    scroll-margin-top: 140px;      /* anchor jumps land below sticky header */
  }

  #pia-vimeo-feed .pia-title{
    margin: 0 0 12px 0;
    font-size: 22px;
    line-height: 1.25;
    text-align: center;
  }
  #pia-vimeo-feed .pia-title a{ color: inherit; text-decoration: none; }
  #pia-vimeo-feed .pia-title a:hover{ text-decoration: underline; }

  /* Responsive 16:9 embed */
  #pia-vimeo-feed .pia-embed{
    position: relative;
    width: 100%;
    aspect-ratio: 16 / 9;
    border-radius: 12px;
    overflow: hidden;
    background: rgba(0,0,0,.06);
  }

  #pia-vimeo-feed .pia-embed iframe{
    position: absolute !important;
    inset: 0 !important;
    display:block;
    width: 100% !important;
    height: 100% !important;
    border: 0;
  }

  #pia-vimeo-feed .pia-desc{
    margin-top: 12px;
    white-space: pre-wrap;
    line-height: 1.55;
  }

  #pia-vimeo-feed .pia-actions{
    display:flex;
    gap:10px;
    flex-wrap:wrap;
    margin-top:14px;
    justify-content:center;
  }

  #pia-vimeo-feed .pia-btn{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    padding: 12px 16px;
    border-radius: 8px;
    border: 1px solid rgba(0,0,0,.14);
    background: #0b3a6f;
    color: #fff;
    cursor: pointer;
    font-weight: 700;
    text-decoration: none;
    user-select: none;
    transition: background .15s ease, transform .05s ease, box-shadow .15s ease;
    box-shadow: 0 6px 16px rgba(11,58,111,.18);
    min-width: 240px;
    text-align:center;
  }
  #pia-vimeo-feed .pia-btn:hover{ background:#092f59; box-shadow: 0 8px 18px rgba(11,58,111,.22); }
  #pia-vimeo-feed .pia-btn:active{ transform: translateY(1px); }

  @media (max-width: 768px){
    #pia-vimeo-feed{
      padding: 14px 12px 0;
      scroll-margin-top: 160px;
    }

    #pia-vimeo-feed .pia-feed-title{
      margin-top: 34px;
    }

    #pia-vimeo-feed .pia-card{
      padding: 14px;
      scroll-margin-top: 160px;
    }

    #pia-vimeo-feed .pia-title{ font-size: 20px; }
    #pia-vimeo-feed .pia-btn{ min-width: 100%; }
  }
</style>

<script>
(function(){
  var SHOWCASE_ID = "12047150";
  var VIMEO_SHOWCASE_URL = "/wp-json/pia/v1/vimeo-showcase?showcase_id=" + encodeURIComponent(SHOWCASE_ID) + "&page=1&per_page=50";
  var NETWORK_URL = "https://community.patriotsinaction.com/share/Pw_KGVpesTKkKrNI?utm_source=manual";

  // Keep in sync with the CSS scroll-margin-top for best results
  var HEADER_OFFSET_PX = 140;

  var root = document.getElementById("pia-vimeo-feed");
  if (!root) return;

  var statusEl = root.querySelector(".pia-status");
  var listEl = root.querySelector(".pia-list");

  function fetchJson(url){
    return fetch(url, { credentials: "same-origin" }).then(function(r){
      if(!r.ok) throw new Error("Fetch failed: " + r.status);
      return r.json();
    });
  }

  function getVimeoIdFromUriOrLink(obj){
    // obj.uri: "/videos/1164792589" OR obj.link: "https://vimeo.com/1164792589"
    var u = (obj && obj.uri) ? String(obj.uri) : "";
    var l = (obj && obj.link) ? String(obj.link) : "";
    var m = u.match(/\/videos\/(\d+)/) || l.match(/vimeo\.com\/(\d+)/);
    return m ? m[1] : null;
  }

  function buildShareUrlByAnchor(anchorId){
    var base = window.location.origin + window.location.pathname;
    return base + "#" + anchorId;
  }

  function copyToClipboard(text){
    if (navigator.clipboard && navigator.clipboard.writeText) return navigator.clipboard.writeText(text);
    var ta = document.createElement("textarea");
    ta.value = text;
    ta.style.position = "fixed";
    ta.style.left = "-9999px";
    document.body.appendChild(ta);
    ta.select();
    try { document.execCommand("copy"); } catch (e) {}
    document.body.removeChild(ta);
    return Promise.resolve();
  }

  function buildPlayerSrc(video){
    // Prefer the API-provided player_embed_url (already includes h= hash)
    var src = (video && video.player_embed_url) ? String(video.player_embed_url) : "";
    var id = getVimeoIdFromUriOrLink(video);

    if (!src && id) src = "https://player.vimeo.com/video/" + encodeURIComponent(id);
    return src || "";
  }

  function renderSingleIframe(embed, src, titleText){
    if (!embed) return;

    while (embed.firstChild) embed.removeChild(embed.firstChild);

    if (!src) {
      embed.textContent = "Video could not be embedded.";
      return;
    }

    var iframe = document.createElement("iframe");
    iframe.src = src;
    iframe.setAttribute("frameborder", "0");
    iframe.setAttribute("allow", "autoplay; fullscreen; picture-in-picture; clipboard-write; encrypted-media; web-share");
    iframe.setAttribute("referrerpolicy", "strict-origin-when-cross-origin");
    iframe.setAttribute("loading", "lazy");
    iframe.allowFullscreen = true;
    iframe.setAttribute("allowfullscreen", "");
    if (titleText) iframe.title = titleText;

    embed.appendChild(iframe);
  }

  function makeCard(video, index){
    var id = getVimeoIdFromUriOrLink(video) || "";
    var link = (video && video.link) ? String(video.link) : (id ? ("https://vimeo.com/" + id) : "");
    var title = (video && video.name) ? String(video.name).trim() : "Untitled";
    var descText = (video && video.description) ? String(video.description).trim() : "";

    var card = document.createElement("article");
    card.className = "pia-card";
    card.setAttribute("data-loaded", "0");
    card.setAttribute("data-vimeo-id", id);
    card.setAttribute("data-player-src", buildPlayerSrc(video));
    card.setAttribute("data-url", link);

    var shortAnchorId = "video-" + index;
    card.id = shortAnchorId;

    var h3 = document.createElement("h3");
    h3.className = "pia-title";

    var titleLink = document.createElement("a");
    titleLink.href = "#" + shortAnchorId;
    titleLink.textContent = title;
    titleLink.title = "Copy/share link to this video section";
    h3.appendChild(titleLink);

    var embed = document.createElement("div");
    embed.className = "pia-embed";
    embed.setAttribute("aria-busy", "true");

    var desc = document.createElement("div");
    desc.className = "pia-desc";
    desc.textContent = descText || "";

    var actions = document.createElement("div");
    actions.className = "pia-actions";

    var joinBtn = document.createElement("a");
    joinBtn.className = "pia-btn";
    joinBtn.href = NETWORK_URL;
    joinBtn.target = "_blank";
    joinBtn.rel = "noopener";
    joinBtn.textContent = "Join Your County Patriot Network";
    actions.appendChild(joinBtn);

    var shareBtn = document.createElement("button");
    shareBtn.type = "button";
    shareBtn.className = "pia-btn";
    shareBtn.textContent = "Share this video";
    shareBtn.addEventListener("click", function(){
      var shareUrl = buildShareUrlByAnchor(shortAnchorId);
      if (navigator.share) {
        navigator.share({ title: title || "Patriots In Action TV", url: shareUrl }).catch(function(){});
        return;
      }
      copyToClipboard(shareUrl).then(function(){
        var old = shareBtn.textContent;
        shareBtn.textContent = "Link copied!";
        setTimeout(function(){ shareBtn.textContent = old; }, 1200);
      });
    });
    actions.appendChild(shareBtn);

    card.appendChild(h3);
    card.appendChild(embed);
    card.appendChild(desc);
    card.appendChild(actions);

    return card;
  }

  function hydrateCard(card){
    if (!card || card.getAttribute("data-loaded") === "1") return Promise.resolve();
    card.setAttribute("data-loaded", "1");

    var embed = card.querySelector(".pia-embed");
    if (!embed) return Promise.resolve();

    var src = card.getAttribute("data-player-src") || "";
    var titleText = (card.querySelector(".pia-title a") || {}).textContent || "";
    renderSingleIframe(embed, src, titleText.trim());
    embed.setAttribute("aria-busy", "false");
    return Promise.resolve();
  }

  function getTargetFromHash(){
    var hash = window.location.hash || "";
    if (!hash) return null;

    var raw = hash.slice(1);
    if (raw.indexOf("video-") !== 0) return null;

    var el = document.getElementById(raw);
    if (el) return el;

    var maybeId = raw.replace("video-", "");
    if (/^\d+$/.test(maybeId)) {
      var match = root.querySelector('[data-vimeo-id="' + maybeId + '"]');
      if (match) return match;
    }
    return null;
  }

  function scrollToTarget(el){
    if (!el) return;

    var top1 = el.getBoundingClientRect().top + window.pageYOffset - HEADER_OFFSET_PX;
    window.scrollTo({ top: Math.max(0, top1), behavior: "auto" });

    hydrateCard(el)["catch"](console.error);

    var attempts = 0;
    function retry(){
      attempts++;
      var top = el.getBoundingClientRect().top + window.pageYOffset - HEADER_OFFSET_PX;
      window.scrollTo({ top: Math.max(0, top), behavior: attempts === 1 ? "auto" : "smooth" });
      if (attempts >= 4) return;
      setTimeout(function(){ requestAnimationFrame(retry); }, 250);
    }
    setTimeout(function(){ requestAnimationFrame(retry); }, 150);
  }

  function onHash(){
    var el = getTargetFromHash();
    if (el) scrollToTarget(el);
  }

  statusEl.textContent = "Loading videos...";

  fetchJson(VIMEO_SHOWCASE_URL)
    .then(function(json){
      var videos = (json && Array.isArray(json.data)) ? json.data : [];
      if (!videos.length) {
        statusEl.textContent = "No videos found in this showcase.";
        return;
      }

      // Optional: sort newest first by created_time (your API may already return in order)
      videos.sort(function(a,b){
        var at = Date.parse(a && a.created_time ? a.created_time : "") || 0;
        var bt = Date.parse(b && b.created_time ? b.created_time : "") || 0;
        return bt - at;
      });

      statusEl.textContent = videos.length + " videos";

      var cards = [];
      for (var i=0; i<videos.length; i++){
        var c = makeCard(videos[i], i + 1);
        cards.push(c);
        listEl.appendChild(c);
      }

      var observer = ("IntersectionObserver" in window) ? new IntersectionObserver(function(entries){
        for (var k=0; k<entries.length; k++){
          var e = entries[k];
          if (e.isIntersecting){
            observer.unobserve(e.target);
            hydrateCard(e.target)["catch"](console.error);
          }
        }
      }, { rootMargin: "700px 0px" }) : null;

      if (observer) {
        for (var x=0; x<cards.length; x++) observer.observe(cards[x]);
      } else {
        for (var y=0; y<Math.min(6, cards.length); y++) hydrateCard(cards[y])["catch"](console.error);
      }

      onHash();
      window.addEventListener("hashchange", onHash);
    })
    .catch(function(err){
      console.error(err);
      statusEl.textContent = "Could not load the showcase feed. Check console for details.";
    });
})();
</script>
