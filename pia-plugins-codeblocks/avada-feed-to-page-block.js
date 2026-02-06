<div id="pia-vimeo-feed">
  <h2 style="margin:0 0 12px 0; text-align:center;">Patriots In Action TV</h2>
  <div class="pia-status">Loading videos...</div>
  <div class="pia-list"></div>
</div>

<style>
  #pia-vimeo-feed{ max-width: 980px; margin: 0 auto; }
  #pia-vimeo-feed .pia-status{ text-align:center; opacity:.85; margin: 8px 0 14px; }
  #pia-vimeo-feed .pia-list { display:flex; flex-direction:column; gap:22px; }

  #pia-vimeo-feed .pia-card{
    border: 1px solid rgba(0,0,0,.10);
    border-radius: 12px;
    padding: 18px;
    background: #fff;
    box-shadow: 0 8px 24px rgba(0,0,0,.06);
  }

  #pia-vimeo-feed .pia-title{
    margin: 0 0 12px 0;
    font-size: 22px;
    line-height: 1.25;
    text-align: center;
  }
  #pia-vimeo-feed .pia-title a{ color: inherit; text-decoration: none; }
  #pia-vimeo-feed .pia-title a:hover{ text-decoration: underline; }

  #pia-vimeo-feed .pia-embed{
    position: relative;
    padding-top: 0;
    border-radius: 12px;
    overflow: hidden;
    background: rgba(0,0,0,.06);
  }
  #pia-vimeo-feed .pia-embed > div{ position: relative !important; margin: 0 !important; }
  #pia-vimeo-feed .pia-embed iframe{ display:block; width: 100% !important; height: 100% !important; }
  #pia-vimeo-feed .pia-embed > div > iframe{ position:absolute !important; inset:0 !important; }

  #pia-vimeo-feed .pia-desc{ margin-top: 12px; white-space: pre-wrap; line-height: 1.55; }

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
    #pia-vimeo-feed .pia-card{ padding: 14px; }
    #pia-vimeo-feed .pia-title{ font-size: 20px; }
    #pia-vimeo-feed .pia-btn{ min-width: 100%; }
  }
</style>

