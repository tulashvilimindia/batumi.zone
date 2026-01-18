    </div><!-- #content -->

    <footer id="colophon" class="site-footer">
        <div class="container">
            <div class="footer-content">
                <!-- Footer Navigation -->
                <?php if (has_nav_menu('footer')) : ?>
                    <nav class="footer-navigation" role="navigation" aria-label="<?php esc_attr_e('Footer Menu', 'batumi-theme'); ?>">
                        <?php
                        wp_nav_menu(array(
                            'theme_location' => 'footer',
                            'menu_id'        => 'footer-menu',
                            'menu_class'     => 'footer-nav-menu',
                            'container'      => false,
                            'depth'          => 1,
                            'fallback_cb'    => false,
                        ));
                        ?>
                    </nav>
                <?php else : ?>
                    <nav class="footer-navigation">
                        <ul class="footer-nav-menu">
                            <li><a href="<?php echo esc_url(home_url('/about')); ?>"><?php _e('About', 'batumi-theme'); ?></a></li>
                            <li><a href="<?php echo esc_url(home_url('/contact')); ?>"><?php _e('Contact', 'batumi-theme'); ?></a></li>
                            <li><a href="<?php echo esc_url(home_url('/privacy')); ?>"><?php _e('Privacy Policy', 'batumi-theme'); ?></a></li>
                            <li><a href="<?php echo esc_url(home_url('/terms')); ?>"><?php _e('Terms of Service', 'batumi-theme'); ?></a></li>
                        </ul>
                    </nav>
                <?php endif; ?>

                <!-- Footer Widgets Area -->
                <?php if (is_active_sidebar('footer-widgets')) : ?>
                    <div class="footer-widgets">
                        <?php dynamic_sidebar('footer-widgets'); ?>
                    </div>
                <?php endif; ?>

                <!-- Site Info -->
                <div class="site-info">
                    <?php
                    printf(
                        /* translators: 1: Year, 2: Site name */
                        esc_html__('&copy; %1$s %2$s. All rights reserved.', 'batumi-theme'),
                        date('Y'),
                        get_bloginfo('name')
                    );
                    ?>
                    <span class="sep"> | </span>
                    <?php
                    printf(
                        /* translators: %s: WordPress */
                        esc_html__('Powered by %s', 'batumi-theme'),
                        '<a href="https://wordpress.org/" target="_blank" rel="noopener">WordPress</a>'
                    );
                    ?>
            <!-- Footer Desktop Ad -->
            <div class="ad-container" data-placement="footer_desktop" data-api-url="/wp-json/batumizone/v1/ads/placement/footer_desktop">
                <div class="ad-label">Advertisement</div>
                <div class="ad-loading">Loading...</div>
            </div>

            <!-- Footer Mobile Ad -->
            <div class="ad-container" data-placement="footer_mobile" data-api-url="/wp-json/batumizone/v1/ads/placement/footer_mobile">
                <div class="ad-label">Advertisement</div>
                <div class="ad-loading">Loading...</div>
            </div>

                </div><!-- .site-info -->
            </div><!-- .footer-content -->
        </div><!-- .container -->
    </footer><!-- #colophon -->
</div><!-- #page -->

<?php wp_footer(); ?>

</body>
</html>
