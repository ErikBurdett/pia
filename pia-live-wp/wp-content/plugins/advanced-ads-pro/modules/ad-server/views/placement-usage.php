<?php
/**
 * Show examples on how to use the ad server placement.
 *
 * @package AdvancedAds\Pro
 *
 * @var string $url URL where the ad placement can be accessed directly.
 * @var string $placement_slug placement ID.
 * @var string $public_slug public name of the placement.
 */

?>
<label>
	<p><?php esc_html_e( 'Direct URL', 'advanced-ads-pro' ); ?></p>
	<input type="text" onclick="this.select();" readonly="readonly" value="<?php echo esc_url( $url ); ?>" style="width:600px;max-width:90%;"/>
</label>
<br/><br/>
<label>
	<p>iframe</p>
	<input type="text" onclick="this.select();" readonly="readonly" value="<?php echo esc_html( '<iframe src="' . $url . '" scrolling="no" width="300" height="250" style="overflow: hidden;border:none;"></iframe>' ); ?>" style="width:600px;max-width:90%;"/>
</label>
<br/><br/>
<label>
	<p>JavaScript</p>
<?php //phpcs:disable ?>
<textarea onclick="this.select();" readonly="readonly" style="width:600px;max-width:90%;" rows="5">
<div id="<?php echo $public_slug; ?>-box"></div>
<script>
	fetch('<?php echo esc_url( $url ); ?>').then(function(e) { return e.text();}).then(function(body) { var server_parser = new DOMParser(); var doc = server_parser.parseFromString(body, "text/html"); var ad_container = doc.querySelector('div'); document.querySelector('#<?php echo $public_slug; ?>-box').innerHTML = ad_container.innerHTML; });
</script></textarea>
<?php //phpcs:enable ?>
</label>
