<?php
/**
 * Plugin Name: PIA Candidates (MU)
 * Description: Per-site candidate profiles with directory and profile shortcodes for the PIA multisite.
 * Version: 0.2.0
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
        $badge_field = self::OPTION_NAME . '[badge_image_url]';
        wp_add_inline_script(
            'jquery',
            "jQuery(function($){
                var piaCandidatesBadgeField = " . wp_json_encode($badge_field) . ";
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

                $('#pia-candidates-badge-select').on('click', function(e){
                    e.preventDefault();
                    openMediaPicker('#pia-candidates-badge-id', 'input[name="' + piaCandidatesBadgeField + '"]', '#pia-candidates-badge-preview');
                });

                $('#pia-candidates-badge-remove').on('click', function(){
                    $('#pia-candidates-badge-id').val('');
                    $('input[name="' + piaCandidatesBadgeField + '"]').val('');
                    $('#pia-candidates-badge-preview').html('');
                });
            });"
        );
    }

    public function enqueue_frontend_assets(): void {
        wp_register_style(
            'pia-candidate-styles',
            false,
            [],
            '0.2.0'
        );
        wp_add_inline_style(
            'pia-candidate-styles',
            '.pia-candidate-grid{display:grid;gap:24px;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));}'
            . '.pia-candidate-card{border:1px solid #e5e5e5;padding:16px;text-align:center;border-radius:16px;background:#fff;box-shadow:0 4px 12px rgba(0,0,0,0.06);} '
            . '.pia-candidate-portrait{position:relative;margin-bottom:12px;} '
            . '.pia-candidate-portrait img{border-radius:16px;width:100%;height:auto;display:block;} '
            . '.pia-candidate-badge{position:absolute;left:50%;transform:translateX(-50%);bottom:-12px;background:#fff;padding:6px 10px;border-radius:999px;box-shadow:0 2px 8px rgba(0,0,0,0.15);} '
            . '.pia-candidate-card h3{margin:24px 0 4px;font-size:18px;} '
            . '.pia-candidate-card p{margin:0 0 12px;color:#666;} '
            . '.pia-candidate-buttons{display:flex;flex-direction:column;gap:8px;margin-top:12px;} '
            . '.pia-candidate-buttons a{display:inline-block;padding:10px 14px;background:#1e4b8f;color:#fff;text-decoration:none;border-radius:999px;} '
            . '.pia-candidate-tag{display:inline-block;margin-top:8px;padding:4px 10px;border-radius:999px;background:#f2f4f8;color:#2c3e50;font-size:12px;} '
            . '.pia-candidate-featured{display:inline-block;margin-top:8px;padding:4px 10px;border-radius:999px;background:#fef3c7;color:#92400e;font-size:12px;} '
        );
        wp_enqueue_style('pia-candidate-styles');
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
    }

    public function sanitize_options(array $options): array {
        return [
            'data_source_type' => isset($options['data_source_type']) ? sanitize_text_field($options['data_source_type']) : 'custom_json',
            'data_source_url' => isset($options['data_source_url']) ? esc_url_raw($options['data_source_url']) : '',
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
            <option value="custom_json" <?php selected($value, 'custom_json'); ?>>Custom JSON (URL or Inline)</option>
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

    public function handle_import(): void {
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions.');
        }
        if (!isset($_POST['pia_candidates_import_nonce']) || !wp_verify_nonce($_POST['pia_candidates_import_nonce'], 'pia_candidates_import')) {
            wp_die('Invalid request.');
        }

        $data = $this->get_import_data();
        if (empty($data)) {
            $this->redirect_with_notice('No data found to import. Existing candidates were not changed.');
        }

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

            $post_id = $this->get_post_id_by_external_id($external_id, $name);
            $post_data = [
                'post_type' => self::POST_TYPE,
                'post_title' => $name,
                'post_content' => isset($candidate['bio']) ? wp_kses_post($candidate['bio']) : '',
                'post_excerpt' => isset($candidate['summary']) ? sanitize_text_field($candidate['summary']) : '',
                'post_status' => 'publish',
            ];

            if ($post_id) {
                $post_data['ID'] = $post_id;
                wp_update_post($post_data);
            } else {
                $post_id = wp_insert_post($post_data);
            }

            if (!$post_id || is_wp_error($post_id)) {
                continue;
            }

            $this->update_candidate_meta($post_id, $candidate, $external_id);
        }

        $this->redirect_with_notice('Import completed.');
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

    private function get_custom_candidates(array $options): array {
        $data = [];
        $error = '';

        if (!empty($options['data_source_url'])) {
            $response = wp_remote_get($options['data_source_url'], ['timeout' => 20]);
            if (is_wp_error($response)) {
                $error = 'Custom JSON URL request failed.';
            } else {
                $body = wp_remote_retrieve_body($response);
                $data = $this->decode_json($body);
            }
        }

        if (empty($data) && !empty($options['data_source_json'])) {
            $data = $this->decode_json($options['data_source_json']);
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
            return ['data' => $json, 'error' => ''];
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

    private function update_candidate_meta(int $post_id, array $candidate, string $external_id): void {
        if ($external_id) {
            update_post_meta($post_id, 'pia_candidate_external_id', $external_id);
        }

        $meta_map = [
            'state' => 'pia_candidate_state',
            'county' => 'pia_candidate_county',
            'district' => 'pia_candidate_district',
            'office' => 'pia_candidate_office',
            'website' => 'pia_candidate_website',
            'video_url' => 'pia_candidate_video_url',
            'portrait_url' => 'pia_candidate_portrait_url',
        ];

        foreach ($meta_map as $source => $target) {
            if (isset($candidate[$source])) {
                $value = $candidate[$source];
                if ($target === 'pia_candidate_website' || $target === 'pia_candidate_video_url' || $target === 'pia_candidate_portrait_url') {
                    update_post_meta($post_id, $target, esc_url_raw($value));
                } else {
                    update_post_meta($post_id, $target, sanitize_text_field($value));
                }
            }
        }

        $featured = !empty($candidate['featured']) ? 1 : 0;
        $approved = !empty($candidate['approved']) ? 1 : 0;
        update_post_meta($post_id, 'pia_candidate_featured', $featured);
        update_post_meta($post_id, 'pia_candidate_approved', $approved);

        if (!empty($candidate['buttons']) && is_array($candidate['buttons'])) {
            for ($i = 1; $i <= 3; $i++) {
                $button = $candidate['buttons'][$i - 1] ?? [];
                $label = isset($button['label']) ? sanitize_text_field($button['label']) : '';
                $url = isset($button['url']) ? esc_url_raw($button['url']) : '';
                update_post_meta($post_id, "pia_candidate_button_{$i}_label", $label);
                update_post_meta($post_id, "pia_candidate_button_{$i}_url", $url);
            }
        }

        if (!empty($candidate['category'])) {
            wp_set_object_terms($post_id, (array) $candidate['category'], self::TAXONOMY, false);
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

    public function render_directory_shortcode(array $atts): string {
        $options = $this->get_options();
        $atts = shortcode_atts([
            'per_page' => 12,
            'category' => '',
            'state' => $options['default_state'],
            'county' => $options['default_county'],
            'district' => $options['default_district'],
            'featured' => '',
            'approved' => '',
        ], $atts, 'pia_candidate_directory');

        $meta_query = ['relation' => 'AND'];
        if (!empty($atts['state'])) {
            $meta_query[] = [
                'key' => 'pia_candidate_state',
                'value' => sanitize_text_field($atts['state']),
                'compare' => 'LIKE',
            ];
        }
        if (!empty($atts['county'])) {
            $meta_query[] = [
                'key' => 'pia_candidate_county',
                'value' => sanitize_text_field($atts['county']),
                'compare' => 'LIKE',
            ];
        }
        if (!empty($atts['district'])) {
            $meta_query[] = [
                'key' => 'pia_candidate_district',
                'value' => sanitize_text_field($atts['district']),
                'compare' => 'LIKE',
            ];
        }
        if ($atts['featured'] !== '') {
            $meta_query[] = [
                'key' => 'pia_candidate_featured',
                'value' => (int) (bool) $atts['featured'],
                'compare' => '=',
            ];
        }
        if ($atts['approved'] !== '') {
            $meta_query[] = [
                'key' => 'pia_candidate_approved',
                'value' => (int) (bool) $atts['approved'],
                'compare' => '=',
            ];
        }

        $query_args = [
            'post_type' => self::POST_TYPE,
            'posts_per_page' => (int) $atts['per_page'],
            'meta_query' => $meta_query,
            'orderby' => [
                'meta_value_num' => 'DESC',
                'title' => 'ASC',
            ],
            'meta_key' => 'pia_candidate_featured',
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

        $query = new WP_Query($query_args);

        if (!$query->have_posts()) {
            return '<p>No candidates found.</p>';
        }

        $badge_url = $this->get_badge_image();

        ob_start();
        echo '<div class="pia-candidate-grid">';
        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();
            $state = (string) get_post_meta($post_id, 'pia_candidate_state', true);
            $county = (string) get_post_meta($post_id, 'pia_candidate_county', true);
            $district = (string) get_post_meta($post_id, 'pia_candidate_district', true);
            $office = (string) get_post_meta($post_id, 'pia_candidate_office', true);
            $approved = (bool) get_post_meta($post_id, 'pia_candidate_approved', true);
            $featured = (bool) get_post_meta($post_id, 'pia_candidate_featured', true);

            echo '<article class="pia-candidate-card">';
            echo $this->get_candidate_portrait_html($post_id, $badge_url, $approved, 'medium');
            echo '<h3>' . esc_html(get_the_title()) . '</h3>';
            if ($office) {
                echo '<p>' . esc_html($office) . '</p>';
            }
            if ($state || $county || $district) {
                $parts = array_filter([$state, $county, $district]);
                $location = implode(' • ', $parts);
                echo '<span class="pia-candidate-tag">' . esc_html($location) . '</span>';
            }
            if ($featured) {
                echo '<span class="pia-candidate-featured">Featured</span>';
            }
            echo '<div class="pia-candidate-buttons">';
            echo '<a href="' . esc_url(get_permalink()) . '">Candidate Profile</a>';
            $website = (string) get_post_meta($post_id, 'pia_candidate_website', true);
            if ($website) {
                echo '<a href="' . esc_url($website) . '" target="_blank" rel="noopener">Website</a>';
            }
            echo '</div>';
            echo '</article>';
        }
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

        ob_start();
        echo '<div class="pia-candidate-profile">';
        echo $this->get_candidate_portrait_html($post_id, $badge_url, $approved, 'large');
        echo '<h2>' . esc_html(get_the_title($post_id)) . '</h2>';
        if ($office) {
            echo '<p>' . esc_html($office) . '</p>';
        }
        if ($state || $county || $district) {
            $parts = array_filter([$state, $county, $district]);
            $location = implode(' • ', $parts);
            echo '<p class="pia-candidate-tag">' . esc_html($location) . '</p>';
        }
        echo apply_filters('the_content', get_post_field('post_content', $post_id));

        if ($video_url) {
            $embed = wp_oembed_get($video_url);
            if ($embed) {
                echo '<div class="pia-candidate-video">' . $embed . '</div>';
            } else {
                echo '<p><a href="' . esc_url($video_url) . '">Watch video</a></p>';
            }
        }

        echo '<div class="pia-candidate-buttons">';
        for ($i = 1; $i <= 3; $i++) {
            $label = (string) get_post_meta($post_id, "pia_candidate_button_{$i}_label", true);
            $url = (string) get_post_meta($post_id, "pia_candidate_button_{$i}_url", true);
            if ($label && $url) {
                echo '<a href="' . esc_url($url) . '">' . esc_html($label) . '</a>';
            }
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
        } elseif ($portrait_url) {
            $image_html = '<img src="' . esc_url($portrait_url) . '" alt="" />';
        } else {
            $thumbnail_id = get_post_thumbnail_id($post_id);
            if ($thumbnail_id) {
                $image_html = wp_get_attachment_image($thumbnail_id, $size);
            }
        }

        if (!$image_html) {
            return '';
        }

        $output = '<div class="pia-candidate-portrait">' . $image_html;
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
