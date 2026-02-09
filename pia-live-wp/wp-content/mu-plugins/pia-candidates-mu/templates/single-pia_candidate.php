<?php
if (!defined('ABSPATH')) {
    exit;
}

get_header();
?>
<main class="pia-candidate-single">
    <?php while (have_posts()) : the_post(); ?>
        <article <?php post_class('pia-candidate-entry'); ?>>
            <?php echo do_shortcode('[pia_candidate_profile]'); ?>
        </article>
    <?php endwhile; ?>
</main>
<?php
get_footer();
