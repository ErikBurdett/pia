<!-- LEFT SIDEBAR ADS (paste this whole block into the LEFT codeblock) -->
<div class="pia-sidepanel-ads pia-sidepanel-ads--left"
     data-pia-sidepanel-ads="left"
     data-pia-count="10"
     aria-label="Sidebar ads (left)">
  <div class="pia-sidepanel-ads__status">Loading…</div>
</div>

<script>
(function(){
  var PIA_BLOG_ID = 13;
  var PIA_GROUP_IDS = [109, 113, 108, 112, 101, 107];

  function stripTrailingSlashes(u){ return String(u || '').replace(/\/+$/, ''); }
  function wpBaseUrl(){
    var link = document.querySelector('link[rel="https://api.w.org/"]');
    if (link && link.href) return stripTrailingSlashes(link.href.replace(/\/wp-json\/?$/, ''));
    return stripTrailingSlashes((window.location.origin || '') + (window.location.pathname || '').split('/').slice(0,2).join('/'));
  }
  function ajaxUrl(){ return wpBaseUrl() + '/wp-admin/admin-ajax.php'; }

  function buildGroupPickList(targetCount){
    var out = [];
    var groups = (PIA_GROUP_IDS || []).filter(Boolean);
    if (!groups.length) return out;
    for (var i=0; i<targetCount; i++){
      out.push(groups[i % groups.length]);
    }
    return out;
  }

  function fetchGroupsHtml(groupIds, side){
    var ids = (groupIds || []).slice();
    if (!ids.length) return Promise.resolve([]);

    var body = new URLSearchParams();
    body.set('action', 'advads_ad_select');

    for (var i = 0; i < ids.length; i++) {
      body.set('deferedAds[' + i + '][ad_method]', 'group');
      body.set('deferedAds[' + i + '][ad_id]', String(ids[i]));
      body.set('deferedAds[' + i + '][blog_id]', String(PIA_BLOG_ID));
      body.set('deferedAds[' + i + '][elementId]', 'pia-sidepanel-' + side + '-g' + String(ids[i]) + '-' + i);
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

  function renderInto(container, side){
    if (!container || container.getAttribute('data-pia-loaded') === '1') return;
    container.setAttribute('data-pia-loaded', '1');

    var target = parseInt(container.getAttribute('data-pia-count') || '10', 10);
    if (!target || target < 1) target = 10;

    Array.prototype.slice.call(container.querySelectorAll('.pia-ad-slot')).forEach(function(n){ n.remove(); });

    var statusEl = container.querySelector('.pia-sidepanel-ads__status');
    if (statusEl) statusEl.textContent = 'Loading ads…';

    function done(){
      if (statusEl) statusEl.remove();
    }

    var groupPicks = buildGroupPickList(target);

    fetchGroupsHtml(groupPicks, side).then(function(results){
      appendRendered(container, results, target);
      done();
    });
  }

  function init(){
    var el = document.querySelector('.pia-sidepanel-ads[data-pia-sidepanel-ads="left"]');
    renderInto(el, 'left');
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();
</script>

<style>
  /* LEFT/RIGHT share the same styling */
  .pia-sidepanel-ads{
    --pia-ad-gap: 48px;
    --pia-edge-gap: 48px;

    display: flex;
    flex-direction: column;
    justify-content: flex-start;
    align-items: stretch;
    gap: var(--pia-ad-gap);

    max-width: 100%;
    padding-top: var(--pia-edge-gap);
    padding-bottom: var(--pia-edge-gap);
  }

  .pia-sidepanel-ads .pia-ad-slot{
    display: block;
    width: 100%;
    clear: both;
    margin: 0 !important;
    padding: 0 !important;
    background: transparent !important;
    box-shadow: none !important;
    border-radius: 0 !important;
    max-width: 100%;
    overflow: hidden;
  }

  .pia-sidepanel-ads .pia-ad-slot > *{
    display: block;
    width: 100%;
    max-width: 100%;
    margin-left: auto;
    margin-right: auto;
    float: none !important;
  }

  /* Existing "single column" coercion */
  .pia-sidepanel-ads .pia-ad-slot .advads-group,
  .pia-sidepanel-ads .pia-ad-slot .advads-group-item,
  .pia-sidepanel-ads .pia-ad-slot .advads-ad-wrapper,
  .pia-sidepanel-ads .pia-ad-slot .advads-ad{
    display: block !important;
    width: 100% !important;
    max-width: 100% !important;
    float: none !important;
    margin-left: auto !important;
    margin-right: auto !important;
  }

  /* ✅ NEW: force multi-item groups to stack vertically (no columns) */
  .pia-sidepanel-ads .pia-ad-slot .advads-group{
    display: flex !important;
    flex-direction: column !important;
    align-items: stretch !important;
    justify-content: flex-start !important;
    gap: 28px !important;                  /* spacing BETWEEN ads inside the group */
    width: 100% !important;
    grid-template-columns: 1fr !important; /* neutralize grid layouts */
  }

  .pia-sidepanel-ads .pia-ad-slot .advads-group-item{
    display: block !important;
    width: 100% !important;
    max-width: 100% !important;
    float: none !important;
    clear: both !important;
    margin: 0 !important;
  }

  .pia-sidepanel-ads .pia-ad-slot .advads-group-item .advads-ad-wrapper,
  .pia-sidepanel-ads .pia-ad-slot .advads-group-item .advads-ad{
    display: block !important;
    width: 100% !important;
    max-width: 100% !important;
    float: none !important;
  }

  .pia-sidepanel-ads .pia-ad-slot .advads-group *{
    float: none !important;
  }

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

  /* ============================
     HARD OVERRIDE: force any "grid" groups to 1-column
     Only inside .pia-sidepanel-ads
     ============================ */

  /* Kill CSS multi-columns if a group uses them */
  .pia-sidepanel-ads .pia-ad-slot .advads-group,
  .pia-sidepanel-ads .pia-ad-slot .advads-group *{
    column-count: 1 !important;
    column-gap: 0 !important;
    column-width: auto !important;
  }

  /* Target common Advanced Ads grid wrappers (names vary by version/settings) */
  .pia-sidepanel-ads .pia-ad-slot .advads-grid,
  .pia-sidepanel-ads .pia-ad-slot .advads-grid-wrapper,
  .pia-sidepanel-ads .pia-ad-slot .advads-ad-grid,
  .pia-sidepanel-ads .pia-ad-slot .advads-group-grid,
  .pia-sidepanel-ads .pia-ad-slot [class*="grid"]{
    display: flex !important;
    flex-direction: column !important;
    flex-wrap: nowrap !important;
    align-items: stretch !important;
    justify-content: flex-start !important;
    width: 100% !important;
    max-width: 100% !important;

    grid-template-columns: 1fr !important;
    grid-auto-flow: row !important;
  }

  /* Force every possible "item" in those wrappers to be full width */
  .pia-sidepanel-ads .pia-ad-slot .advads-grid > *,
  .pia-sidepanel-ads .pia-ad-slot .advads-grid-wrapper > *,
  .pia-sidepanel-ads .pia-ad-slot .advads-ad-grid > *,
  .pia-sidepanel-ads .pia-ad-slot .advads-group-grid > *,
  .pia-sidepanel-ads .pia-ad-slot [class*="grid"] > *,
  .pia-sidepanel-ads .pia-ad-slot .advads-group-item{
    width: 100% !important;
    max-width: 100% !important;
    flex: 0 0 100% !important;
    float: none !important;
    clear: both !important;
    display: block !important;
    box-sizing: border-box !important;
  }

  /* If a theme sets inline-block tiles, kill that too */
  .pia-sidepanel-ads .pia-ad-slot .advads-group-item,
  .pia-sidepanel-ads .pia-ad-slot .advads-group-item *{
    display: block !important;
    float: none !important;
  }

  /* Optional: consistent spacing BETWEEN items inside a group */
  .pia-sidepanel-ads .pia-ad-slot .advads-group,
  .pia-sidepanel-ads .pia-ad-slot .advads-grid,
  .pia-sidepanel-ads .pia-ad-slot .advads-grid-wrapper,
  .pia-sidepanel-ads .pia-ad-slot .advads-ad-grid,
  .pia-sidepanel-ads .pia-ad-slot .advads-group-grid,
  .pia-sidepanel-ads .pia-ad-slot [class*="grid"]{
    gap: 28px !important;
  }

  /* If inline widths (50%/33%/25%) are used, stomp them */
  .pia-sidepanel-ads .pia-ad-slot [style*="50%"],
  .pia-sidepanel-ads .pia-ad-slot [style*="33%"],
  .pia-sidepanel-ads .pia-ad-slot [style*="25%"]{
    width: 100% !important;
    flex-basis: 100% !important;
    max-width: 100% !important;
  }
</style>
