<?php
/**
 * Main template fallback.
 *
 * @package OHTheme
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

global $post;

get_header();
?>
<main id="primary" class="oh-main container">
    <?php if (have_posts()) : ?>
        <?php
        while (have_posts()) :
            the_post();
            ?>
            <article id="post-<?php the_ID(); ?>" <?php post_class('oh-generic-entry'); ?>>
                <header class="oh-generic-entry__header">
                    <h1 class="oh-generic-entry__title"><?php the_title(); ?></h1>
                </header>
                <div class="oh-generic-entry__content">
                    <?php the_content(); ?>
                </div>
            </article>
        <?php endwhile; ?>
    <?php else : ?>
        <section class="oh-generic-entry oh-generic-entry--empty">
            <p><?php echo esc_html__('No content found.', \OHTheme\THEME_TEXT_DOMAIN); ?></p>
        </section>
    <?php endif; ?>
</main>
<?php
get_footer();
