<div id="pia-watch">
  <div class="pia-watch-status">Loading...</div>
  <h1 class="pia-watch-title" style="margin:10px 0;"></h1>
  <div class="pia-watch-embed"></div>
  <div class="pia-watch-desc" style="margin-top:12px; white-space:pre-wrap;"></div>
</div>

<style>
  #pia-watch .pia-watch-embed > div { position:relative; }
  #pia-watch .pia-watch-embed iframe { display:block; width:100% !important; height:100% !important; }
</style>

<script>
(function(){
  var params = new URLSearchParams(window.location.search);
  var id = params.get("v");

  var wrap = document.getElementById("pia-watch");
  var statusEl = wrap.querySelector(".pia-watch-status");
  var titleEl = wrap.querySelector(".pia-watch-title");
  var embedEl = wrap.querySelector(".pia-watch-embed");
  var descEl = wrap.querySelector(".pia-watch-desc");

  if (!id || !/^\d+$/.test(id)){
    statusEl.textContent = "Missing video id.";
    return;
  }

  var vimeoUrl = "https://vimeo.com/" + id;
  var endpoint = "https://vimeo.com/api/oembed.json?url=" + encodeURIComponent(vimeoUrl) + "&responsive=1";

  statusEl.textContent = "Loading video...";

  fetch(endpoint).then(function(r){
    if(!r.ok) throw new Error("oEmbed failed: " + r.status);
    return r.json();
  }).then(function(data){
    statusEl.textContent = "";
    titleEl.textContent = data.title || "";
    embedEl.innerHTML = data.html || "";
    descEl.textContent = (data.description || "").trim();
  }).catch(function(err){
    console.error(err);
    statusEl.textContent = "Could not load video.";
  });
})();
</script>