<script>
(function(){
  var INOREADER_FEED_URL = "/wp-json/pia/v1/inoreader?feed=pia_latest_from_us";
  var NETWORK_URL = "https://community.patriotsinaction.com/share/Pw_KGVpesTKkKrNI?utm_source=manual";

  // Adjust if you have a sticky header; this keeps the target from being hidden
  var HEADER_OFFSET_PX = 120;

  var root = document.getElementById("pia-vimeo-feed");
  if (!root) return;

  var statusEl = root.querySelector(".pia-status");
  var listEl = root.querySelector(".pia-list");

  function isVimeoUrl(u){
    return /^https?:\/\/(www\.)?vimeo\.com\/\d+/.test(u || "");
  }
  function getVimeoId(u){
    var m = String(u || "").match(/vimeo\.com\/(\d+)/);
    return m ? m[1] : null;
  }
  function normalizeVimeoUrl(u){
    var id = getVimeoId(u);
    return id ? ("https://vimeo.com/" + id) : null;
  }

  function fetchJson(url){
    return fetch(url, { credentials: "same-origin" }).then(function(r){
      if(!r.ok) throw new Error("Feed fetch failed: " + r.status);
      return r.json();
    });
  }

  function parseInoreaderJsonFeed(feed){
    var items = (feed && Array.isArray(feed.items)) ? feed.items : [];
    var out = [];
    for (var i=0; i<items.length; i++){
      var it = items[i] || {};
      var title = String(it.title || "").trim();
      var url = normalizeVimeoUrl(it.url || it.external_url || it.id || "");
      if (isVimeoUrl(url)) out.push({ title: title, url: url });
    }
    return out;
  }

  function fetchOembed(vimeoUrl){
    var endpoint = "https://vimeo.com/api/oembed.json?url=" + encodeURIComponent(vimeoUrl) + "&responsive=1";
    return fetch(endpoint).then(function(r){
      if(!r.ok) throw new Error("oEmbed failed: " + r.status);
      return r.json();
    });
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

  function makeCard(item, index){
    var card = document.createElement("article");
    card.className = "pia-card";
    card.setAttribute("data-url", item.url);
    card.setAttribute("data-loaded", "0");

    var vimeoId = getVimeoId(item.url);
    card.setAttribute("data-vimeo-id", vimeoId || "");

    var shortAnchorId = "video-" + index;
    card.id = shortAnchorId;

    var h3 = document.createElement("h3");
    h3.className = "pia-title";

    var titleLink = document.createElement("a");
    titleLink.href = "#" + shortAnchorId;
    titleLink.textContent = item.title || "Loading...";
    titleLink.title = "Copy/share link to this video section";
    h3.appendChild(titleLink);

    var embed = document.createElement("div");
    embed.className = "pia-embed";
    embed.setAttribute("aria-busy", "true");

    var desc = document.createElement("div");
    desc.className = "pia-desc";
    desc.textContent = "";

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
        navigator.share({ title: item.title || "Patriots In Action TV", url: shareUrl }).catch(function(){});
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
    if(card.getAttribute("data-loaded") === "1") return Promise.resolve();
    card.setAttribute("data-loaded", "1");

    var videoUrl = card.getAttribute("data-url");
    return fetchOembed(videoUrl).then(function(data){
      var titleLink = card.querySelector(".pia-title a");
      if(!titleLink.textContent.trim() || titleLink.textContent.trim() === "Loading..."){
        titleLink.textContent = data.title || videoUrl;
      }

      card.querySelector(".pia-desc").textContent = (data.description || "").trim();

      var embed = card.querySelector(".pia-embed");
      embed.innerHTML = data.html || "";
      embed.setAttribute("aria-busy", "false");

      var iframe = embed.querySelector("iframe");
      if(iframe) iframe.setAttribute("loading","lazy");
    });
  }

  function getTargetFromHash(){
    var hash = window.location.hash || "";
    if (!hash) return null;

    var raw = hash.slice(1);
    if (raw.indexOf("video-") !== 0) return null;

    // Try direct ID first (#video-14 etc.)
    var el = document.getElementById(raw);
    if (el) return el;

    // Legacy: #video-1159489682
    var maybeId = raw.replace("video-", "");
    if (/^\d+$/.test(maybeId)) {
      var match = root.querySelector('[data-vimeo-id="' + maybeId + '"]');
      if (match) return match;
    }
    return null;
  }

  // Robust scroll that compensates for late layout shifts and sticky headers
  function scrollToTarget(el){
    if (!el) return;

    // Step 1: jump instantly close (so we start near the correct area)
    var top1 = el.getBoundingClientRect().top + window.pageYOffset - HEADER_OFFSET_PX;
    window.scrollTo({ top: Math.max(0, top1), behavior: "auto" });

    // Step 2: ensure the embed is loaded (it changes layout height)
    hydrateCard(el)["catch"](console.error);

    // Step 3: re-scroll a few times as layout settles
    var attempts = 0;
    function retry(){
      attempts++;
      var top = el.getBoundingClientRect().top + window.pageYOffset - HEADER_OFFSET_PX;

      // Smooth on later attempts for nicer UX
      window.scrollTo({ top: Math.max(0, top), behavior: attempts === 1 ? "auto" : "smooth" });

      // Stop after a few tries
      if (attempts >= 4) return;

      // Wait a bit for iframe/layout shifts, then try again
      setTimeout(function(){
        requestAnimationFrame(retry);
      }, 250);
    }
    setTimeout(function(){
      requestAnimationFrame(retry);
    }, 150);
  }

  function onHash(){
    var el = getTargetFromHash();
    if (el) scrollToTarget(el);
  }

  statusEl.textContent = "Loading videos...";

  fetchJson(INOREADER_FEED_URL)
    .then(function(feed){
      var items = parseInoreaderJsonFeed(feed);

      var seen = {};
      var videos = [];
      for (var i=0; i<items.length; i++){
        var u = items[i].url;
        if (!seen[u]) { seen[u] = true; videos.push(items[i]); }
      }

      if(!videos.length){
        statusEl.textContent = "No Vimeo video links found in the feed.";
        return;
      }

      statusEl.textContent = videos.length + " videos";

      var cards = [];
      for (var j=0; j<videos.length; j++){
        var c = makeCard(videos[j], j + 1);
        cards.push(c);
        listEl.appendChild(c);
      }

      // Lazy-load embeds when cards come near viewport
      var observer = ("IntersectionObserver" in window) ? new IntersectionObserver(function(entries){
        for (var k=0; k<entries.length; k++){
          var e = entries[k];
          if(e.isIntersecting){
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

      // Initial hash scroll (after DOM exists)
      onHash();

      // Hash changes
      window.addEventListener("hashchange", onHash);
    })
    .catch(function(err){
      console.error(err);
      statusEl.textContent = "Could not load the feed. Check console for details.";
    });
})();
</script>
