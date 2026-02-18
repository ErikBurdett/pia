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

  function looksLikeAdvancedAdsInlineJs(text){
    var t = String(text || '').replace(/^\s+|\s+$/g, '');
    if (!t) return false;
    if (t.indexOf('advanced_ads_ready') !== -1) return true;
    if (t.indexOf('advads') !== -1 && t.indexOf('jQuery') !== -1 && /function\s*\(/.test(t)) return true;
    return false;
  }

  function ensurePatriGridCss(){
    if (document.getElementById('pia-patri-grid-css')) return;
    var css = [
      '.pia-sidepanel-ads ul[id^="patri-grid-"]{list-style:none;margin:0;padding:0;overflow:hidden;}',
      '.pia-sidepanel-ads ul[id^="patri-grid-"]>li{float:left;width:17%;min-width:175px;list-style:none;margin:0 3% 3% 0;padding:0;overflow:hidden;}',
      '.pia-sidepanel-ads ul[id^="patri-grid-"]>li.last{margin-right:0;}',
      '.pia-sidepanel-ads ul[id^="patri-grid-"]>li.last+li{clear:both;}'
    ].join('');
    var style = document.createElement('style');
    style.id = 'pia-patri-grid-css';
    style.textContent = css;
    (document.head || document.documentElement).appendChild(style);
  }

  function removeVisibleInlineCodeArtifacts(slotEl){
    if (!slotEl) return;

    function isArtifactNode(el, kind){
      if (!el || el.nodeType !== 1) return false;
      var txt = String(el.textContent || '').trim();
      if (!txt) return false;
      if (txt.length > 80000) return false;

      // Don't remove real ad markup.
      if (el.querySelector && el.querySelector('img,iframe,video,svg,canvas,form,input,textarea,select,button')) return false;

      if (kind === 'js') {
        return txt.indexOf('advanced_ads_ready') !== -1 && txt.indexOf('unslider') !== -1;
      }
      if (kind === 'css') {
        return (txt.indexOf('#patri-grid-') !== -1 || txt.indexOf('patri-grid-') !== -1) && txt.indexOf('{') !== -1 && txt.indexOf('}') !== -1;
      }
      return false;
    }

    // Remove inline scripts/styles that belong to patri grid/slider to avoid “showing code as text”.
    var scripts = slotEl.querySelectorAll('script');
    for (var i = scripts.length - 1; i >= 0; i--) {
      var s = scripts[i];
      var t = String(s.text || s.textContent || '');
      if (t.indexOf('advanced_ads_ready') !== -1 && t.indexOf('unslider') !== -1) s.remove();
    }
    var styles = slotEl.querySelectorAll('style');
    for (var j = styles.length - 1; j >= 0; j--) {
      var st = styles[j];
      var ct = String(st.textContent || '');
      if (ct.indexOf('#patri-grid-') !== -1 || ct.indexOf('patri-grid-') !== -1) st.remove();
    }

    // Remove sanitizer-wrapped JS/CSS that is being rendered as normal text.
    var all = slotEl.querySelectorAll('*');
    for (var k = all.length - 1; k >= 0; k--) {
      var el = all[k];
      if (isArtifactNode(el, 'js') || isArtifactNode(el, 'css')) {
        el.remove();
      }
    }
  }

  function initPatriSliders(slotEl){
    if (!slotEl || !window.jQuery) return;
    if (!jQuery.fn || !jQuery.fn.unslider) return;

    var sliders = slotEl.querySelectorAll('.custom-slider[class*="patri-slider-"]');
    for (var i = 0; i < sliders.length; i++) {
      var el = sliders[i];
      if (!el || el.getAttribute('data-pia-unslider') === '1') continue;
      el.setAttribute('data-pia-unslider', '1');

      var $s = jQuery(el);
      if ($s.data('unslider')) continue;

      $s.on('unslider.ready', function() {
        jQuery('div.custom-slider ul li').css('display', 'block');
      });

      $s.unslider({ delay: 10000, autoplay: true, nav: false, arrows: false, infinite: true });

      $s.on('mouseover', function() { $s.unslider('stop'); })
        .on('mouseout', function() { $s.unslider('start'); });
    }
  }

  function postProcessSlot(slotEl){
    ensurePatriGridCss();
    removeVisibleInlineCodeArtifacts(slotEl);

    // Unslider may load late; retry a bit.
    var tries = 0;
    (function retry(){
      tries++;
      initPatriSliders(slotEl);
      if (tries < 15 && (!window.jQuery || !jQuery.fn || !jQuery.fn.unslider)) {
        setTimeout(retry, 200);
      }
    })();
  }

  function activateAdScriptsIn(slotEl){
    if (!slotEl) return;

    function isSliderInitJs(text){
      var t = String(text || '');
      return t.indexOf('advanced_ads_ready') !== -1 && t.indexOf('unslider') !== -1;
    }

    function normalizeInlineJsText(js){
      // Sometimes sanitizers leave literal "\n" sequences in text nodes.
      return String(js || '')
        .replace(/\\r\\n/g, '\n')
        .replace(/\\n/g, '\n')
        .replace(/\\t/g, '\t');
    }

    function hasForbiddenDescendants(el){
      // If these are present, this isn't just "displayed text".
      return !!(el.querySelector && el.querySelector('img,iframe,video,svg,canvas,form,input,textarea,select,button,a,ul,ol,li,table'));
    }

    function isTextyLineElement(el){
      if (!el || el.nodeType !== 1) return false;
      var tn = (el.tagName || '').toUpperCase();
      if (tn === 'SCRIPT' || tn === 'STYLE' || tn === 'NOSCRIPT' || tn === 'TEXTAREA') return false;
      if (hasForbiddenDescendants(el)) return false;
      // Must not contain structural markup besides BR/SPAN/CODE.
      var kids = el.childNodes || [];
      for (var i = 0; i < kids.length; i++) {
        var k = kids[i];
        if (!k) continue;
        if (k.nodeType === 3) continue;
        if (k.nodeType !== 1) return false;
        var kt = (k.tagName || '').toUpperCase();
        if (kt !== 'BR' && kt !== 'SPAN' && kt !== 'CODE') return false;
      }
      return String(el.textContent || '').trim().length > 0;
    }

    function tryJoinSiblingLinesIntoScript(startEl){
      if (!startEl || startEl.nodeType !== 1) return false;
      if (!isTextyLineElement(startEl)) return false;

      var parent = startEl.parentNode;
      if (!parent || parent.nodeType !== 1) return false;
      if ((parent.tagName || '').toUpperCase() === 'SCRIPT') return false;

      var js = '';
      var nodesToRemove = [];
      var cur = startEl;
      var maxNodes = 80;

      while (cur && maxNodes-- > 0) {
        if (cur.nodeType === 1) {
          if (!isTextyLineElement(cur)) break;
          js += String(cur.textContent || '') + '\n';
          nodesToRemove.push(cur);
        } else if (cur.nodeType === 3) {
          if (String(cur.nodeValue || '').trim()) {
            js += String(cur.nodeValue || '') + '\n';
            nodesToRemove.push(cur);
          }
        } else if (cur.nodeType === 1 && (cur.tagName || '').toUpperCase() === 'BR') {
          js += '\n';
          nodesToRemove.push(cur);
        }

        var norm = normalizeInlineJsText(js);
        if (isSliderInitJs(norm) && (norm.indexOf('});});') !== -1 || norm.indexOf('}); });') !== -1 || norm.indexOf('unslider("start")') !== -1)) {
          js = norm;
          break;
        }

        if (js.length > 50000) {
          js = normalizeInlineJsText(js);
          break;
        }

        cur = cur.nextSibling;
      }

      js = normalizeInlineJsText(js).trim();
      if (!isSliderInitJs(js)) return false;

      var s = document.createElement('script');
      s.setAttribute('data-pia-activated', '1');
      s.text = js;

      // Insert before the first node, then remove the run.
      var first = nodesToRemove[0];
      if (first && first.parentNode) first.parentNode.insertBefore(s, first);
      for (var i = 0; i < nodesToRemove.length; i++) {
        var n = nodesToRemove[i];
        if (n && n.parentNode) n.parentNode.removeChild(n);
      }
      return true;
    }

    function isAllowedJsWrapper(el){
      if (!el || el.nodeType !== 1) return false;
      var tn = (el.tagName || '').toUpperCase();
      if (tn === 'SCRIPT' || tn === 'STYLE' || tn === 'NOSCRIPT' || tn === 'TEXTAREA') return false;
      // Wrapper must contain only BR/SPAN and text (common sanitizers).
      var kids = el.childNodes || [];
      for (var i = 0; i < kids.length; i++) {
        var k = kids[i];
        if (!k) continue;
        if (k.nodeType === 3) continue; // text
        if (k.nodeType !== 1) return false;
        var kt = (k.tagName || '').toUpperCase();
        if (kt !== 'BR' && kt !== 'SPAN') return false;
      }
      var txt = String(el.textContent || '').trim();
      if (!txt) return false;
      if (txt.length > 50000) return false;
      txt = normalizeInlineJsText(txt);
      // Only treat as "whole script" if it looks complete; otherwise we'll join siblings.
      if (isSliderInitJs(txt) && (txt.indexOf('});});') !== -1 || txt.indexOf('}); });') !== -1 || txt.indexOf('unslider("start")') !== -1)) return true;
      return false;
    }

    function replaceWrapperWithScript(el){
      var js = String(el.textContent || '');
      if (!js) return false;
      var s = document.createElement('script');
      s.setAttribute('data-pia-activated', '1');
      s.text = js;
      if (el.parentNode) el.parentNode.replaceChild(s, el);
      return true;
    }

    // Phase 1: handle sanitizer-wrapped inline JS blocks like <p><span>..</span><br>..</p>
    try {
      var all = slotEl.querySelectorAll('*');
      for (var ai = 0; ai < all.length; ai++) {
        var el = all[ai];
        if (!el || el.getAttribute && el.getAttribute('data-pia-activated') === '1') continue;
        // First try joining line-split blocks (e.g. multiple <p> siblings).
        if (String(el.textContent || '').indexOf('advanced_ads_ready') !== -1) {
          if (tryJoinSiblingLinesIntoScript(el)) continue;
        }
        if (!isAllowedJsWrapper(el)) continue;
        replaceWrapperWithScript(el);
      }
    } catch (e) { /* ignore */ }

    // Some Advanced Ads outputs return raw JS outside <script> tags; convert to <script>.
    function collectTextNodes(root, out){
      if (!root || !out) return;
      // Never treat real script/style contents as "visible text".
      if (root.nodeType === 1) {
        var tn = (root.tagName || '').toUpperCase();
        if (tn === 'SCRIPT' || tn === 'STYLE' || tn === 'NOSCRIPT' || tn === 'TEXTAREA') return;
      }
      var kids = root.childNodes;
      if (!kids || !kids.length) return;
      for (var i = 0; i < kids.length; i++) {
        var n = kids[i];
        if (!n) continue;
        if (n.nodeType === 3) out.push(n);
        else if (n.nodeType === 1) collectTextNodes(n, out);
      }
    }

    function upgradeIfWrappedJsText(textNode){
      var p = textNode && textNode.parentNode;
      if (!p || p.nodeType !== 1) return false;
      var tag = (p.tagName || '').toUpperCase();
      if (tag === 'SCRIPT' || tag === 'STYLE' || tag === 'NOSCRIPT' || tag === 'TEXTAREA') return false;

      // Only do this when the wrapper is basically "just text" (optionally with <br>).
      var kids = p.childNodes || [];
      for (var i = 0; i < kids.length; i++) {
        var k = kids[i];
        if (!k) continue;
        if (k.nodeType === 1 && (k.tagName || '').toUpperCase() !== 'BR') return false;
      }

      var js = String(p.textContent || '');
      if (!looksLikeAdvancedAdsInlineJs(js)) return false;

      var s = document.createElement('script');
      s.setAttribute('data-pia-activated', '1');
      s.text = js;
      if (p.parentNode) p.parentNode.replaceChild(s, p);
      return true;
    }

    var textNodes = [];
    collectTextNodes(slotEl, textNodes);
    for (var i = 0; i < textNodes.length; i++) {
      var n = textNodes[i];
      var v = n && n.nodeValue;
      if (!v) continue;
      if (!looksLikeAdvancedAdsInlineJs(v)) continue;
      if (upgradeIfWrappedJsText(n)) continue;
      var s = document.createElement('script');
      s.setAttribute('data-pia-activated', '1');
      s.text = String(v);
      if (n.parentNode) n.parentNode.replaceChild(s, n);
    }

    // Phase 2b: if JS got split across multiple text/BR siblings, join and replace as one script.
    try {
      var more = [];
      collectTextNodes(slotEl, more);
      for (var mi = 0; mi < more.length; mi++) {
        var tn = more[mi];
        if (!tn || !tn.nodeValue) continue;
        if (!isSliderInitJs(tn.nodeValue) && !looksLikeAdvancedAdsInlineJs(tn.nodeValue)) continue;
        var p = tn.parentNode;
        if (!p || p.nodeType !== 1) continue;
        if ((p.tagName || '').toUpperCase() === 'SCRIPT') continue;

        var js = '';
        var run = [];
        var cur = tn;
        while (cur) {
          if (cur.nodeType === 3) {
            js += cur.nodeValue;
            run.push(cur);
          } else if (cur.nodeType === 1 && (cur.tagName || '').toUpperCase() === 'BR') {
            js += '\n';
            run.push(cur);
          } else {
            break;
          }
          cur = cur.nextSibling;
          if (js.length > 50000) break;
          if (js.indexOf('});});') !== -1 || js.indexOf('}); });') !== -1) break;
        }

        js = String(js || '').trim();
        if (!js) continue;
        if (!isSliderInitJs(js) && !looksLikeAdvancedAdsInlineJs(js)) continue;

        var sc = document.createElement('script');
        sc.setAttribute('data-pia-activated', '1');
        sc.text = js;
        if (run.length) {
          var first = run[0];
          if (first.parentNode) first.parentNode.insertBefore(sc, first);
          for (var ri = 0; ri < run.length; ri++) {
            var r = run[ri];
            if (r && r.parentNode) r.parentNode.removeChild(r);
          }
        }
      }
    } catch (e2) { /* ignore */ }

    // Scripts inserted via innerHTML don't reliably execute; re-insert them to run.
    var scripts = slotEl.querySelectorAll('script');
    for (var j = 0; j < scripts.length; j++) {
      var old = scripts[j];
      if (!old || old.getAttribute('data-pia-activated') === '1') continue;

      var type = (old.getAttribute('type') || '').toLowerCase();
      if (type && type !== 'text/javascript' && type !== 'application/javascript' && type !== 'module') continue;

      var neu = document.createElement('script');
      for (var k = 0; k < old.attributes.length; k++) {
        var a = old.attributes[k];
        if (a && a.name) neu.setAttribute(a.name, a.value);
      }
      neu.setAttribute('data-pia-activated', '1');

      if (old.src) {
        neu.src = old.src;
      } else {
        neu.text = old.text || old.textContent || '';
      }

      if (old.parentNode) old.parentNode.replaceChild(neu, old);
    }
  }

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
    var newSlots = [];

    for (var i=0; i<results.length; i++) {
      var res = results[i];
      if (!res || res.status !== 'success' || !res.item) continue;

      var slot = document.createElement('div');
      slot.className = 'pia-ad-slot';
      slot.innerHTML = String(res.item);

      frag.appendChild(slot);
      newSlots.push(slot);
      added++;
      if (added >= targetCount) break;
    }

    if (frag.childNodes.length) {
      container.appendChild(frag);
      // Must happen after insertion into the live DOM to guarantee execution.
      for (var s = 0; s < newSlots.length; s++) {
        activateAdScriptsIn(newSlots[s]);
        postProcessSlot(newSlots[s]);
      }
    }
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
