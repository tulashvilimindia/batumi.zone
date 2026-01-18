<?php
/**
 * Main template file
 *
 * @package Batumi_Theme
 */

get_header();
?>

<main id="main" class="site-main">
    <div class="container">
        <?php
        if (have_posts()) :
            ?>
            <div class="posts-grid">
                <?php
                while (have_posts()) :
                    the_post();
                    ?>
                    <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                        <?php if (has_post_thumbnail()) : ?>
                            <div class="entry-thumbnail">
                                <a href="<?php the_permalink(); ?>">
                                    <?php the_post_thumbnail('service-thumbnail'); ?>
                                </a>
                            </div>
                        <?php endif; ?>

                        <header class="entry-header">
                            <?php
                            the_title(
                                '<h2 class="entry-title"><a href="' . esc_url(get_permalink()) . '">',
                                '</a></h2>'
                            );
                            ?>

                            <div class="entry-meta">
                                <span class="posted-on">
                                    <time datetime="<?php echo esc_attr(get_the_date('c')); ?>">
                                        <?php echo esc_html(get_the_date()); ?>
                                    </time>
                                </span>
                            </div>
                        </header>

                        <div class="entry-content">
                            <?php the_excerpt(); ?>
                        </div>

                        <footer class="entry-footer">
                            <a href="<?php the_permalink(); ?>" class="btn">
                                <?php _e('Read More', 'batumi-theme'); ?>
                            </a>
                        </footer>
                    </article>
                    <?php
                endwhile;
                ?>
            </div><!-- .posts-grid -->

            <?php
            // Pagination
            the_posts_pagination(array(
                'mid_size'  => 2,
                'prev_text' => __('&laquo; Previous', 'batumi-theme'),
                'next_text' => __('Next &raquo;', 'batumi-theme'),
            ));

        else :
            ?>
            <div class="no-results">
                <h1><?php _e('Batumi.zone', 'batumi-theme'); ?></h1>
                <p><?php _e('Find services in Batumi', 'batumi-theme'); ?></p>
                <p class="mt-2">
                    <a href="<?php echo esc_url(home_url('/services')); ?>" class="btn">
                        <?php _e('Browse Services', 'batumi-theme'); ?>
                    </a>
                </p>
            </div>
            <?php
        endif;
        ?>
    </div><!-- .container -->
</main><!-- #main -->

<?php
get_footer();
