<div id="pia-vimeo-feed">
  <h2 style="margin:0 0 12px 0;">Patriots In Action TV</h2>
  <div class="pia-status">Loading videos...</div>
  <div class="pia-list"></div>
  <button class="pia-more" type="button" style="display:none;">Load more</button>
</div>

<style>
  #pia-vimeo-feed .pia-list { display:flex; flex-direction:column; gap:22px; }
  #pia-vimeo-feed .pia-card { border:1px solid rgba(0,0,0,.12); border-radius:10px; padding:14px; background:#fff; }
  #pia-vimeo-feed .pia-title { margin:0 0 10px 0; font-size:20px; line-height:1.25; }
  #pia-vimeo-feed .pia-embed { position:relative; padding-top:56.25%; border-radius:10px; overflow:hidden; background:rgba(0,0,0,.06); }
  #pia-vimeo-feed .pia-embed iframe { position:absolute; inset:0; width:100%; height:100%; }
  #pia-vimeo-feed .pia-desc { margin-top:10px; white-space:pre-wrap; }
  #pia-vimeo-feed .pia-more { margin-top:16px; padding:10px 14px; border-radius:8px; border:1px solid rgba(0,0,0,.18); background:#fff; cursor:pointer; }
  #pia-vimeo-feed .is-hidden { display:none; }
</style>

<script>
(function(){
  // Same-origin REST endpoint (no CORS)
  var INOREADER_FEED_URL = "/wp-json/pia/v1/inoreader?feed=pia_latest_from_us";
  var BATCH_SIZE = 12;

  function onDomReady(fn) {
    if (document.readyState === "complete" || document.readyState === "interactive") {
      setTimeout(fn, 1);
    } else {
      document.addEventListener("DOMContentLoaded", fn);
    }
  }

  onDomReady(function() {
    var root = document.getElementById("pia-vimeo-feed");
    if (!root) return;

    var statusEl = root.querySelector(".pia-status");
    var listEl = root.querySelector(".pia-list");
    var moreBtn = root.querySelector(".pia-more");

    function isVimeoUrl(u){
      return /^https?:\/\/(www\.)?vimeo\.com\/\d+/.test(u || "");
    }

    function normalizeVimeoUrl(u){
      var m = String(u || "").match(/vimeo\.com\/(\d+)/);
      return m ? ("https://vimeo.com/" + m[1]) : null;
    }

    function fetchJson(url){
      return fetch(url, { credentials: "same-origin" }).then(function(r){
        if(!r.ok) throw new Error("Feed fetch failed: " + r.status);
        return r.json();
      });
    }

    function parseFeed(feed){
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

    function makeCard(item){
      var card = document.createElement("article");
      card.className = "pia-card is-hidden";
      card.setAttribute("data-url", item.url);
      card.setAttribute("data-loaded", "0");

      var h3 = document.createElement("h3");
      h3.className = "pia-title";
      h3.textContent = item.title || "Loading...";

      var embed = document.createElement("div");
      embed.className = "pia-embed";
      embed.setAttribute("aria-busy", "true");

      var desc = document.createElement("div");
      desc.className = "pia-desc";

      card.appendChild(h3);
      card.appendChild(embed);
      card.appendChild(desc);
      return card;
    }

    function hydrateCard(card){
      if(card.getAttribute("data-loaded") === "1") return Promise.resolve();
      card.setAttribute("data-loaded", "1");

      var videoUrl = card.getAttribute("data-url");
      return fetchOembed(videoUrl).then(function(data){
        var titleEl = card.querySelector(".pia-title");
        if(!titleEl.textContent.trim() || titleEl.textContent.trim() === "Loading..."){
          titleEl.textContent = data.title || videoUrl;
        }
        card.querySelector(".pia-desc").textContent = (data.description || "").trim();

        var embed = card.querySelector(".pia-embed");
        embed.innerHTML = data.html || "";
        embed.setAttribute("aria-busy", "false");

        var iframe = embed.querySelector("iframe");
        if(iframe) iframe.setAttribute("loading","lazy");
      });
    }

    statusEl.textContent = "Loading videos...";

    fetchJson(INOREADER_FEED_URL)
      .then(function(feed){
        var items = parseFeed(feed);

        // De-dupe by URL
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
          var c = makeCard(videos[j]);
          cards.push(c);
          listEl.appendChild(c);
        }

        var revealed = 0;
        var observer = ("IntersectionObserver" in window) ? new window.IntersectionObserver(function(entries){
          for (var k=0; k<entries.length; k++){
            var e = entries[k];
            if(e.isIntersecting){
              observer.unobserve(e.target);
              hydrateCard(e.target)["catch"](console.error);
            }
          }
        }, { rootMargin: "250px 0px" }) : null;

        function revealNext(){
          var next = Math.min(revealed + BATCH_SIZE, cards.length);
          for (var x=revealed; x<next; x++){
            cards[x].classList.remove("is-hidden");
            if(observer) observer.observe(cards[x]);
            else hydrateCard(cards[x])["catch"](console.error);
          }
          revealed = next;
          moreBtn.style.display = (revealed < cards.length) ? "" : "none";
        }

        moreBtn.addEventListener("click", revealNext);
        revealNext();
      })
      .catch(function(err){
        console.error(err);
        statusEl.textContent = "Could not load the feed. Check console for details.";
      });
  });
})();
</script>
