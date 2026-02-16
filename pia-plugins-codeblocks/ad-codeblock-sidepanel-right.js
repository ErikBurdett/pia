<!-- RIGHT SIDEBAR ADS (paste this whole block into the RIGHT codeblock) -->
<div class="pia-sidepanel-ads pia-sidepanel-ads--right"
     data-pia-sidepanel-ads="right"
     data-pia-count="10"
     aria-label="Sidebar ads (right)">
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
    var el = document.querySelector('.pia-sidepanel-ads[data-pia-sidepanel-ads="right"]');
    renderInto(el, 'right');
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();
</script>
