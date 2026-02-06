<?php
/**
 * Plugin Name: PIA Candidates (MU)
 * Description: Per-site candidate profiles with directory and profile shortcodes for the PIA multisite.
 * Version: 0.1.0
 * Author: PIA
 */

if (!defined('ABSPATH')) {
    exit;
}

final class PIA_Candidates_MU {
    const POST_TYPE = 'pia_candidate';
    const TAXONOMY = 'pia_candidate_category';

    public function __construct() {
        add_action('init', [$this, 'register_post_type']);
        add_action('init', [$this, 'register_taxonomy']);
        add_action('init', [$this, 'register_meta']);
        add_action('add_meta_boxes', [$this, 'register_meta_boxes']);
        add_action('save_post_' . self::POST_TYPE, [$this, 'save_meta'], 10, 2);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);

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
        $meta_args = [
            'type' => 'string',
            'single' => true,
            'show_in_rest' => true,
            'sanitize_callback' => 'sanitize_text_field',
        ];

        register_post_meta(self::POST_TYPE, 'pia_candidate_video_url', $meta_args);
        register_post_meta(self::POST_TYPE, 'pia_candidate_portrait_id', [
            'type' => 'integer',
            'single' => true,
            'show_in_rest' => true,
            'sanitize_callback' => 'absint',
        ]);

        for ($i = 1; $i <= 3; $i++) {
            register_post_meta(self::POST_TYPE, "pia_candidate_button_{$i}_label", $meta_args);
            register_post_meta(self::POST_TYPE, "pia_candidate_button_{$i}_url", [
                'type' => 'string',
                'single' => true,
                'show_in_rest' => true,
                'sanitize_callback' => 'esc_url_raw',
            ]);
        }
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
        $video_url = (string) get_post_meta($post->ID, 'pia_candidate_video_url', true);
        $portrait_url = $portrait_id ? wp_get_attachment_image_url($portrait_id, 'medium') : '';
        ?>
        <p>
            <label for="pia-candidate-video-url"><strong>Video URL</strong></label><br />
            <input type="url" id="pia-candidate-video-url" name="pia_candidate_video_url" value="<?php echo esc_attr($video_url); ?>" class="widefat" />
        </p>
        <p>
            <label><strong>Portrait Image</strong></label><br />
            <input type="hidden" id="pia-candidate-portrait-id" name="pia_candidate_portrait_id" value="<?php echo esc_attr($portrait_id); ?>" />
            <button type="button" class="button" id="pia-candidate-portrait-select">Select Portrait</button>
            <button type="button" class="button" id="pia-candidate-portrait-remove">Remove</button>
        </p>
        <div id="pia-candidate-portrait-preview" style="margin-bottom:16px;">
            <?php if ($portrait_url) : ?>
                <img src="<?php echo esc_url($portrait_url); ?>" alt="" style="max-width:200px;height:auto;" />
            <?php endif; ?>
        </div>
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

        $video_url = isset($_POST['pia_candidate_video_url']) ? sanitize_text_field(wp_unslash($_POST['pia_candidate_video_url'])) : '';
        update_post_meta($post_id, 'pia_candidate_video_url', $video_url);

        $portrait_id = isset($_POST['pia_candidate_portrait_id']) ? absint($_POST['pia_candidate_portrait_id']) : 0;
        update_post_meta($post_id, 'pia_candidate_portrait_id', $portrait_id);

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
        if ($hook !== 'post-new.php' && $hook !== 'post.php') {
            return;
        }
        $screen = get_current_screen();
        if (!$screen || $screen->post_type !== self::POST_TYPE) {
            return;
        }
        wp_enqueue_media();
        wp_add_inline_script(
            'jquery',
            "jQuery(function($){
                var frame;
                $('#pia-candidate-portrait-select').on('click', function(e){
                    e.preventDefault();
                    if(frame){ frame.open(); return; }
                    frame = wp.media({ title: 'Select Portrait', button: { text: 'Use this image' }, multiple: false });
                    frame.on('select', function(){
                        var attachment = frame.state().get('selection').first().toJSON();
                        $('#pia-candidate-portrait-id').val(attachment.id);
                        $('#pia-candidate-portrait-preview').html('<img src="' + attachment.sizes.medium.url + '" style="max-width:200px;height:auto;" />');
                    });
                    frame.open();
                });
                $('#pia-candidate-portrait-remove').on('click', function(){
                    $('#pia-candidate-portrait-id').val('');
                    $('#pia-candidate-portrait-preview').html('');
                });
            });"
        );
    }

    public function enqueue_frontend_assets(): void {
        wp_register_style(
            'pia-candidate-styles',
            false,
            [],
            '0.1.0'
        );
        wp_add_inline_style(
            'pia-candidate-styles',
            '.pia-candidate-grid{display:grid;gap:24px;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));}'
            . '.pia-candidate-card{border:1px solid #e5e5e5;padding:16px;text-align:center;border-radius:8px;}'
            . '.pia-candidate-card img{border-radius:8px;}'
            . '.pia-candidate-buttons{display:flex;flex-direction:column;gap:8px;margin-top:12px;}'
            . '.pia-candidate-buttons a{display:inline-block;padding:10px 14px;background:#1e4b8f;color:#fff;text-decoration:none;border-radius:4px;}'
        );
        wp_enqueue_style('pia-candidate-styles');
    }

    public function render_directory_shortcode(array $atts): string {
        $atts = shortcode_atts([
            'per_page' => 12,
            'category' => '',
        ], $atts, 'pia_candidate_directory');

        $query_args = [
            'post_type' => self::POST_TYPE,
            'posts_per_page' => (int) $atts['per_page'],
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

        ob_start();
        echo '<div class="pia-candidate-grid">';
        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();
            $portrait_id = (int) get_post_meta($post_id, 'pia_candidate_portrait_id', true);
            $image_id = $portrait_id ?: get_post_thumbnail_id($post_id);
            $image = $image_id ? wp_get_attachment_image($image_id, 'medium') : '';
            echo '<article class="pia-candidate-card">';
            if ($image) {
                echo $image;
            }
            echo '<h3>' . esc_html(get_the_title()) . '</h3>';
            echo '<p>' . esc_html(get_the_excerpt()) . '</p>';
            echo '<a href="' . esc_url(get_permalink()) . '">View Profile</a>';
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

        $portrait_id = (int) get_post_meta($post_id, 'pia_candidate_portrait_id', true);
        $image_id = $portrait_id ?: get_post_thumbnail_id($post_id);
        $image = $image_id ? wp_get_attachment_image($image_id, 'large') : '';
        $video_url = (string) get_post_meta($post_id, 'pia_candidate_video_url', true);

        ob_start();
        echo '<div class="pia-candidate-profile">';
        if ($image) {
            echo '<div class="pia-candidate-portrait">' . $image . '</div>';
        }
        echo '<h2>' . esc_html(get_the_title($post_id)) . '</h2>';
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
