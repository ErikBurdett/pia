/*
PASTE INTO AN AVADA CODE BLOCK (LEFT SIDEPANEL)

<div class="pia-sidepanel-ads pia-sidepanel-ads--left" data-pia-sidepanel-ads="left" data-pia-count="10" aria-label="Sidebar ads (left)">
  <div class="pia-sidepanel-ads__status">Loading…</div>
</div>

<script>
(function(){
  // Same script can exist in both left/right code blocks.
  if (window.__piaSidepanelAdsBucketsInit) return;
  window.__piaSidepanelAdsBucketsInit = true;

  // Optional override if you ever need it; normally omit and let admin-ajax determine blog.
  // Set e.g. <div ... data-pia-blog-id="13">
  function getBlogIdFromContainer(container){
    try {
      var v = container && container.getAttribute ? container.getAttribute('data-pia-blog-id') : '';
      var n = parseInt(String(v || ''), 10);
      return Number.isFinite(n) && n > 0 ? n : 0;
    } catch (e) { return 0; }
  }

  var PIA_EXCLUDE_IDS = { 5122: true, 5957: true }; // "Ad Space Available" (site-specific)

  // Preference buckets from the 2026-02-13 export.
  // - vertical banners
  // - square/rectangle banners
  // - large landscape / slides
  // - buttons
  // - other sized
  // - unsized (last)
  var PIA_G_VERTICAL = [5955, 5971, 5973, 5975, 5977, 5979, 5981, 6016, 6018, 6020, 6022, 5714];
  var PIA_G_SQUARE = [5917, 5919, 5921, 5923, 5927, 5929, 5931, 5933, 5935, 5937, 5939, 5941, 5943, 5945, 5947];
  var PIA_G_SLIDES = [5949, 5025, 5951, 5953, 5959, 5961, 5965, 5967, 5969];
  var PIA_G_BUTTONS = [5925, 5983, 5985, 5987, 5989, 5991, 5993, 5995, 5997, 5999, 6001, 6003, 6005, 6007, 6009, 6011, 5730];
  var PIA_G_OTHER = [5160, 5963, 5419, 6013, 5422, 6014];
  var PIA_G_UNSIZED = [4828, 5157, 5166, 5185, 5198, 5228, 5231, 5613, 5615, 5689, 5692, 5716, 5731, 5732, 5734, 5735, 5736, 5737, 5738, 5739, 5740, 5741, 5742, 5743, 5744, 5745, 5746, 5747, 5748, 5749, 5750, 5751, 5752, 5753, 5755, 4932, 4934, 4938, 4941, 4948, 4955, 4957, 4982, 4988, 5028, 5103, 5104, 5136, 5201, 5215, 5407, 5423, 5424, 5437];

  function stripTrailingSlashes(u){ return String(u || '').replace(/\/+$/, ''); }
  function wpBaseUrl(){
    // In multisite subdirectory installs this is a reliable way to get /county.
    var link = document.querySelector('link[rel="https://api.w.org/"]');
    if (link && link.href) return stripTrailingSlashes(link.href.replace(/\/wp-json\/?$/, ''));
    return stripTrailingSlashes((window.location.origin || '') + (window.location.pathname || '').split('/').slice(0,2).join('/'));
  }
  function ajaxUrl(){ return wpBaseUrl() + '/wp-admin/admin-ajax.php'; }

  function shuffle(arr){
    for (var i = arr.length - 1; i > 0; i--) {
      var j = Math.floor(Math.random() * (i + 1));
      var t = arr[i]; arr[i] = arr[j]; arr[j] = t;
    }
    return arr;
  }
  function uniq(arr){
    var seen = {};
    var out = [];
    for (var i=0; i<arr.length; i++){
      var v = arr[i];
      if (!v || PIA_EXCLUDE_IDS[v]) continue;
      if (!seen[v]) { seen[v] = true; out.push(v); }
    }
    return out;
  }
  function isMobileViewport(){
    try { return window.matchMedia && window.matchMedia('(max-width: 800px)').matches; }
    catch (e) { return false; }
  }
  function looksLikeAdSpaceAvailableHtml(html){
    return String(html || '').toLowerCase().indexOf('ad space available') !== -1;
  }

  function buildPriorityOrder(){
    // Round-robin across preference buckets so both sidebars get a similar mix.
    var groups = [
      shuffle(PIA_G_VERTICAL.slice()),
      shuffle(PIA_G_SQUARE.slice()),
      shuffle(PIA_G_SLIDES.slice()),
      shuffle(PIA_G_BUTTONS.slice()),
      shuffle(PIA_G_OTHER.slice())
    ];
    var unsized = shuffle(PIA_G_UNSIZED.slice());

    var out = [];
    var any = true;
    while (any) {
      any = false;
      for (var i=0; i<groups.length; i++) {
        while (groups[i].length && PIA_EXCLUDE_IDS[groups[i][0]]) groups[i].shift();
        if (groups[i].length) {
          out.push(groups[i].shift());
          any = true;
        }
      }
    }
    // Only start using unsized ads after sized pools are exhausted.
    return uniq(out.concat(unsized));
  }

  function ensureAllocation(reserveEach){
    window.__piaSidepanelAdsAlloc = window.__piaSidepanelAdsAlloc || null;
    if (window.__piaSidepanelAdsAlloc && window.__piaSidepanelAdsAlloc.reserveEach === reserveEach) {
      return window.__piaSidepanelAdsAlloc;
    }

    var order = buildPriorityOrder();
    var alloc = {
      reserveEach: reserveEach,
      order: order,
      cursor: 0,
      used: {},
      left: [],
      right: []
    };

    // Allocate a fixed pool for both sides so they're always unique and ordered differently.
    var needTotal = reserveEach * 2;
    while (alloc.cursor < alloc.order.length && (alloc.left.length + alloc.right.length) < needTotal) {
      var id = alloc.order[alloc.cursor++];
      if (!id || alloc.used[id] || PIA_EXCLUDE_IDS[id]) continue;
      alloc.used[id] = true;
      if (alloc.left.length < reserveEach) alloc.left.push(id);
      else alloc.right.push(id);
    }
    alloc.right.reverse(); // "opposite end" ordering

    window.__piaSidepanelAdsAlloc = alloc;
    return alloc;
  }

  function takeExtra(alloc, n, side){
    var out = [];
    while (out.length < n && alloc.cursor < alloc.order.length) {
      var id = alloc.order[alloc.cursor++];
      if (!id || alloc.used[id] || PIA_EXCLUDE_IDS[id]) continue;
      alloc.used[id] = true;
      out.push(id);
    }
    if (side === 'right') out.reverse();
    return out;
  }

  function fetchAdsHtml(adIds, side, blogId){
    var ids = (adIds || []).slice();
    if (!ids.length) return Promise.resolve([]);

    var body = new URLSearchParams();
    body.set('action', 'advads_ad_select');
    for (var i = 0; i < ids.length; i++) {
      body.set('deferedAds[' + i + '][ad_method]', 'ad');
      body.set('deferedAds[' + i + '][ad_id]', String(ids[i]));
      if (blogId && blogId > 0) body.set('deferedAds[' + i + '][blog_id]', String(blogId));
      body.set('deferedAds[' + i + '][elementId]', 'pia-sidepanel-' + side + '-' + String(ids[i]));
    }

    return fetch(ajaxUrl(), {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
      body: body.toString()
    })
    .then(function(r){ return r.json(); })
    .then(function(json){ return Array.isArray(json) ? json : []; })
    .catch(function(){ return []; });
  }

  function appendRendered(container, results, targetCount){
    var frag = document.createDocumentFragment();
    var added = 0;
    for (var i=0; i<results.length; i++) {
      var res = results[i];
      if (!res || res.status !== 'success' || !res.item) continue;
      if (looksLikeAdSpaceAvailableHtml(res.item)) continue;
      var slot = document.createElement('div');
      slot.className = 'pia-ad-slot';
      slot.innerHTML = String(res.item);
      frag.appendChild(slot);
      added++;
      if (added >= targetCount) break;
    }
    if (frag.childNodes.length) container.appendChild(frag);
    return added;
  }

  function renderInto(container){
    if (!container) return;
    if (container.getAttribute('data-pia-loaded') === '1' || container.getAttribute('data-pia-loading') === '1') return;

    var side = String(container.getAttribute('data-pia-sidepanel-ads') || '').toLowerCase();
    if (side !== 'left' && side !== 'right') return;

    if (isMobileViewport()) {
      // CSS also hides, but skipping avoids AJAX work.
      var s0 = container.querySelector('.pia-sidepanel-ads__status');
      if (s0) s0.remove();
      return;
    }

    container.setAttribute('data-pia-loading', '1');

    var target = parseInt(container.getAttribute('data-pia-count') || '10', 10);
    if (!target || target < 1) target = 10;

    var blogId = getBlogIdFromContainer(container);

    var reserveEach = Math.max(target * 2, 20);
    var alloc = ensureAllocation(reserveEach);
    var candidates = (side === 'right' ? alloc.right.slice() : alloc.left.slice());

    // Clear any previous slots (keep status wrapper).
    Array.prototype.slice.call(container.querySelectorAll('.pia-ad-slot')).forEach(function(n){ n.remove(); });

    var statusEl = container.querySelector('.pia-sidepanel-ads__status');
    if (statusEl) statusEl.textContent = 'Loading ads…';

    function applyFullHeightSpacing(){
      // Stretch the ad stack to match the full row/content height so
      // `justify-content: space-between` can distribute white space.
      try {
        var row = container.closest('.fusion-row, .fusion-builder-row, .fusion-layout-row, .fusion-builder-row-inner, .fusion-row-inner');
        var h = 0;

        if (row) {
          var cols = row.querySelectorAll('.fusion-layout-column, .fusion_builder_column, .fusion-builder-column, .fusion-column-wrapper');
          for (var i = 0; i < cols.length; i++) {
            var ch = cols[i].getBoundingClientRect().height || 0;
            if (ch > h) h = ch;
          }
          if (!h) h = row.getBoundingClientRect().height || 0;
        }

        if (!h || h < 300) {
          var main = document.querySelector('#main') || document.querySelector('main') || document.body;
          h = main ? (main.getBoundingClientRect().height || 0) : 0;
        }

        if (h && h > 0) container.style.setProperty('--pia-fill-height', Math.ceil(h) + 'px');
      } catch (e) {}
    }

    function done(added){
      container.removeAttribute('data-pia-loading');
      container.setAttribute('data-pia-loaded', '1');
      if (statusEl) {
        if (added && added > 0) statusEl.remove();
        else statusEl.textContent = 'No ads available.';
      }
      applyFullHeightSpacing();
    }

    fetchAdsHtml(candidates, side, blogId).then(function(results){
      var added = appendRendered(container, results, target);
      if (added >= target) { done(added); return; }

      // One bounded retry (grabs more IDs, still fast).
      var extraIds = takeExtra(alloc, Math.max((target - added) * 2, 8), side);
      if (!extraIds.length) { done(added); return; }
      fetchAdsHtml(extraIds, side, blogId).then(function(results2){
        added += appendRendered(container, results2, target - added);
        done(added);
      });
    });

    applyFullHeightSpacing();
    window.addEventListener('load', applyFullHeightSpacing, { once: true });
    window.addEventListener('resize', function(){
      clearTimeout(window.__piaSidepanelAdsResizeT);
      window.__piaSidepanelAdsResizeT = setTimeout(applyFullHeightSpacing, 150);
    });
  }

  function init(){
    var els = document.querySelectorAll('.pia-sidepanel-ads[data-pia-sidepanel-ads]');
    if (!els || !els.length) return;
    for (var i=0; i<els.length; i++) renderInto(els[i]);
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();
</script>

<style>
  /* Sidebar stack: spacing only, no background/padding "card" styling */
  .pia-sidepanel-ads{
    /* Spread 10 ads across full column height */
    --pia-ad-min-gap: 60px;
    --pia-edge-pad: 40px; /* extra space above first + below last */
    box-sizing: border-box;
    padding-top: var(--pia-edge-pad);
    padding-bottom: var(--pia-edge-pad);
    min-height: var(--pia-fill-height, auto);
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    gap: var(--pia-ad-min-gap);
    max-width: 100%;
  }

  .pia-sidepanel-ads .pia-ad-slot{
    margin-inline: auto !important;
    padding: 0 !important;
    background: transparent !important;
    box-shadow: none !important;
    border-radius: 0 !important;
    max-width: 100%;
  }

  /* Extra "breathing room" for the very first/last ad */
  .pia-sidepanel-ads .pia-ad-slot:first-child{ margin-top: 10px; }
  .pia-sidepanel-ads .pia-ad-slot:last-child{ margin-bottom: 10px; }

  .pia-sidepanel-ads img,
  .pia-sidepanel-ads iframe{
    display: block;
    max-width: 100%;
    height: auto;
    margin: 0 auto;
    border-radius: 0 !important;
  }

  .pia-sidepanel-ads__status{
    opacity: .8;
    text-align: center;
    font-size: 14px;
  }

  /* Non-mobile: hide sidepanels on small screens */
  @media (max-width: 800px) {
    .pia-sidepanel-ads { display:none !important; }
  }
</style>
*/

// (This file is a copy/paste snippet; keep it syntactically valid.)
void 0;
