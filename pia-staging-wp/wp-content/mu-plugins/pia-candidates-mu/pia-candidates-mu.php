<?php
/**
 * Plugin Name: PIA Candidates (MU)
 * Description: Per-site candidate profiles with directory and profile shortcodes for the PIA multisite.
 * Version: 0.4.0
 * Author: PIA
 */

if (!defined('ABSPATH')) {
    exit;
}

final class PIA_Candidates_MU {
    const POST_TYPE = 'pia_candidate';
    const TAXONOMY = 'pia_candidate_category';
    const OPTION_GROUP = 'pia_candidates_settings';
    const OPTION_NAME = 'pia_candidates_options';
    const SETTINGS_SLUG = 'pia-candidates-settings';
    const DEFAULT_FEC_CYCLE = 2024;
    const MISSING_TEXT = 'Information pending/not provided';
    const ASSET_VERSION = '0.4.0';

    public function __construct() {
        add_action('init', [$this, 'register_post_type']);
        add_action('init', [$this, 'register_taxonomy']);
        add_action('init', [$this, 'register_meta']);
        add_action('add_meta_boxes', [$this, 'register_meta_boxes']);
        add_action('save_post_' . self::POST_TYPE, [$this, 'save_meta'], 10, 2);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);

        add_action('admin_menu', [$this, 'register_settings_page']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_post_pia_candidates_import', [$this, 'handle_import']);
        add_action('admin_notices', [$this, 'render_admin_notice']);

        add_shortcode('pia_candidate_directory', [$this, 'render_directory_shortcode']);
        add_shortcode('pia_candidate_profile', [$this, 'render_profile_shortcode']);

        add_filter('template_include', [$this, 'maybe_use_single_template']);
    }

    public function register_post_type(): void {
        register_post_type(
            self::POST_TYPE,
            [
                'labels' => [
                    'name' => 'Candidates',
                    'singular_name' => 'Candidate',
                    'add_new_item' => 'Add New Candidate',
                    'edit_item' => 'Edit Candidate',
                    'new_item' => 'New Candidate',
                    'view_item' => 'View Candidate',
                    'search_items' => 'Search Candidates',
                ],
                'public' => true,
                'menu_icon' => 'dashicons-id',
                'supports' => ['title', 'editor', 'excerpt', 'thumbnail'],
                'has_archive' => true,
                'rewrite' => ['slug' => 'candidates'],
                'show_in_rest' => true,
            ]
        );
    }

    public function register_taxonomy(): void {
        register_taxonomy(
            self::TAXONOMY,
            [self::POST_TYPE],
            [
                'labels' => [
                    'name' => 'Candidate Categories',
                    'singular_name' => 'Candidate Category',
                ],
                'hierarchical' => true,
                'show_in_rest' => true,
                'rewrite' => ['slug' => 'candidate-category'],
            ]
        );
    }

    public function register_meta(): void {
        $string_meta = [
            'type' => 'string',
            'single' => true,
            'show_in_rest' => true,
            'sanitize_callback' => 'sanitize_text_field',
        ];

        register_post_meta(self::POST_TYPE, 'pia_candidate_external_id', $string_meta);
        register_post_meta(self::POST_TYPE, 'pia_candidate_state', $string_meta);
        register_post_meta(self::POST_TYPE, 'pia_candidate_county', $string_meta);
        register_post_meta(self::POST_TYPE, 'pia_candidate_district', $string_meta);
        register_post_meta(self::POST_TYPE, 'pia_candidate_office', $string_meta);
        register_post_meta(self::POST_TYPE, 'pia_candidate_website', [
            'type' => 'string',
            'single' => true,
            'show_in_rest' => true,
            'sanitize_callback' => 'esc_url_raw',
        ]);
        register_post_meta(self::POST_TYPE, 'pia_candidate_video_url', [
            'type' => 'string',
            'single' => true,
            'show_in_rest' => true,
            'sanitize_callback' => 'esc_url_raw',
        ]);
        register_post_meta(self::POST_TYPE, 'pia_candidate_portrait_id', [
            'type' => 'integer',
            'single' => true,
            'show_in_rest' => true,
            'sanitize_callback' => 'absint',
        ]);
        register_post_meta(self::POST_TYPE, 'pia_candidate_portrait_url', [
            'type' => 'string',
            'single' => true,
            'show_in_rest' => true,
            'sanitize_callback' => 'esc_url_raw',
        ]);
        register_post_meta(self::POST_TYPE, 'pia_candidate_featured', [
            'type' => 'boolean',
            'single' => true,
            'show_in_rest' => true,
            'sanitize_callback' => [$this, 'sanitize_boolean'],
        ]);
        register_post_meta(self::POST_TYPE, 'pia_candidate_approved', [
            'type' => 'boolean',
            'single' => true,
            'show_in_rest' => true,
            'sanitize_callback' => [$this, 'sanitize_boolean'],
        ]);

        for ($i = 1; $i <= 3; $i++) {
            register_post_meta(self::POST_TYPE, "pia_candidate_button_{$i}_label", $string_meta);
            register_post_meta(self::POST_TYPE, "pia_candidate_button_{$i}_url", [
                'type' => 'string',
                'single' => true,
                'show_in_rest' => true,
                'sanitize_callback' => 'esc_url_raw',
            ]);
        }
    }

    public function sanitize_boolean($value): bool {
        return (bool) $value;
    }

    public function register_meta_boxes(): void {
        add_meta_box(
            'pia-candidate-details',
            'Candidate Details',
            [$this, 'render_meta_box'],
            self::POST_TYPE,
            'normal',
            'high'
        );
    }

    public function render_meta_box($post): void {
        wp_nonce_field('pia_candidate_meta', 'pia_candidate_meta_nonce');
        $portrait_id = (int) get_post_meta($post->ID, 'pia_candidate_portrait_id', true);
        $portrait_url = (string) get_post_meta($post->ID, 'pia_candidate_portrait_url', true);
        $video_url = (string) get_post_meta($post->ID, 'pia_candidate_video_url', true);
        $external_id = (string) get_post_meta($post->ID, 'pia_candidate_external_id', true);
        $state = (string) get_post_meta($post->ID, 'pia_candidate_state', true);
        $county = (string) get_post_meta($post->ID, 'pia_candidate_county', true);
        $district = (string) get_post_meta($post->ID, 'pia_candidate_district', true);
        $office = (string) get_post_meta($post->ID, 'pia_candidate_office', true);
        $website = (string) get_post_meta($post->ID, 'pia_candidate_website', true);
        $featured = (bool) get_post_meta($post->ID, 'pia_candidate_featured', true);
        $approved = (bool) get_post_meta($post->ID, 'pia_candidate_approved', true);
        $portrait_preview = $portrait_id ? wp_get_attachment_image_url($portrait_id, 'medium') : $portrait_url;
        ?>
        <p>
            <label for="pia-candidate-external-id"><strong>External ID (optional)</strong></label><br />
            <input type="text" id="pia-candidate-external-id" name="pia_candidate_external_id" value="<?php echo esc_attr($external_id); ?>" class="widefat" />
            <small>Used to match records during imports.</small>
        </p>
        <p>
            <label for="pia-candidate-office"><strong>Office</strong></label><br />
            <input type="text" id="pia-candidate-office" name="pia_candidate_office" value="<?php echo esc_attr($office); ?>" class="widefat" />
        </p>
        <p>
            <label for="pia-candidate-state"><strong>State</strong></label><br />
            <input type="text" id="pia-candidate-state" name="pia_candidate_state" value="<?php echo esc_attr($state); ?>" class="widefat" />
        </p>
        <p>
            <label for="pia-candidate-county"><strong>County</strong></label><br />
            <input type="text" id="pia-candidate-county" name="pia_candidate_county" value="<?php echo esc_attr($county); ?>" class="widefat" />
        </p>
        <p>
            <label for="pia-candidate-district"><strong>District</strong></label><br />
            <input type="text" id="pia-candidate-district" name="pia_candidate_district" value="<?php echo esc_attr($district); ?>" class="widefat" />
        </p>
        <p>
            <label for="pia-candidate-website"><strong>Website</strong></label><br />
            <input type="url" id="pia-candidate-website" name="pia_candidate_website" value="<?php echo esc_attr($website); ?>" class="widefat" />
        </p>
        <p>
            <label for="pia-candidate-video-url"><strong>Video URL</strong></label><br />
            <input type="url" id="pia-candidate-video-url" name="pia_candidate_video_url" value="<?php echo esc_attr($video_url); ?>" class="widefat" />
        </p>
        <p>
            <label><strong>Portrait Image</strong></label><br />
            <input type="hidden" id="pia-candidate-portrait-id" name="pia_candidate_portrait_id" value="<?php echo esc_attr($portrait_id); ?>" />
            <input type="url" id="pia-candidate-portrait-url" name="pia_candidate_portrait_url" value="<?php echo esc_attr($portrait_url); ?>" class="widefat" placeholder="https://example.com/image.jpg" />
            <small>Use the media picker or paste an external image URL.</small><br />
            <button type="button" class="button" id="pia-candidate-portrait-select">Select Portrait</button>
            <button type="button" class="button" id="pia-candidate-portrait-remove">Remove</button>
        </p>
        <div id="pia-candidate-portrait-preview" style="margin-bottom:16px;">
            <?php if ($portrait_preview) : ?>
                <img src="<?php echo esc_url($portrait_preview); ?>" alt="" style="max-width:200px;height:auto;" />
            <?php endif; ?>
        </div>
        <p>
            <label>
                <input type="checkbox" name="pia_candidate_featured" value="1" <?php checked($featured); ?> />
                Featured Candidate
            </label>
        </p>
        <p>
            <label>
                <input type="checkbox" name="pia_candidate_approved" value="1" <?php checked($approved); ?> />
                PIA Approved (shows badge)
            </label>
        </p>
        <hr />
        <p><strong>CTA Buttons (shown under portrait)</strong></p>
        <?php for ($i = 1; $i <= 3; $i++) :
            $label = (string) get_post_meta($post->ID, "pia_candidate_button_{$i}_label", true);
            $url = (string) get_post_meta($post->ID, "pia_candidate_button_{$i}_url", true);
            ?>
            <p>
                <label for="pia-candidate-button-<?php echo esc_attr((string) $i); ?>-label">Button <?php echo esc_html((string) $i); ?> Label</label><br />
                <input type="text" id="pia-candidate-button-<?php echo esc_attr((string) $i); ?>-label" name="pia_candidate_button_<?php echo esc_attr((string) $i); ?>_label" value="<?php echo esc_attr($label); ?>" class="widefat" />
            </p>
            <p>
                <label for="pia-candidate-button-<?php echo esc_attr((string) $i); ?>-url">Button <?php echo esc_html((string) $i); ?> URL</label><br />
                <input type="url" id="pia-candidate-button-<?php echo esc_attr((string) $i); ?>-url" name="pia_candidate_button_<?php echo esc_attr((string) $i); ?>_url" value="<?php echo esc_attr($url); ?>" class="widefat" />
            </p>
        <?php endfor; ?>
        <?php
    }

