<?php
/**
 * The view to render the option.
 *
 * @var int $checked Value of 1, when the option is checked.
 */
?>
    <label>
        <input name="<?php echo esc_attr( ADVADS_SLUG ); ?>[layer][use-fancybox]" id="advanced-ads-layer-use-fancybox" type="checkbox" value="1" <?php echo checked( 1, $checked, false ); ?> />
        <?php esc_html_e( 'Activate this if you want to use Fancybox plugin for popup windows', 'advanced-ads-layer' ); ?>
    </label>
