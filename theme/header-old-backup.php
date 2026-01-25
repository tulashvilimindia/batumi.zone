<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5">
    <meta name="description" content="<?php bloginfo('description'); ?>">
    <link rel="profile" href="https://gmpg.org/xfn/11">
    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<div id="page" class="site">
    <a class="skip-link screen-reader-text" href="#main"><?php _e('Skip to content', 'batumi-theme'); ?></a>

    <header id="masthead" class="site-header">
        <div class="container">
            <div class="header-container">
                <!-- Site Branding -->
                <div class="site-branding">
                    <h1 class="site-title">
                        <a href="<?php echo esc_url(home_url('/')); ?>" rel="home">
                            <?php bloginfo('name'); ?>
                        </a>
                    </h1>
                    <?php
                    $description = get_bloginfo('description', 'display');
                    if ($description || is_customize_preview()) :
                        ?>
                        <p class="site-description"><?php echo $description; ?></p>
                    <?php endif; ?>
                </div>

                <!-- Primary Navigation -->
                <nav id="site-navigation" class="main-navigation" role="navigation" aria-label="<?php esc_attr_e('Primary Menu', 'batumi-theme'); ?>">
                    <button class="menu-toggle" aria-controls="primary-menu" aria-expanded="false">
                        <span class="menu-toggle-text"><?php _e('Menu', 'batumi-theme'); ?></span>
                        <span class="menu-toggle-icon">☰</span>
                    </button>
                    <?php
                    if (has_nav_menu('primary')) {
                        wp_nav_menu(array(
                            'theme_location' => 'primary',
                            'menu_id'        => 'primary-menu',
                            'menu_class'     => 'nav-menu',
                            'container'      => false,
                            'fallback_cb'    => false,
                        ));
                    } else {
                        // Default menu if none is set
                        echo '<ul id="primary-menu" class="nav-menu">';
                        echo '<li><a href="' . esc_url(home_url('/')) . '">' . __('Home', 'batumi-theme') . '</a></li>';
                        echo '<li><a href="' . esc_url(home_url('/services')) . '">' . __('Services', 'batumi-theme') . '</a></li>';
                        echo '</ul>';
                    }
                    ?>
                </nav>

                <!-- Favorites Link -->
                <a href="<?php echo esc_url(home_url('/favorites/')); ?>" class="favorites-link nav-link" title="Favorites">
                    <span>❤️</span>
                    <span class="favorites-badge" style="display: none;">0</span>
                </a>

                <!-- Language Switcher (Polylang) -->
                <?php batumi_language_switcher(); ?>
            </div><!-- .header-container -->
        </div><!-- .container -->
    </header><!-- #masthead -->

    <div id="content" class="site-content">