    public function save_meta(int $post_id, $post): void {
        if (!isset($_POST['pia_candidate_meta_nonce']) || !wp_verify_nonce($_POST['pia_candidate_meta_nonce'], 'pia_candidate_meta')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        if ($post->post_type !== self::POST_TYPE) {
            return;
        }

        $fields = [
            'pia_candidate_external_id' => 'sanitize_text_field',
            'pia_candidate_state' => 'sanitize_text_field',
            'pia_candidate_county' => 'sanitize_text_field',
            'pia_candidate_district' => 'sanitize_text_field',
            'pia_candidate_office' => 'sanitize_text_field',
            'pia_candidate_video_url' => 'esc_url_raw',
            'pia_candidate_portrait_url' => 'esc_url_raw',
            'pia_candidate_website' => 'esc_url_raw',
        ];

        foreach ($fields as $key => $sanitize) {
            $value = isset($_POST[$key]) ? call_user_func($sanitize, wp_unslash($_POST[$key])) : '';
            update_post_meta($post_id, $key, $value);
        }

        $portrait_id = isset($_POST['pia_candidate_portrait_id']) ? absint($_POST['pia_candidate_portrait_id']) : 0;
        update_post_meta($post_id, 'pia_candidate_portrait_id', $portrait_id);
        update_post_meta($post_id, 'pia_candidate_featured', isset($_POST['pia_candidate_featured']) ? 1 : 0);
        update_post_meta($post_id, 'pia_candidate_approved', isset($_POST['pia_candidate_approved']) ? 1 : 0);

        for ($i = 1; $i <= 3; $i++) {
            $label_key = "pia_candidate_button_{$i}_label";
            $url_key = "pia_candidate_button_{$i}_url";
            $label = isset($_POST[$label_key]) ? sanitize_text_field(wp_unslash($_POST[$label_key])) : '';
            $url = isset($_POST[$url_key]) ? esc_url_raw(wp_unslash($_POST[$url_key])) : '';
            update_post_meta($post_id, $label_key, $label);
            update_post_meta($post_id, $url_key, $url);
        }
    }

    public function enqueue_admin_assets(string $hook): void {
        if ($hook !== 'post-new.php' && $hook !== 'post.php' && $hook !== 'settings_page_' . self::SETTINGS_SLUG) {
            return;
        }

        if ($hook === 'post-new.php' || $hook === 'post.php') {
            $screen = get_current_screen();
            if (!$screen || $screen->post_type !== self::POST_TYPE) {
                return;
            }
        }

        wp_enqueue_media();
        wp_enqueue_script('jquery');

        // IMPORTANT: MU plugins must never fatal error. Use nowdoc for inline JS to avoid PHP string escaping issues.
        $badge_field = self::OPTION_NAME . '[badge_image_url]';
        wp_add_inline_script('jquery', 'var piaCandidatesBadgeField = ' . wp_json_encode($badge_field) . ';', 'before');

        $admin_js = <<<'JS'
jQuery(function($){
    function openMediaPicker(targetInput, urlInput, previewTarget){
        var frame = wp.media({ title: 'Select Image', button: { text: 'Use this image' }, multiple: false });
        frame.on('select', function(){
            var attachment = frame.state().get('selection').first().toJSON();
            $(targetInput).val(attachment.id ? attachment.id : '');
            if (urlInput) {
                $(urlInput).val(attachment.url || '');
            }
            if (previewTarget) {
                var url = attachment.sizes && attachment.sizes.medium ? attachment.sizes.medium.url : attachment.url;
                $(previewTarget).html('<img src="' + url + '" style="max-width:200px;height:auto;" />');
            }
        });
        frame.open();
    }

    $('#pia-candidate-portrait-select').on('click', function(e){
        e.preventDefault();
        openMediaPicker('#pia-candidate-portrait-id', '#pia-candidate-portrait-url', '#pia-candidate-portrait-preview');
    });

    $('#pia-candidate-portrait-remove').on('click', function(){
        $('#pia-candidate-portrait-id').val('');
        $('#pia-candidate-portrait-url').val('');
        $('#pia-candidate-portrait-preview').html('');
    });

    // If the editor pastes an external URL, prefer it over a previously selected media ID.
    $('#pia-candidate-portrait-url').on('change', function(){
        var url = ($(this).val() || '').toString().trim();
        if (url !== '') {
            $('#pia-candidate-portrait-id').val('');
        }
    });

    $('#pia-candidates-badge-select').on('click', function(e){
        e.preventDefault();
        openMediaPicker('#pia-candidates-badge-id', 'input[name="' + piaCandidatesBadgeField + '"]', '#pia-candidates-badge-preview');
    });

    $('#pia-candidates-badge-remove').on('click', function(){
        $('#pia-candidates-badge-id').val('');
        $('input[name="' + piaCandidatesBadgeField + '"]').val('');
        $('#pia-candidates-badge-preview').html('');
    });
});
JS;
        wp_add_inline_script('jquery', $admin_js);
    }

    public function enqueue_frontend_assets(): void {
        wp_register_style(
            'pia-candidate-styles',
            false,
            [],
            self::ASSET_VERSION
        );
        wp_add_inline_style(
            'pia-candidate-styles',
            '.pia-candidate-grid{display:grid;gap:24px;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));}'
            . '.pia-candidate-directory-controls{display:flex;flex-wrap:wrap;gap:12px;align-items:end;margin:0 0 18px;padding:12px;border:1px solid #e5e5e5;border-radius:16px;background:#fff;box-shadow:0 4px 12px rgba(0,0,0,0.04);} '
            . '.pia-candidate-directory-controls label{display:block;font-size:12px;color:#334155;margin:0 0 4px;} '
            . '.pia-candidate-directory-controls input[type=search],.pia-candidate-directory-controls select{min-width:180px;max-width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:12px;background:#fff;} '
            . '.pia-candidate-directory-controls .pia-candidate-directory-count{margin-left:auto;font-size:13px;color:#475569;white-space:nowrap;} '
            . '.pia-candidate-card{border:1px solid #e5e5e5;padding:16px;text-align:center;border-radius:16px;background:#fff;box-shadow:0 4px 12px rgba(0,0,0,0.06);} '
            . '.pia-candidate-portrait{position:relative;margin-bottom:12px;} '
            . '.pia-candidate-portrait-media{border-radius:16px;overflow:hidden;aspect-ratio:2 / 3;background:#f8fafc;} '
            . '.pia-candidate-portrait-media img{width:100%;height:100%;object-fit:cover;display:block;} '
            . '.pia-candidate-portrait--placeholder{width:100%;height:100%;border:1px dashed #cbd5e1;background:#f8fafc;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:18px;color:#475569;font-size:14px;line-height:1.3;} '
            . '.pia-candidate-portrait--placeholder strong{display:block;margin-bottom:6px;color:#0f172a;} '
            . '.pia-candidate-profile .pia-candidate-portrait{max-width:420px;margin:0 auto 16px;} '
            . '.pia-candidate-badge{position:absolute;left:50%;transform:translateX(-50%);bottom:-12px;background:#fff;padding:6px 10px;border-radius:999px;box-shadow:0 2px 8px rgba(0,0,0,0.15);} '
            . '.pia-candidate-card h3{margin:24px 0 4px;font-size:18px;} '
            . '.pia-candidate-card p{margin:0 0 12px;color:#666;} '
            . '.pia-candidate-buttons{display:flex;flex-direction:column;gap:8px;margin-top:12px;} '
            . '.pia-candidate-buttons a{display:inline-block;padding:10px 14px;background:#1e4b8f;color:#fff;text-decoration:none;border-radius:999px;} '
            . '.pia-candidate-buttons .pia-candidate-button-disabled{display:inline-block;padding:10px 14px;border-radius:999px;background:#e2e8f0;color:#475569;} '
            . '.pia-candidate-tag{display:inline-block;margin-top:8px;padding:4px 10px;border-radius:999px;background:#f2f4f8;color:#2c3e50;font-size:12px;} '
            . '.pia-candidate-featured{display:inline-block;margin-top:8px;padding:4px 10px;border-radius:999px;background:#fef3c7;color:#92400e;font-size:12px;} '
        );
        wp_enqueue_style('pia-candidate-styles');

        wp_register_script('pia-candidate-directory', false, [], self::ASSET_VERSION, true);
        wp_add_inline_script(
            'pia-candidate-directory',
            "(function(){
                function normalize(v){ return (v || '').toString().trim().toLowerCase(); }
                function runDirectory(directory){
                    var search = directory.querySelector('[data-pia-candidate-search]');
                    var filters = directory.querySelectorAll('[data-pia-candidate-filter]');
                    var cards = Array.prototype.slice.call(directory.querySelectorAll('[data-pia-candidate-card]'));
                    var countEl = directory.querySelector('[data-pia-candidate-count]');
                    var emptyEl = directory.querySelector('[data-pia-candidate-empty]');

                    if (!cards.length) { return; }

                    function apply(){
                        var q = search ? normalize(search.value) : '';
                        var active = {};
                        Array.prototype.forEach.call(filters, function(sel){
                            active[sel.getAttribute('data-pia-candidate-filter')] = normalize(sel.value);
                        });

                        var shown = 0;
                        cards.forEach(function(card){
                            var ok = true;

                            if (q){
                                var blob = normalize(card.getAttribute('data-search'));
                                if (blob.indexOf(q) === -1){ ok = false; }
                            }

                            if (ok && active.office){
                                if (normalize(card.getAttribute('data-office')) !== active.office){ ok = false; }
                            }
                            if (ok && active.state){
                                if (normalize(card.getAttribute('data-state')) !== active.state){ ok = false; }
                            }
                            if (ok && active.county){
                                if (normalize(card.getAttribute('data-county')) !== active.county){ ok = false; }
                            }
                            if (ok && active.district){
                                if (normalize(card.getAttribute('data-district')) !== active.district){ ok = false; }
                            }
                            if (ok && active.category){
                                var cats = ' ' + normalize(card.getAttribute('data-category')) + ' ';
                                if (cats.indexOf(' ' + active.category + ' ') === -1){ ok = false; }
                            }
                            if (ok && active.approved){
                                if (normalize(card.getAttribute('data-approved')) !== active.approved){ ok = false; }
                            }
                            if (ok && active.featured){
                                if (normalize(card.getAttribute('data-featured')) !== active.featured){ ok = false; }
                            }

                            if (ok){
                                card.hidden = false;
                                shown++;
                            } else {
                                card.hidden = true;
                            }
                        });

                        if (countEl){
                            countEl.textContent = shown + ' result' + (shown === 1 ? '' : 's');
                        }
                        if (emptyEl){
                            emptyEl.hidden = shown !== 0;
                        }
                    }

                    if (search){
                        search.addEventListener('input', apply);
                    }
                    Array.prototype.forEach.call(filters, function(sel){
                        sel.addEventListener('change', apply);
                    });
                    apply();
                }

                function init(){
                    var dirs = document.querySelectorAll('[data-pia-candidate-directory]');
                    Array.prototype.forEach.call(dirs, runDirectory);
                }
                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', init);
                } else {
                    init();
                }
            })();"
        );
        wp_enqueue_script('pia-candidate-directory');
    }

    public function register_settings_page(): void {
        add_options_page(
            'PIA Candidates',
            'PIA Candidates',
            'manage_options',
            self::SETTINGS_SLUG,
            [$this, 'render_settings_page']
        );
    }

    public function register_settings(): void {
        register_setting(self::OPTION_GROUP, self::OPTION_NAME, [$this, 'sanitize_options']);

        add_settings_section(
            'pia_candidates_data',
            'Candidate Data Source',
            '__return_false',
            self::SETTINGS_SLUG
        );

        add_settings_field(
            'data_source_type',
            'Data Source Type',
            [$this, 'render_field_data_source_type'],
            self::SETTINGS_SLUG,
            'pia_candidates_data'
        );

        add_settings_field(
            'data_source_url',
            'Data Source URL',
            [$this, 'render_field_data_source_url'],
            self::SETTINGS_SLUG,
            'pia_candidates_data'
        );

        add_settings_field(
            'data_source_local_file',
            'Local JSON File (MU plugin)',
            [$this, 'render_field_data_source_local_file'],
            self::SETTINGS_SLUG,
            'pia_candidates_data'
        );

        add_settings_field(
            'data_source_json',
            'Inline JSON',
            [$this, 'render_field_data_source_json'],
            self::SETTINGS_SLUG,
            'pia_candidates_data'
        );

        add_settings_field(
            'fec_api_key',
            'FEC API Key',
            [$this, 'render_field_fec_api_key'],
            self::SETTINGS_SLUG,
            'pia_candidates_data'
        );

        add_settings_field(
            'fec_cycle',
            'FEC Cycle',
            [$this, 'render_field_fec_cycle'],
            self::SETTINGS_SLUG,
            'pia_candidates_data'
        );

        add_settings_field(
            'fec_offices',
            'FEC Offices',
            [$this, 'render_field_fec_offices'],
            self::SETTINGS_SLUG,
            'pia_candidates_data'
        );

        add_settings_field(
            'sos_url',
            'Texas SOS URL',
            [$this, 'render_field_sos_url'],
            self::SETTINGS_SLUG,
            'pia_candidates_data'
        );

        add_settings_section(
            'pia_candidates_defaults',
            'Directory Defaults (per site)',
            '__return_false',
            self::SETTINGS_SLUG
        );

        add_settings_field(
            'default_state',
            'Default State',
            [$this, 'render_field_default_state'],
            self::SETTINGS_SLUG,
            'pia_candidates_defaults'
        );

        add_settings_field(
            'default_county',
            'Default County',
            [$this, 'render_field_default_county'],
            self::SETTINGS_SLUG,
            'pia_candidates_defaults'
        );

        add_settings_field(
            'default_district',
            'Default District',
            [$this, 'render_field_default_district'],
            self::SETTINGS_SLUG,
            'pia_candidates_defaults'
        );

        add_settings_section(
            'pia_candidates_display',
            'Display Settings',
            '__return_false',
            self::SETTINGS_SLUG
        );

        add_settings_field(
            'badge_image',
            'PIA Approved Badge Image',
            [$this, 'render_field_badge_image'],
            self::SETTINGS_SLUG,
            'pia_candidates_display'
        );

        add_settings_field(
            'fetch_ballotpedia_images',
            'Fetch Ballotpedia Photos (on import)',
            [$this, 'render_field_fetch_ballotpedia_images'],
            self::SETTINGS_SLUG,
            'pia_candidates_display'
        );

        add_settings_field(
            'ballotpedia_images_limit',
            'Ballotpedia Photo Fetch Limit (per import)',
            [$this, 'render_field_ballotpedia_images_limit'],
            self::SETTINGS_SLUG,
            'pia_candidates_display'
        );

        add_settings_field(
            'ballotpedia_images_only_default_county',
            'Only Fetch Photos for Default County',
            [$this, 'render_field_ballotpedia_images_only_default_county'],
            self::SETTINGS_SLUG,
            'pia_candidates_display'
        );
    }

    public function sanitize_options(array $options): array {
        return [
            'data_source_type' => isset($options['data_source_type']) ? sanitize_text_field($options['data_source_type']) : 'custom_json',
            'data_source_url' => isset($options['data_source_url']) ? esc_url_raw($options['data_source_url']) : '',
            'data_source_local_file' => isset($options['data_source_local_file']) ? sanitize_text_field($options['data_source_local_file']) : '',
            'data_source_json' => isset($options['data_source_json']) ? wp_kses_post($options['data_source_json']) : '',
            'fec_api_key' => isset($options['fec_api_key']) ? sanitize_text_field($options['fec_api_key']) : '',
            'fec_cycle' => isset($options['fec_cycle']) ? absint($options['fec_cycle']) : self::DEFAULT_FEC_CYCLE,
            'fec_offices' => isset($options['fec_offices']) ? array_map('sanitize_text_field', (array) $options['fec_offices']) : [],
            'sos_url' => isset($options['sos_url']) ? esc_url_raw($options['sos_url']) : '',
            'default_state' => isset($options['default_state']) ? sanitize_text_field($options['default_state']) : '',
            'default_county' => isset($options['default_county']) ? sanitize_text_field($options['default_county']) : '',
            'default_district' => isset($options['default_district']) ? sanitize_text_field($options['default_district']) : '',
            'badge_image_id' => isset($options['badge_image_id']) ? absint($options['badge_image_id']) : 0,
            'badge_image_url' => isset($options['badge_image_url']) ? esc_url_raw($options['badge_image_url']) : '',
            'fetch_ballotpedia_images' => !empty($options['fetch_ballotpedia_images']) ? 1 : 0,
            'ballotpedia_images_limit' => isset($options['ballotpedia_images_limit']) ? max(0, absint($options['ballotpedia_images_limit'])) : 30,
            'ballotpedia_images_only_default_county' => !empty($options['ballotpedia_images_only_default_county']) ? 1 : 0,
        ];
    }

    public function render_settings_page(): void {
        if (!current_user_can('manage_options')) {
            return;
        }
        $options = $this->get_options();
        $import_url = admin_url('admin-post.php');
        ?>
        <div class="wrap">
            <h1>PIA Candidates</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields(self::OPTION_GROUP);
                do_settings_sections(self::SETTINGS_SLUG);
                submit_button('Save Settings');
                ?>
            </form>
            <hr />
            <h2>Import Candidates</h2>
            <p>Import candidates from the configured data source. This keeps manual candidates intact and updates existing records by External ID.</p>
            <form method="post" action="<?php echo esc_url($import_url); ?>">
                <?php wp_nonce_field('pia_candidates_import', 'pia_candidates_import_nonce'); ?>
                <input type="hidden" name="action" value="pia_candidates_import" />
                <?php submit_button('Run Import', 'primary', 'submit', false); ?>
            </form>
        </div>
        <?php
    }

    public function render_admin_notice(): void {
        if (!current_user_can('manage_options')) {
            return;
        }
        if (!isset($_GET['page']) || $_GET['page'] !== self::SETTINGS_SLUG) {
            return;
        }
        if (!empty($_GET['pia_candidates_notice'])) {
            $message = sanitize_text_field(wp_unslash($_GET['pia_candidates_notice']));
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($message) . '</p></div>';
        }
    }

    public function render_field_data_source_url(): void {
        $options = $this->get_options();
        ?>
        <input type="url" name="<?php echo esc_attr(self::OPTION_NAME); ?>[data_source_url]" value="<?php echo esc_attr($options['data_source_url']); ?>" class="regular-text" />
        <p class="description">Provide a JSON feed URL to populate candidates across sites when using the Custom JSON source.</p>
        <?php
    }

    public function render_field_data_source_local_file(): void {
        $options = $this->get_options();
        ?>
        <input
            type="text"
            name="<?php echo esc_attr(self::OPTION_NAME); ?>[data_source_local_file]"
            value="<?php echo esc_attr($options['data_source_local_file']); ?>"
            class="regular-text"
            placeholder="data/texas_candidates_2026-0.json"
        />
        <p class="description">
            Optional. Path relative to this MU plugin folder (e.g. <code>data/texas_candidates_2026-0.json</code>). Only <code>.json</code> files inside the plugin directory are allowed.
        </p>
        <?php
    }

    public function render_field_data_source_json(): void {
        $options = $this->get_options();
        ?>
        <textarea name="<?php echo esc_attr(self::OPTION_NAME); ?>[data_source_json]" rows="8" class="large-text code"><?php echo esc_textarea($options['data_source_json']); ?></textarea>
        <p class="description">Paste JSON directly if you prefer storing data in the WordPress admin (Custom JSON source).</p>
        <?php
    }

    public function render_field_data_source_type(): void {
        $options = $this->get_options();
        $value = $options['data_source_type'];
        ?>
        <select name="<?php echo esc_attr(self::OPTION_NAME); ?>[data_source_type]">
            <option value="custom_json" <?php selected($value, 'custom_json'); ?>>Custom JSON (URL, Local file, or Inline)</option>
            <option value="fec_api" <?php selected($value, 'fec_api'); ?>>FEC API (Federal)</option>
            <option value="tx_sos" <?php selected($value, 'tx_sos'); ?>>Texas SOS (URL/CSV)</option>
        </select>
        <p class="description">Select the data source to use when importing candidates.</p>
        <?php
    }

    public function render_field_fec_api_key(): void {
        $options = $this->get_options();
        ?>
        <input type="text" name="<?php echo esc_attr(self::OPTION_NAME); ?>[fec_api_key]" value="<?php echo esc_attr($options['fec_api_key']); ?>" class="regular-text" />
        <p class="description">Required for FEC API imports.</p>
        <?php
    }

    public function render_field_fec_cycle(): void {
        $options = $this->get_options();
        ?>
        <input type="number" name="<?php echo esc_attr(self::OPTION_NAME); ?>[fec_cycle]" value="<?php echo esc_attr((string) $options['fec_cycle']); ?>" class="small-text" />
        <p class="description">Election cycle year (e.g., 2024).</p>
        <?php
    }

    public function render_field_fec_offices(): void {
        $options = $this->get_options();
        $selected = (array) $options['fec_offices'];
        $offices = [
            'H' => 'House',
            'S' => 'Senate',
            'P' => 'President',
        ];
        foreach ($offices as $key => $label) : ?>
            <label style="margin-right:12px;">
                <input type="checkbox" name="<?php echo esc_attr(self::OPTION_NAME); ?>[fec_offices][]" value="<?php echo esc_attr($key); ?>" <?php checked(in_array($key, $selected, true)); ?> />
                <?php echo esc_html($label); ?>
            </label>
        <?php endforeach; ?>
        <p class="description">Select federal offices to import for Texas.</p>
        <?php
    }

    public function render_field_sos_url(): void {
        $options = $this->get_options();
        ?>
        <input type="url" name="<?php echo esc_attr(self::OPTION_NAME); ?>[sos_url]" value="<?php echo esc_attr($options['sos_url']); ?>" class="regular-text" />
        <p class="description">Provide a JSON or CSV URL for Texas SOS candidate data.</p>
        <?php
    }

    public function render_field_default_state(): void {
        $options = $this->get_options();
        ?>
        <input type="text" name="<?php echo esc_attr(self::OPTION_NAME); ?>[default_state]" value="<?php echo esc_attr($options['default_state']); ?>" class="regular-text" />
        <?php
    }

    public function render_field_default_county(): void {
        $options = $this->get_options();
        ?>
        <input type="text" name="<?php echo esc_attr(self::OPTION_NAME); ?>[default_county]" value="<?php echo esc_attr($options['default_county']); ?>" class="regular-text" />
        <?php
    }

    public function render_field_default_district(): void {
        $options = $this->get_options();
        ?>
        <input type="text" name="<?php echo esc_attr(self::OPTION_NAME); ?>[default_district]" value="<?php echo esc_attr($options['default_district']); ?>" class="regular-text" />
        <?php
    }

    public function render_field_badge_image(): void {
        $options = $this->get_options();
        $preview = $options['badge_image_id'] ? wp_get_attachment_image_url($options['badge_image_id'], 'thumbnail') : $options['badge_image_url'];
        ?>
        <input type="hidden" id="pia-candidates-badge-id" name="<?php echo esc_attr(self::OPTION_NAME); ?>[badge_image_id]" value="<?php echo esc_attr((string) $options['badge_image_id']); ?>" />
        <input type="url" name="<?php echo esc_attr(self::OPTION_NAME); ?>[badge_image_url]" value="<?php echo esc_attr($options['badge_image_url']); ?>" class="regular-text" placeholder="https://example.com/badge.png" />
        <button type="button" class="button" id="pia-candidates-badge-select">Select Badge</button>
        <button type="button" class="button" id="pia-candidates-badge-remove">Remove</button>
        <div id="pia-candidates-badge-preview" style="margin-top:10px;">
            <?php if ($preview) : ?>
                <img src="<?php echo esc_url($preview); ?>" alt="" style="max-width:120px;height:auto;" />
            <?php endif; ?>
        </div>
        <?php
    }

    public function render_field_fetch_ballotpedia_images(): void {
        $options = $this->get_options();
        ?>
        <label>
            <input type="checkbox" name="<?php echo esc_attr(self::OPTION_NAME); ?>[fetch_ballotpedia_images]" value="1" <?php checked(!empty($options['fetch_ballotpedia_images'])); ?> />
            Attempt to pull each candidate’s photo from their Ballotpedia page during imports.
        </label>
        <p class="description">
            Recommended. Photos are saved into the candidate’s <code>portrait_url</code> so cards and profiles match Ballotpedia. Placeholders like “Submit photo” are ignored.
        </p>
        <?php
    }

    public function render_field_ballotpedia_images_limit(): void {
        $options = $this->get_options();
        ?>
        <input
            type="number"
            min="0"
            class="small-text"
            name="<?php echo esc_attr(self::OPTION_NAME); ?>[ballotpedia_images_limit]"
            value="<?php echo esc_attr((string) $options['ballotpedia_images_limit']); ?>"
        />
        <p class="description">
            Max Ballotpedia pages to request per import (helps avoid timeouts). Set to <code>0</code> to disable fetching without turning off the setting.
        </p>
        <?php
    }

    public function render_field_ballotpedia_images_only_default_county(): void {
        $options = $this->get_options();
        ?>
        <label>
            <input type="checkbox" name="<?php echo esc_attr(self::OPTION_NAME); ?>[ballotpedia_images_only_default_county]" value="1" <?php checked(!empty($options['ballotpedia_images_only_default_county'])); ?> />
            Only fetch photos for candidates whose <strong>county</strong> matches this site’s Default County.
        </label>
        <p class="description">
            Recommended for multisite county pages (prevents pulling photos for every county when the dataset includes the entire state).
        </p>
        <?php
    }

    public function handle_import(): void {
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions.');
        }
        if (!isset($_POST['pia_candidates_import_nonce']) || !wp_verify_nonce($_POST['pia_candidates_import_nonce'], 'pia_candidates_import')) {
            wp_die('Invalid request.');
        }

        $options = $this->get_options();
        $data = $this->get_import_data();
        if (empty($data)) {
            $this->redirect_with_notice('No data found to import. Existing candidates were not changed.');
        }

        $stats = [
            'created' => 0,
            'updated' => 0,
            'images_fetched' => 0,
            'images_skipped_limit' => 0,
        ];

        foreach ($data as $candidate) {
            if (!is_array($candidate)) {
                continue;
            }

            $external_id = isset($candidate['external_id']) ? sanitize_text_field($candidate['external_id']) : '';
            $name = isset($candidate['name']) ? sanitize_text_field($candidate['name']) : '';
            if (!$name) {
                $first_name = isset($candidate['first_name']) ? sanitize_text_field($candidate['first_name']) : '';
                $last_name = isset($candidate['last_name']) ? sanitize_text_field($candidate['last_name']) : '';
                $name = trim($first_name . ' ' . $last_name);
            }
            if (!$name && $external_id) {
                $name = 'Candidate ' . $external_id;
            }
            if (!$name) {
                continue;
            }

            $existing_post_id = $this->get_post_id_by_external_id($external_id, $name);
            $post_id = $existing_post_id;
            $bio = isset($candidate['bio']) ? wp_kses_post($candidate['bio']) : '';
            $summary = isset($candidate['summary']) ? sanitize_text_field($candidate['summary']) : '';

            $post_data = [
                'post_type' => self::POST_TYPE,
                'post_title' => $name,
                'post_status' => 'publish',
            ];

            // Don't wipe manual edits when the dataset has empty fields.
            if (!$post_id) {
                $post_data['post_content'] = $bio;
                $post_data['post_excerpt'] = $summary;
            } else {
                if (trim(wp_strip_all_tags($bio)) !== '') {
                    $post_data['post_content'] = $bio;
                }
                if (trim($summary) !== '') {
                    $post_data['post_excerpt'] = $summary;
                }
            }

            if ($post_id) {
                $post_data['ID'] = $post_id;
                wp_update_post($post_data);
            } else {
                $post_id = wp_insert_post($post_data);
            }

            if (!$post_id || is_wp_error($post_id)) {
                continue;
            }

            if ($existing_post_id) {
                $stats['updated']++;
            } else {
                $stats['created']++;
            }

            $this->update_candidate_meta($post_id, $candidate, $external_id, $options, $stats);
        }

        $notice = 'Import completed. '
            . $stats['created'] . ' created, '
            . $stats['updated'] . ' updated. '
            . $stats['images_fetched'] . ' photos fetched'
            . ($stats['images_skipped_limit'] ? (' (' . $stats['images_skipped_limit'] . ' skipped due to limit).') : '.');

        $this->redirect_with_notice($notice);
    }

    private function get_import_data(): array {
        $options = $this->get_options();
        $data = [];

        switch ($options['data_source_type']) {
            case 'fec_api':
                $result = $this->get_fec_candidates($options);
                $data = $result['data'];
                if ($result['error']) {
                    $this->redirect_with_notice($result['error']);
                }
                break;
            case 'tx_sos':
                $result = $this->get_sos_candidates($options);
                $data = $result['data'];
                if ($result['error']) {
                    $this->redirect_with_notice($result['error']);
                }
                break;
            case 'custom_json':
            default:
                $result = $this->get_custom_candidates($options);
                $data = $result['data'];
                if ($result['error']) {
                    $this->redirect_with_notice($result['error']);
                }
                break;
        }

        return is_array($data) ? $data : [];
    }

    private function decode_json(string $raw) {
        $raw = trim($raw);
        if ($raw === '') {
            return [];
        }
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }

    private function decode_candidate_dataset(string $raw): array {
        $decoded = $this->decode_json($raw);
        return $this->normalize_dataset(is_array($decoded) ? $decoded : []);
    }

    private function ends_with(string $value, string $suffix): bool {
        if ($suffix === '') {
            return true;
        }
        if (strlen($suffix) > strlen($value)) {
            return false;
        }
        return substr($value, -strlen($suffix)) === $suffix;
    }

    private function is_ballotpedia_placeholder_image_url(string $url): bool {
        $url = trim($url);
        if ($url === '') {
            return false;
        }
        $parts = wp_parse_url($url);
        $host = strtolower((string) ($parts['host'] ?? ''));
        $path = (string) ($parts['path'] ?? '');
        $basename = strtolower(basename($path));

        $known_placeholders = [
            'submitphoto-150px.png',
            'silhouette_placeholder_image.png',
            'silhouette placeholder image.png',
            'silhouette_placeholder_image.jpg',
            'silhouette_placeholder_image.jpeg',
        ];

        if (in_array($basename, $known_placeholders, true)) {
            return true;
        }

        // Ballotpedia sometimes uses variations; treat any "submitphoto" in their S3 image hosts as placeholder.
        if (strpos($basename, 'submitphoto') !== false && (strpos($host, 'ballotpedia') !== false || strpos($host, 'amazonaws.com') !== false)) {
            return true;
        }

        return false;
    }

    private function extract_ballotpedia_candidate_page_url(array $candidate): string {
        if (empty($candidate['buttons']) || !is_array($candidate['buttons'])) {
            return '';
        }
        foreach ($candidate['buttons'] as $btn) {
            if (!is_array($btn) || empty($btn['url'])) {
                continue;
            }
            $url = trim((string) $btn['url']);
            if ($url === '') {
                continue;
            }
            $parts = wp_parse_url($url);
            $host = strtolower((string) ($parts['host'] ?? ''));
            if ($host === 'ballotpedia.org') {
                return esc_url_raw($url);
            }
        }
        return '';
    }

    private function fetch_ballotpedia_og_image(string $page_url): string {
        $page_url = esc_url_raw(trim($page_url));
        if ($page_url === '') {
            return '';
        }

        $response = wp_remote_get($page_url, [
            'timeout' => 12,
            'redirection' => 5,
            'headers' => [
                'User-Agent' => 'PIA-Candidates-MU/0.4.0',
                'Accept' => 'text/html,application/xhtml+xml',
            ],
        ]);
        if (is_wp_error($response)) {
            return '';
        }

        $html = (string) wp_remote_retrieve_body($response);
        if ($html === '') {
            return '';
        }

        $candidates = [];
        if (preg_match('/<meta\\s+property=[\"\']og:image[\"\']\\s+content=[\"\']([^\"\']+)[\"\']/i', $html, $m)) {
            $candidates[] = $m[1];
        }
        if (preg_match('/<meta\\s+name=[\"\']twitter:image[\"\']\\s+content=[\"\']([^\"\']+)[\"\']/i', $html, $m)) {
            $candidates[] = $m[1];
        }

        foreach ($candidates as $raw) {
            $img = esc_url_raw(trim((string) $raw));
            if ($img === '' || $this->is_ballotpedia_placeholder_image_url($img)) {
                continue;
            }
            return $img;
        }

        return '';
    }

    private function normalize_portrait_url(string $url): string {
        $url = esc_url_raw(trim($url));
        if ($url === '' || $this->is_ballotpedia_placeholder_image_url($url)) {
            return '';
        }
        return $url;
    }

    private function parse_year_from_external_id(string $external_id): int {
        if (preg_match('/\b(20\d{2})\b/', $external_id, $matches)) {
            return (int) $matches[1];
        }
        return 0;
    }

    private function should_disambiguate_ballotpedia_url(array $candidate, string $external_id): bool {
        $county = isset($candidate['county']) ? sanitize_text_field((string) $candidate['county']) : '';
        if (trim($county) === '') {
            return false;
        }

        $year = $this->parse_year_from_external_id($external_id);
        if ($year <= 0) {
            return false;
        }

        $county_slug = sanitize_title($county);
        // Local candidate IDs in this dataset follow: tx-<year>-<county>-...
        return (bool) preg_match('/^tx-' . preg_quote((string) $year, '/') . '-' . preg_quote($county_slug, '/') . '-/i', $external_id);
    }

    private function maybe_disambiguate_ballotpedia_url(string $url, array $candidate, string $external_id): string {
        $url = trim($url);
        if ($url === '') {
            return $url;
        }

        $parts = wp_parse_url($url);
        if (empty($parts['host'])) {
            return $url;
        }

        $host = strtolower((string) $parts['host']);
        if ($host !== 'ballotpedia.org' && substr($host, -strlen('.ballotpedia.org')) !== '.ballotpedia.org') {
            return $url;
        }

        $path = isset($parts['path']) ? (string) $parts['path'] : '';
        if (strpos($path, '(') !== false || strpos($path, 'candidate_') !== false) {
            // Already disambiguated (or at least not a simple name-only slug).
            return $url;
        }

        if (!$this->should_disambiguate_ballotpedia_url($candidate, $external_id)) {
            return $url;
        }

        $name = isset($candidate['name']) ? sanitize_text_field((string) $candidate['name']) : '';
        $office = isset($candidate['office']) ? sanitize_text_field((string) $candidate['office']) : '';
        $state = isset($candidate['state']) ? sanitize_text_field((string) $candidate['state']) : '';
        $year = $this->parse_year_from_external_id($external_id);

        if ($name === '' || $office === '' || $year <= 0) {
            return $url;
        }

        if (strtoupper($state) === 'TX') {
            $state = 'Texas';
        }
        if ($state === '') {
            $state = 'Texas';
        }

        // Ballotpedia local candidate pages often use: Name_(Office,_State,_candidate_<year>)
        $title = sprintf('%s (%s, %s, candidate %d)', $name, $office, $state, $year);
        $slug = str_replace(' ', '_', $title);

        // Preserve the existing scheme if present; otherwise default to https.
        $scheme = !empty($parts['scheme']) ? (string) $parts['scheme'] : 'https';
        return $scheme . '://ballotpedia.org/' . $slug;
    }

    private function is_list_array(array $value): bool {
        if ($value === []) {
            return true;
        }
        $expected = 0;
        foreach ($value as $k => $_v) {
            if ($k !== $expected) {
                return false;
            }
            $expected++;
        }
        return true;
    }

    /**
     * Accept either:
     * - a list of candidate objects: [ { ... }, { ... } ]
     * - or a grouped object: { federal: [ { ... } ], state: [ { ... } ], ... }
     */
    private function normalize_dataset(array $data): array {
        if ($data === []) {
            return [];
        }

        // Already a flat list of candidates.
        if ($this->is_list_array($data)) {
            return $data;
        }

        // Single candidate object (rare, but handle gracefully).
        if (isset($data['external_id']) || isset($data['name']) || isset($data['first_name']) || isset($data['last_name'])) {
            return [$data];
        }

        // Grouped object -> flatten any list values.
        $flattened = [];
        foreach ($data as $_group => $maybe_list) {
            if (!is_array($maybe_list)) {
                continue;
            }
            if (!$this->is_list_array($maybe_list)) {
                // Not a list; skip (could be metadata like {"generated_at": "..."}).
                continue;
            }
            foreach ($maybe_list as $candidate) {
                if (is_array($candidate)) {
                    $flattened[] = $candidate;
                }
            }
        }

        return $flattened;
    }

    private function load_json_from_local_file(string $relative_path): array {
        $relative_path = trim($relative_path);
        if ($relative_path === '') {
            return [];
        }

        // Basic hardening: only allow .json inside this plugin directory.
        if (!$this->ends_with(strtolower($relative_path), '.json')) {
            return [];
        }

        $base_dir = realpath(__DIR__);
        if (!$base_dir) {
            return [];
        }

        $candidate_path = $base_dir . '/' . ltrim($relative_path, '/\\');
        $resolved = realpath($candidate_path);
        if (!$resolved) {
            return [];
        }

        $base_dir = rtrim($base_dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (strpos($resolved, $base_dir) !== 0) {
            return [];
        }

        $raw = (string) @file_get_contents($resolved);
        return $this->decode_candidate_dataset($raw);
    }

    private function get_custom_candidates(array $options): array {
        $data = [];
        $error = '';

        // Prefer local file (Option A) when configured.
        if (!empty($options['data_source_local_file'])) {
            $data = $this->load_json_from_local_file((string) $options['data_source_local_file']);
        }

        if (empty($data) && !empty($options['data_source_url'])) {
            $response = wp_remote_get($options['data_source_url'], ['timeout' => 20]);
            if (is_wp_error($response)) {
                $error = 'Custom JSON URL request failed.';
            } else {
                $body = wp_remote_retrieve_body($response);
                $data = $this->decode_candidate_dataset($body);
            }
        }

        if (empty($data) && !empty($options['data_source_json'])) {
            $data = $this->decode_candidate_dataset($options['data_source_json']);
        }

        if (empty($data) && !$error) {
            $error = 'No data found to import.';
        }

        return [
            'data' => is_array($data) ? $data : [],
            'error' => $error,
        ];
    }

    private function get_fec_candidates(array $options): array {
        $api_key = $options['fec_api_key'];
        if (!$api_key) {
            return ['data' => [], 'error' => 'FEC API key is required.'];
        }

        $cycle = $options['fec_cycle'] ?: self::DEFAULT_FEC_CYCLE;
        $offices = !empty($options['fec_offices']) ? (array) $options['fec_offices'] : ['H', 'S', 'P'];
        $data = [];

        foreach ($offices as $office) {
            $office = strtoupper(trim($office));
            $page = 1;
            $has_more = true;

            while ($has_more) {
                $endpoint = add_query_arg(
                    [
                        'api_key' => $api_key,
                        'state' => 'TX',
                        'office' => $office,
                        'cycle' => $cycle,
                        'per_page' => 100,
                        'page' => $page,
                    ],
                    'https://api.open.fec.gov/v1/candidates/search/'
                );
                $response = wp_remote_get($endpoint, ['timeout' => 20]);
                if (is_wp_error($response)) {
                    return ['data' => [], 'error' => 'FEC API request failed.'];
                }

                $body = wp_remote_retrieve_body($response);
                $payload = $this->decode_json($body);
                if (empty($payload['results']) || !is_array($payload['results'])) {
                    $has_more = false;
                    continue;
                }

                foreach ($payload['results'] as $candidate) {
                    $data[] = $this->normalize_fec_candidate($candidate);
                }

                $pagination = $payload['pagination'] ?? [];
                $has_more = !empty($pagination['pages']) && $page < (int) $pagination['pages'];
                $page++;
            }
        }

        return ['data' => $data, 'error' => empty($data) ? 'No FEC candidates returned.' : ''];
    }

    private function normalize_fec_candidate(array $candidate): array {
        $name = $candidate['name'] ?? '';
        $external_id = $candidate['candidate_id'] ?? '';
        $office = $candidate['office_full'] ?? ($candidate['office'] ?? '');
        $district = $candidate['district'] ?? '';
        $state = $candidate['state'] ?? 'TX';

        return [
            'external_id' => $external_id,
            'name' => $name,
            'state' => $state,
            'district' => $district,
            'office' => $office,
            'website' => $candidate['website'] ?? '',
            'featured' => false,
            'approved' => false,
        ];
    }

    private function get_sos_candidates(array $options): array {
        $url = $options['sos_url'];
        if (!$url) {
            return ['data' => [], 'error' => 'Texas SOS URL is required.'];
        }

        $response = wp_remote_get($url, ['timeout' => 20]);
        if (is_wp_error($response)) {
            return ['data' => [], 'error' => 'Texas SOS request failed.'];
        }

        $body = wp_remote_retrieve_body($response);
        $json = $this->decode_json($body);
        if (!empty($json)) {
            return ['data' => $this->normalize_dataset($json), 'error' => ''];
        }

        $csv = $this->parse_csv($body);
        if (!empty($csv)) {
            $normalized = [];
            foreach ($csv as $row) {
                $normalized[] = $this->normalize_sos_row($row);
            }
            return ['data' => $normalized, 'error' => ''];
        }

        return [
            'data' => [],
            'error' => 'Texas SOS data could not be parsed.',
        ];
    }

    private function parse_csv(string $raw): array {
        $rows = [];
        $stream = fopen('php://temp', 'r+');
        if (!$stream) {
            return $rows;
        }
        fwrite($stream, $raw);
        rewind($stream);

        $headers = [];
        $line = 0;
        while (($data = fgetcsv($stream)) !== false) {
            if ($line === 0) {
                $headers = array_map('sanitize_key', $data);
                $line++;
                continue;
            }
            if (empty($data)) {
                continue;
            }
            $row = [];
            foreach ($headers as $index => $header) {
                $row[$header] = $data[$index] ?? '';
            }
            $rows[] = $row;
        }
        fclose($stream);

        return $rows;
    }

    private function normalize_sos_row(array $row): array {
        $name = $row['candidate_name'] ?? $row['name'] ?? '';
        if (!$name) {
            $first = $row['first_name'] ?? '';
            $last = $row['last_name'] ?? '';
            $name = trim($first . ' ' . $last);
        }

        return [
            'external_id' => $row['candidate_id'] ?? $row['external_id'] ?? '',
            'name' => $name,
            'state' => $row['state'] ?? 'Texas',
            'county' => $row['county'] ?? '',
            'district' => $row['district'] ?? '',
            'office' => $row['office'] ?? $row['race'] ?? '',
            'website' => $row['website'] ?? '',
            'summary' => $row['summary'] ?? '',
            'bio' => $row['bio'] ?? '',
            'featured' => !empty($row['featured']),
            'approved' => !empty($row['approved']),
        ];
    }

    private function get_post_id_by_external_id(string $external_id, string $fallback_title): int {
        if ($external_id) {
            $query = new WP_Query([
                'post_type' => self::POST_TYPE,
                'post_status' => 'any',
                'meta_key' => 'pia_candidate_external_id',
                'meta_value' => $external_id,
                'fields' => 'ids',
                'posts_per_page' => 1,
            ]);
            if (!empty($query->posts)) {
                return (int) $query->posts[0];
            }
        }

        $existing = get_page_by_title($fallback_title, OBJECT, self::POST_TYPE);
        return $existing ? (int) $existing->ID : 0;
    }

    private function update_candidate_meta(int $post_id, array $candidate, string $external_id, array $options, array &$stats): void {
        if ($external_id) {
            update_post_meta($post_id, 'pia_candidate_external_id', $external_id);
        }

        // If the import provides a portrait URL, ensure it shows by clearing any existing portrait media ID.
        if (array_key_exists('portrait_url', $candidate)) {
            $new_portrait_url = is_string($candidate['portrait_url']) ? $this->normalize_portrait_url((string) $candidate['portrait_url']) : '';
            if ($new_portrait_url !== '') {
                $old_portrait_url = (string) get_post_meta($post_id, 'pia_candidate_portrait_url', true);
                if ($new_portrait_url !== $old_portrait_url) {
                    update_post_meta($post_id, 'pia_candidate_portrait_url', $new_portrait_url);
                    update_post_meta($post_id, 'pia_candidate_portrait_id', 0);
                }
            }
        }

        // If an existing portrait URL is a Ballotpedia placeholder image, remove it so we show our placeholder element.
        $existing_portrait_url = (string) get_post_meta($post_id, 'pia_candidate_portrait_url', true);
        if ($existing_portrait_url && $this->is_ballotpedia_placeholder_image_url($existing_portrait_url)) {
            update_post_meta($post_id, 'pia_candidate_portrait_url', '');
            $existing_portrait_url = '';
        }

        $meta_map = [
            'state' => 'pia_candidate_state',
            'county' => 'pia_candidate_county',
            'district' => 'pia_candidate_district',
            'office' => 'pia_candidate_office',
            'website' => 'pia_candidate_website',
            'video_url' => 'pia_candidate_video_url',
        ];

        foreach ($meta_map as $source => $target) {
            if (isset($candidate[$source])) {
                $value = is_string($candidate[$source]) ? trim($candidate[$source]) : $candidate[$source];

                // Avoid wiping manual edits when dataset contains blanks.
                if (is_string($value) && $value === '') {
                    continue;
                }

                if ($target === 'pia_candidate_website' || $target === 'pia_candidate_video_url' || $target === 'pia_candidate_portrait_url') {
                    update_post_meta($post_id, $target, esc_url_raw((string) $value));
                } else {
                    update_post_meta($post_id, $target, sanitize_text_field((string) $value));
                }
            }
        }

        // Only update these if present in the dataset.
        if (array_key_exists('featured', $candidate)) {
            update_post_meta($post_id, 'pia_candidate_featured', !empty($candidate['featured']) ? 1 : 0);
        }
        if (array_key_exists('approved', $candidate)) {
            update_post_meta($post_id, 'pia_candidate_approved', !empty($candidate['approved']) ? 1 : 0);
        }

        if (!empty($candidate['buttons']) && is_array($candidate['buttons'])) {
            for ($i = 1; $i <= 3; $i++) {
                $button = $candidate['buttons'][$i - 1] ?? [];
                $label = isset($button['label']) ? sanitize_text_field($button['label']) : '';
                $url = isset($button['url']) ? esc_url_raw((string) $button['url']) : '';
                $url = $this->maybe_disambiguate_ballotpedia_url($url, $candidate, $external_id);
                if ($label !== '') {
                    update_post_meta($post_id, "pia_candidate_button_{$i}_label", $label);
                }
                if ($url !== '') {
                    update_post_meta($post_id, "pia_candidate_button_{$i}_url", $url);
                }
            }
        }

        if (!empty($candidate['category'])) {
            wp_set_object_terms($post_id, (array) $candidate['category'], self::TAXONOMY, false);
        }

        // Optional: fetch portrait from Ballotpedia page during import (cached in portrait_url meta).
        $fetch_enabled = !empty($options['fetch_ballotpedia_images']);
        $fetch_limit = isset($options['ballotpedia_images_limit']) ? max(0, (int) $options['ballotpedia_images_limit']) : 0;
        $only_default_county = !empty($options['ballotpedia_images_only_default_county']);

        if ($fetch_enabled && $fetch_limit > 0) {
            if (($stats['images_fetched'] + $stats['images_skipped_limit']) >= $fetch_limit) {
                $stats['images_skipped_limit']++;
                return;
            }

            $portrait_id = (int) get_post_meta($post_id, 'pia_candidate_portrait_id', true);
            $portrait_url = (string) get_post_meta($post_id, 'pia_candidate_portrait_url', true);
            $portrait_url = $this->normalize_portrait_url($portrait_url);

            // Don't override manually chosen media portraits, or existing non-placeholder URLs.
            if ($portrait_id || $portrait_url) {
                return;
            }

            if ($only_default_county && !empty($options['default_county'])) {
                $candidate_county = isset($candidate['county']) ? sanitize_text_field((string) $candidate['county']) : '';
                if (strcasecmp(trim($candidate_county), trim((string) $options['default_county'])) !== 0) {
                    return;
                }
            }

            $page_url = $this->extract_ballotpedia_candidate_page_url($candidate);
            if ($page_url === '') {
                return;
            }

            $img = $this->fetch_ballotpedia_og_image($page_url);
            if ($img !== '') {
                update_post_meta($post_id, 'pia_candidate_portrait_url', $img);
                update_post_meta($post_id, 'pia_candidate_portrait_id', 0);
                $stats['images_fetched']++;
            }
        }
    }

    private function redirect_with_notice(string $message): void {
        $url = add_query_arg([
            'page' => self::SETTINGS_SLUG,
            'pia_candidates_notice' => rawurlencode($message),
        ], admin_url('options-general.php'));
        wp_safe_redirect($url);
        exit;
    }

    private function get_options(): array {
        $defaults = [
            'data_source_type' => 'custom_json',
            'data_source_url' => '',
            'data_source_local_file' => 'data/texas_candidates_2026-0.json',
            'data_source_json' => '',
            'fec_api_key' => '',
            'fec_cycle' => self::DEFAULT_FEC_CYCLE,
            'fec_offices' => ['H', 'S', 'P'],
            'sos_url' => '',
            'default_state' => '',
            'default_county' => '',
            'default_district' => '',
            'badge_image_id' => 0,
            'badge_image_url' => '',
            'fetch_ballotpedia_images' => 1,
            'ballotpedia_images_limit' => 30,
            'ballotpedia_images_only_default_county' => 1,
        ];

        $options = get_option(self::OPTION_NAME, []);
        if (!is_array($options)) {
            $options = [];
        }

        return array_merge($defaults, $options);
    }

    private function get_badge_image(): string {
        $options = $this->get_options();
        if ($options['badge_image_id']) {
            $url = wp_get_attachment_image_url($options['badge_image_id'], 'thumbnail');
            if ($url) {
                return $url;
            }
        }

        return $options['badge_image_url'];
    }

    private function parse_boolean_shortcode_value($value, bool $default = false): bool {
        if ($value === null) {
            return $default;
        }
        if (is_bool($value)) {
            return $value;
        }
        $value = strtolower(trim((string) $value));
        if ($value === '') {
            return $default;
        }
        if (in_array($value, ['1', 'true', 'yes', 'y', 'on'], true)) {
            return true;
        }
        if (in_array($value, ['0', 'false', 'no', 'n', 'off'], true)) {
            return false;
        }
        return $default;
    }

    private function parse_per_page_value($value, int $default = 12): int {
        if ($value === null) {
            return $default;
        }
        $raw = strtolower(trim((string) $value));
        if ($raw === 'all' || $raw === '-1' || $raw === '0') {
            return -1;
        }
        $n = absint($raw);
        return $n > 0 ? $n : $default;
    }

    private function unique_directory_id(): string {
        if (function_exists('wp_unique_id')) {
            return wp_unique_id('pia-candidate-directory-');
        }
        return 'pia-candidate-directory-' . uniqid('', true);
    }

    public function render_directory_shortcode(array $atts): string {
        $options = $this->get_options();
        $raw_atts = is_array($atts) ? $atts : [];
        $atts = shortcode_atts([
            'per_page' => 12,
            'category' => '',
            // scope:
            // - auto: current behavior (use defaults for state/county/district if present)
            // - county: force county-only (uses Default County if not passed)
            // - state: statewide (ignores Default County unless explicitly passed)
            // - all: show all candidates (ignores defaults unless explicitly passed)
            'scope' => 'auto',
            'state' => $options['default_state'],
            'county' => $options['default_county'],
            'district' => $options['default_district'],
            'featured' => '',
            'approved' => '',
            // Client-side realtime filtering controls
            'search' => '1',
            'filters' => '1',
        ], $atts, 'pia_candidate_directory');

        $meta_query = ['relation' => 'AND'];

        $scope = strtolower(trim((string) ($atts['scope'] ?? 'auto')));
        if (!in_array($scope, ['auto', 'county', 'state', 'all'], true)) {
            $scope = 'auto';
        }

        $has_explicit_state = array_key_exists('state', $raw_atts);
        $has_explicit_county = array_key_exists('county', $raw_atts);
        $has_explicit_district = array_key_exists('district', $raw_atts);

        $effective_state = (string) ($atts['state'] ?? '');
        $effective_county = (string) ($atts['county'] ?? '');
        $effective_district = (string) ($atts['district'] ?? '');

        if ($scope === 'state' && trim($effective_state) === '') {
            // For Texas-only sites, make statewide easy.
            $effective_state = 'Texas';
        }

        if ($scope === 'county' && trim($effective_county) === '') {
            return '<p>Please configure a Default County (Settings → PIA Candidates) or pass a county attribute.</p>';
        }

        // Geo filters based on scope
        if ($scope === 'auto') {
            // Preserve current behavior: apply defaults/explicit values when non-empty.
            if (!empty($effective_state)) {
                $meta_query[] = [
                    'key' => 'pia_candidate_state',
                    'value' => sanitize_text_field($effective_state),
                    'compare' => 'LIKE',
                ];
            }
            if (!empty($effective_county)) {
                $meta_query[] = [
                    'key' => 'pia_candidate_county',
                    'value' => sanitize_text_field($effective_county),
                    'compare' => 'LIKE',
                ];
            }
            if (!empty($effective_district)) {
                $meta_query[] = [
                    'key' => 'pia_candidate_district',
                    'value' => sanitize_text_field($effective_district),
                    'compare' => 'LIKE',
                ];
            }
        } elseif ($scope === 'county') {
            if (!empty($effective_state)) {
                $meta_query[] = [
                    'key' => 'pia_candidate_state',
                    'value' => sanitize_text_field($effective_state),
                    'compare' => 'LIKE',
                ];
            }
            $meta_query[] = [
                'key' => 'pia_candidate_county',
                'value' => sanitize_text_field($effective_county),
                'compare' => 'LIKE',
            ];
            // Only apply district if explicitly passed.
            if ($has_explicit_district && !empty($effective_district)) {
                $meta_query[] = [
                    'key' => 'pia_candidate_district',
                    'value' => sanitize_text_field($effective_district),
                    'compare' => 'LIKE',
                ];
            }
        } elseif ($scope === 'state') {
            if (!empty($effective_state)) {
                $meta_query[] = [
                    'key' => 'pia_candidate_state',
                    'value' => sanitize_text_field($effective_state),
                    'compare' => 'LIKE',
                ];
            }
            // Only apply county/district if explicitly passed.
            if ($has_explicit_county && !empty($effective_county)) {
                $meta_query[] = [
                    'key' => 'pia_candidate_county',
                    'value' => sanitize_text_field($effective_county),
                    'compare' => 'LIKE',
                ];
            }
            if ($has_explicit_district && !empty($effective_district)) {
                $meta_query[] = [
                    'key' => 'pia_candidate_district',
                    'value' => sanitize_text_field($effective_district),
                    'compare' => 'LIKE',
                ];
            }
        } else { // scope === 'all'
            // Only apply geo filters if explicitly passed.
            if ($has_explicit_state && !empty($effective_state)) {
                $meta_query[] = [
                    'key' => 'pia_candidate_state',
                    'value' => sanitize_text_field($effective_state),
                    'compare' => 'LIKE',
                ];
            }
            if ($has_explicit_county && !empty($effective_county)) {
                $meta_query[] = [
                    'key' => 'pia_candidate_county',
                    'value' => sanitize_text_field($effective_county),
                    'compare' => 'LIKE',
                ];
            }
            if ($has_explicit_district && !empty($effective_district)) {
                $meta_query[] = [
                    'key' => 'pia_candidate_district',
                    'value' => sanitize_text_field($effective_district),
                    'compare' => 'LIKE',
                ];
            }
        }

        if ($atts['featured'] !== '') {
            $meta_query[] = [
                'key' => 'pia_candidate_featured',
                'value' => $this->parse_boolean_shortcode_value($atts['featured'], false) ? 1 : 0,
                'compare' => '=',
            ];
        }
        if ($atts['approved'] !== '') {
            $meta_query[] = [
                'key' => 'pia_candidate_approved',
                'value' => $this->parse_boolean_shortcode_value($atts['approved'], false) ? 1 : 0,
                'compare' => '=',
            ];
        }

        $posts_per_page = $this->parse_per_page_value($atts['per_page'], 12);
        $query_args = [
            'post_type' => self::POST_TYPE,
            'posts_per_page' => $posts_per_page,
            'meta_query' => $meta_query,
            'orderby' => 'title',
            'order' => 'ASC',
            'no_found_rows' => true,
        ];

        if (!empty($atts['category'])) {
            $query_args['tax_query'] = [
                [
                    'taxonomy' => self::TAXONOMY,
                    'field' => 'slug',
                    'terms' => array_map('sanitize_title', explode(',', $atts['category'])),
                ],
            ];
        }

        $posts = get_posts($query_args);
        if (empty($posts)) {
            return '<p>No candidates found.</p>';
        }

        $badge_url = $this->get_badge_image();
        $directory_id = $this->unique_directory_id();
        $show_search = $this->parse_boolean_shortcode_value($atts['search'], true);
        $show_filters = $this->parse_boolean_shortcode_value($atts['filters'], true);

        // Collect filter options from the result set.
        $office_options = [];
        $state_options = [];
        $county_options = [];
        $district_options = [];
        $category_options = [];

        foreach ($posts as $p) {
            $post_id = (int) $p->ID;
            $office = (string) get_post_meta($post_id, 'pia_candidate_office', true);
            $state = (string) get_post_meta($post_id, 'pia_candidate_state', true);
            $county = (string) get_post_meta($post_id, 'pia_candidate_county', true);
            $district = (string) get_post_meta($post_id, 'pia_candidate_district', true);

            if (trim($office) !== '') {
                $office_options[sanitize_title($office)] = $office;
            }
            if (trim($state) !== '') {
                $state_options[sanitize_title($state)] = $state;
            }
            if (trim($county) !== '') {
                $county_options[sanitize_title($county)] = $county;
            }
            if (trim($district) !== '') {
                $district_options[sanitize_title($district)] = $district;
            }

            $terms = wp_get_post_terms($post_id, self::TAXONOMY, ['fields' => 'all']);
            if (!is_wp_error($terms) && !empty($terms)) {
                foreach ($terms as $term) {
                    if (!empty($term->slug)) {
                        $category_options[$term->slug] = $term->name;
                    }
                }
            }
        }

        asort($office_options, SORT_NATURAL | SORT_FLAG_CASE);
        asort($state_options, SORT_NATURAL | SORT_FLAG_CASE);
        asort($county_options, SORT_NATURAL | SORT_FLAG_CASE);
        asort($district_options, SORT_NATURAL | SORT_FLAG_CASE);
        asort($category_options, SORT_NATURAL | SORT_FLAG_CASE);

        // Put featured candidates first, then alphabetical.
        usort($posts, function ($a, $b) {
            $a_featured = (int) get_post_meta((int) $a->ID, 'pia_candidate_featured', true);
            $b_featured = (int) get_post_meta((int) $b->ID, 'pia_candidate_featured', true);
            if ($a_featured !== $b_featured) {
                return $a_featured > $b_featured ? -1 : 1;
            }
            return strcasecmp((string) $a->post_title, (string) $b->post_title);
        });

        ob_start();
        echo '<div data-pia-candidate-directory="1" id="' . esc_attr($directory_id) . '">';

        if ($show_search || $show_filters) {
            echo '<div class="pia-candidate-directory-controls">';

            if ($show_search) {
                echo '<div class="pia-candidate-directory-search">';
                echo '<label for="' . esc_attr($directory_id . '-search') . '">Search</label>';
                echo '<input type="search" id="' . esc_attr($directory_id . '-search') . '" data-pia-candidate-search="1" placeholder="Search by name, office, county..." />';
                echo '</div>';
            }

            if ($show_filters) {
                if (!empty($office_options)) {
                    echo '<div><label for="' . esc_attr($directory_id . '-office') . '">Office</label><select id="' . esc_attr($directory_id . '-office') . '" data-pia-candidate-filter="office"><option value="">All</option>';
                    foreach ($office_options as $value => $label) {
                        echo '<option value="' . esc_attr((string) $value) . '">' . esc_html((string) $label) . '</option>';
                    }
                    echo '</select></div>';
                }
                if (!empty($county_options)) {
                    echo '<div><label for="' . esc_attr($directory_id . '-county') . '">County</label><select id="' . esc_attr($directory_id . '-county') . '" data-pia-candidate-filter="county"><option value="">All</option>';
                    foreach ($county_options as $value => $label) {
                        echo '<option value="' . esc_attr((string) $value) . '">' . esc_html((string) $label) . '</option>';
                    }
                    echo '</select></div>';
                }
                if (!empty($district_options)) {
                    echo '<div><label for="' . esc_attr($directory_id . '-district') . '">District</label><select id="' . esc_attr($directory_id . '-district') . '" data-pia-candidate-filter="district"><option value="">All</option>';
                    foreach ($district_options as $value => $label) {
                        echo '<option value="' . esc_attr((string) $value) . '">' . esc_html((string) $label) . '</option>';
                    }
                    echo '</select></div>';
                }
                if (!empty($state_options)) {
                    echo '<div><label for="' . esc_attr($directory_id . '-state') . '">State</label><select id="' . esc_attr($directory_id . '-state') . '" data-pia-candidate-filter="state"><option value="">All</option>';
                    foreach ($state_options as $value => $label) {
                        echo '<option value="' . esc_attr((string) $value) . '">' . esc_html((string) $label) . '</option>';
                    }
                    echo '</select></div>';
                }
                if (!empty($category_options)) {
                    echo '<div><label for="' . esc_attr($directory_id . '-category') . '">Category</label><select id="' . esc_attr($directory_id . '-category') . '" data-pia-candidate-filter="category"><option value="">All</option>';
                    foreach ($category_options as $value => $label) {
                        echo '<option value="' . esc_attr((string) $value) . '">' . esc_html((string) $label) . '</option>';
                    }
                    echo '</select></div>';
                }

                echo '<div><label for="' . esc_attr($directory_id . '-approved') . '">Approved</label><select id="' . esc_attr($directory_id . '-approved') . '" data-pia-candidate-filter="approved"><option value="">All</option><option value="1">Approved</option><option value="0">Not approved</option></select></div>';
                echo '<div><label for="' . esc_attr($directory_id . '-featured') . '">Featured</label><select id="' . esc_attr($directory_id . '-featured') . '" data-pia-candidate-filter="featured"><option value="">All</option><option value="1">Featured</option><option value="0">Not featured</option></select></div>';
            }

            echo '<div class="pia-candidate-directory-count" data-pia-candidate-count="1"></div>';
            echo '</div>';
        }

        echo '<div class="pia-candidate-grid">';
        foreach ($posts as $post) {
            setup_postdata($post);
            $post_id = (int) $post->ID;
            $state = (string) get_post_meta($post_id, 'pia_candidate_state', true);
            $county = (string) get_post_meta($post_id, 'pia_candidate_county', true);
            $district = (string) get_post_meta($post_id, 'pia_candidate_district', true);
            $office = (string) get_post_meta($post_id, 'pia_candidate_office', true);
            $approved = (bool) get_post_meta($post_id, 'pia_candidate_approved', true);
            $featured = (bool) get_post_meta($post_id, 'pia_candidate_featured', true);

            $term_slugs = [];
            $terms = get_the_terms($post_id, self::TAXONOMY);
            if (!empty($terms) && !is_wp_error($terms)) {
                foreach ($terms as $term) {
                    if (!empty($term->slug)) {
                        $term_slugs[] = $term->slug;
                    }
                }
            }

            $search_blob = strtolower(implode(' ', array_filter([
                (string) get_the_title($post_id),
                $office,
                $state,
                $county,
                $district,
                implode(' ', $term_slugs),
            ])));

            echo '<article class="pia-candidate-card" data-pia-candidate-card="1"'
                . ' data-office="' . esc_attr(sanitize_title($office)) . '"'
                . ' data-state="' . esc_attr(sanitize_title($state)) . '"'
                . ' data-county="' . esc_attr(sanitize_title($county)) . '"'
                . ' data-district="' . esc_attr(sanitize_title($district)) . '"'
                . ' data-category="' . esc_attr(implode(' ', $term_slugs)) . '"'
                . ' data-approved="' . esc_attr($approved ? '1' : '0') . '"'
                . ' data-featured="' . esc_attr($featured ? '1' : '0') . '"'
                . ' data-search="' . esc_attr($search_blob) . '"'
                . '>';
            echo $this->get_candidate_portrait_html($post_id, $badge_url, $approved, 'medium');
            echo '<h3>' . esc_html(get_the_title($post_id)) . '</h3>';
            $office_line = $office ? ('For ' . $office) : self::MISSING_TEXT;
            echo '<p>' . esc_html($office_line) . '</p>';
            $parts = array_filter([$state, $county, $district]);
            $location = implode(' • ', $parts);
            echo '<span class="pia-candidate-tag">' . esc_html($location ?: self::MISSING_TEXT) . '</span>';
            if ($featured) {
                echo '<span class="pia-candidate-featured">Featured</span>';
            }
            echo '<div class="pia-candidate-buttons">';
            echo '<a href="' . esc_url(get_permalink($post_id)) . '">Candidate Profile</a>';
            $website = (string) get_post_meta($post_id, 'pia_candidate_website', true);
            if ($website) {
                echo '<a href="' . esc_url($website) . '" target="_blank" rel="noopener">Website</a>';
            } else {
                // Fallback: if no website meta is set, show the first configured CTA button instead of a disabled pill.
                $fallback_label = '';
                $fallback_url = '';
                for ($i = 1; $i <= 3; $i++) {
                    $label = (string) get_post_meta($post_id, "pia_candidate_button_{$i}_label", true);
                    $url = (string) get_post_meta($post_id, "pia_candidate_button_{$i}_url", true);
                    if (trim($label) !== '' && trim($url) !== '') {
                        $fallback_label = $label;
                        $fallback_url = $url;
                        break;
                    }
                }
                if ($fallback_label && $fallback_url) {
                    echo '<a href="' . esc_url($fallback_url) . '" target="_blank" rel="noopener">' . esc_html($fallback_label) . '</a>';
                } else {
                    echo '<span class="pia-candidate-button-disabled">' . esc_html(self::MISSING_TEXT) . '</span>';
                }
            }
            echo '</div>';
            echo '</article>';
        }
        echo '</div>';
        echo '<p data-pia-candidate-empty="1" hidden>No matching candidates found.</p>';
        echo '</div>';
        wp_reset_postdata();

        return (string) ob_get_clean();
    }

    public function render_profile_shortcode(array $atts): string {
        $atts = shortcode_atts([
            'id' => 0,
        ], $atts, 'pia_candidate_profile');

        $post_id = (int) $atts['id'];
        if (!$post_id && is_singular(self::POST_TYPE)) {
            $post_id = get_the_ID();
        }
        if (!$post_id) {
            return '';
        }

        $badge_url = $this->get_badge_image();
        $approved = (bool) get_post_meta($post_id, 'pia_candidate_approved', true);
        $office = (string) get_post_meta($post_id, 'pia_candidate_office', true);
        $state = (string) get_post_meta($post_id, 'pia_candidate_state', true);
        $county = (string) get_post_meta($post_id, 'pia_candidate_county', true);
        $district = (string) get_post_meta($post_id, 'pia_candidate_district', true);
        $video_url = (string) get_post_meta($post_id, 'pia_candidate_video_url', true);
        $website = (string) get_post_meta($post_id, 'pia_candidate_website', true);
        $raw_content = (string) get_post_field('post_content', $post_id);
        $excerpt = (string) get_post_field('post_excerpt', $post_id);

        ob_start();
        echo '<div class="pia-candidate-profile">';
        echo $this->get_candidate_portrait_html($post_id, $badge_url, $approved, 'large');
        echo '<h2>' . esc_html(get_the_title($post_id)) . '</h2>';
        echo '<p>' . esc_html($office ?: self::MISSING_TEXT) . '</p>';
        $parts = array_filter([$state, $county, $district]);
        $location = implode(' • ', $parts);
        echo '<p class="pia-candidate-tag">' . esc_html($location ?: self::MISSING_TEXT) . '</p>';

        if (trim(wp_strip_all_tags($raw_content)) !== '') {
            echo apply_filters('the_content', $raw_content);
        } elseif (trim(wp_strip_all_tags($excerpt)) !== '') {
            echo '<p>' . esc_html($excerpt) . '</p>';
        } else {
            echo '<p>' . esc_html(self::MISSING_TEXT) . '</p>';
        }

        if ($website) {
            echo '<p><a href="' . esc_url($website) . '" target="_blank" rel="noopener">Website</a></p>';
        } else {
            echo '<p>Website: ' . esc_html(self::MISSING_TEXT) . '</p>';
        }

        if ($video_url) {
            $embed = wp_oembed_get($video_url);
            if ($embed) {
                echo '<div class="pia-candidate-video">' . $embed . '</div>';
            } else {
                echo '<p><a href="' . esc_url($video_url) . '">Watch video</a></p>';
            }
        } else {
            echo '<p>Video: ' . esc_html(self::MISSING_TEXT) . '</p>';
        }

        $profile_url = (string) get_permalink($post_id);

        echo '<div class="pia-candidate-buttons">';
        if ($profile_url) {
            // Keep the primary "profile" link consistent with the directory cards (internal WP permalink).
            echo '<a href="' . esc_url($profile_url) . '">Candidate Profile</a>';
        }

        for ($i = 1; $i <= 3; $i++) {
            $label = (string) get_post_meta($post_id, "pia_candidate_button_{$i}_label", true);
            $url = (string) get_post_meta($post_id, "pia_candidate_button_{$i}_url", true);
            if (!$label || !$url) {
                continue;
            }

            // Avoid duplicates when imported data includes a "Candidate Profile" button.
            $normalized_label = strtolower(trim($label));
            if ($normalized_label === 'candidate profile' || $normalized_label === 'view profile') {
                continue;
            }
            if ($profile_url && $url === $profile_url) {
                continue;
            }

            echo '<a href="' . esc_url($url) . '" target="_blank" rel="noopener">' . esc_html($label) . '</a>';
        }
        echo '</div>';
        echo '</div>';

        return (string) ob_get_clean();
    }

    private function get_candidate_portrait_html(int $post_id, string $badge_url, bool $approved, string $size): string {
        $portrait_id = (int) get_post_meta($post_id, 'pia_candidate_portrait_id', true);
        $portrait_url = (string) get_post_meta($post_id, 'pia_candidate_portrait_url', true);
        $image_html = '';

        if ($portrait_id) {
            $image_html = wp_get_attachment_image($portrait_id, $size);
        } elseif ($portrait_url && !$this->is_ballotpedia_placeholder_image_url($portrait_url)) {
            $image_html = '<img src="' . esc_url($portrait_url) . '" alt="" />';
        } else {
            $thumbnail_id = get_post_thumbnail_id($post_id);
            if ($thumbnail_id) {
                $image_html = wp_get_attachment_image($thumbnail_id, $size);
            }
        }

        $output = '<div class="pia-candidate-portrait">';
        $output .= '<div class="pia-candidate-portrait-media">';
        if ($image_html) {
            $output .= $image_html;
        } else {
            $output .= '<div class="pia-candidate-portrait--placeholder">'
                . '<strong>Submit a photo</strong>'
                . '<div>Recommended: 200×300</div>'
                . '</div>';
        }
        $output .= '</div>';
        if ($approved && $badge_url) {
            $output .= '<span class="pia-candidate-badge"><img src="' . esc_url($badge_url) . '" alt="PIA Approved" /></span>';
        }
        $output .= '</div>';

        return $output;
    }

    public function maybe_use_single_template(string $template): string {
        if (!is_singular(self::POST_TYPE)) {
            return $template;
        }

        $theme_template = locate_template('single-' . self::POST_TYPE . '.php');
        if ($theme_template) {
            return $theme_template;
        }

        $plugin_template = __DIR__ . '/templates/single-' . self::POST_TYPE . '.php';
        if (file_exists($plugin_template)) {
            return $plugin_template;
        }

        return $template;
    }
}

new PIA_Candidates_MU();
