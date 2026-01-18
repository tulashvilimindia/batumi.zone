<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <meta name="description" content="<?php bloginfo('description'); ?>">
    <link rel="profile" href="https://gmpg.org/xfn/11">
    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<div id="page" class="site">
<!-- Sidebar Ads (Desktop Only) -->
<div class="ad-container" data-placement="sidebar_left" data-api-url="/wp-json/batumizone/v1/ads/placement/sidebar_left">
    <div class="ad-label">Ad</div>
    <div class="ad-loading">...</div>
</div>
<div class="ad-container" data-placement="sidebar_right" data-api-url="/wp-json/batumizone/v1/ads/placement/sidebar_right">
    <div class="ad-label">Ad</div>
    <div class="ad-loading">...</div>
</div>

    <a class="skip-link screen-reader-text" href="#main"><?php _e('Skip to content', 'batumi-theme'); ?></a>

    <!-- Glassmorphism Header -->
    <header id="masthead" class="fancy-header">
        <div class="fancy-header-inner">
            <!-- Logo/Brand -->
            <div class="header-logo">
                <a href="<?php echo esc_url(home_url('/')); ?>" rel="home">
                    <?php bloginfo('name'); ?>
                </a>
            </div>

            <!-- Search Bar -->
            <div class="header-search">
                <form role="search" method="get" class="search-form" action="<?php echo esc_url(home_url('/')); ?>">
                    <input type="search"
                           class="search-input"
                           placeholder="<?php esc_attr_e('Search services...', 'batumi-theme'); ?>"
                           value="<?php echo get_search_query(); ?>"
                           name="s" />
                    <button type="submit" class="search-button">
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                            <path d="M9 17A8 8 0 1 0 9 1a8 8 0 0 0 0 16zM19 19l-4.35-4.35" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                </form>
            </div>

            <!-- Header Actions -->
            <div class="header-actions">
                <!-- Filter Toggle Button -->
                <button class="header-action-btn filter-toggle" aria-label="Filters" title="Filters">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                        <path d="M22 3H2l8 9.46V19l4 2v-8.54L22 3z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>

                <!-- Favorites Link -->
                <a href="<?php echo esc_url(home_url('/favorites/')); ?>" class="header-action-btn favorites-link" aria-label="Favorites" title="Favorites">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                    </svg>
                    <span class="favorites-badge" style="display: none;">0</span>
                </a>

                <!-- Dark/Light Mode Toggle -->
                <button class="header-action-btn theme-toggle" aria-label="Toggle Theme" title="Toggle Dark/Light Mode">
                    <svg class="sun-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="5"/>
                        <path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/>
                    </svg>
                    <svg class="moon-icon" style="display: none;" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
                    </svg>
                </button>

                <!-- Language Switcher (Show Only Current Flag) -->
                <?php if (function_exists('pll_the_languages')) :
                    $languages = pll_the_languages(array(
                        'show_flags' => 1,
                        'show_names' => 0,
                        'hide_if_empty' => 0,
                        'echo' => 0,
                        'raw' => 1
                    ));

                    if (!empty($languages)) :
                        $current_lang = null;
                        $other_langs = [];

                        foreach ($languages as $lang) {
                            if ($lang['current_lang']) {
                                $current_lang = $lang;
                            } else {
                                $other_langs[] = $lang;
                            }
                        }

                        // Flag emoji mapping
                        $flag_emojis = array(
                            'ka' => '🇬🇪',
                            'ge' => '🇬🇪',
                            'ru' => '🇷🇺',
                            'en' => '🇬🇧',
                            'us' => '🇺🇸'
                        );
                        ?>
                        <div class="header-lang-switcher">
                            <button class="lang-current-flag" aria-label="Language" title="<?php echo esc_attr($current_lang['name']); ?>">
                                <?php if (!empty($current_lang['flag'])) : ?>
                                    <img src="<?php echo esc_url($current_lang['flag']); ?>"
                                         alt="<?php echo esc_attr($current_lang['name']); ?>"
                                         onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                                    <span class="flag-emoji" style="display: none;">
                                        <?php echo isset($flag_emojis[$current_lang['slug']]) ? $flag_emojis[$current_lang['slug']] : '🌐'; ?>
                                    </span>
                                <?php else : ?>
                                    <span class="flag-emoji">
                                        <?php echo isset($flag_emojis[$current_lang['slug']]) ? $flag_emojis[$current_lang['slug']] : '🌐'; ?>
                                    </span>
                                <?php endif; ?>
                                <svg class="dropdown-arrow" width="12" height="12" viewBox="0 0 12 12" fill="currentColor">
                                    <path d="M6 9L1 4h10z"/>
                                </svg>
                            </button>

                            <div class="lang-dropdown">
                                <?php foreach ($other_langs as $lang) : ?>
                                    <a href="<?php echo esc_url($lang['url']); ?>"
                                       class="lang-dropdown-item"
                                       hreflang="<?php echo esc_attr($lang['slug']); ?>"
                                       title="<?php echo esc_attr($lang['name']); ?>">
                                        <?php if (!empty($lang['flag'])) : ?>
                                            <img src="<?php echo esc_url($lang['flag']); ?>"
                                                 alt="<?php echo esc_attr($lang['name']); ?>"
                                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='inline';">
                                            <span class="flag-emoji" style="display: none;">
                                                <?php echo isset($flag_emojis[$lang['slug']]) ? $flag_emojis[$lang['slug']] : '🌐'; ?>
                                            </span>
                                        <?php else : ?>
                                            <span class="flag-emoji">
                                                <?php echo isset($flag_emojis[$lang['slug']]) ? $flag_emojis[$lang['slug']] : '🌐'; ?>
                                            </span>
                                        <?php endif; ?>
                                        <span class="lang-name"><?php echo esc_html($lang['name']); ?></span>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <!-- User Menu (if logged in) -->
                <?php if (is_user_logged_in()) : ?>
                    <div class="header-user-menu">
                        <button class="header-action-btn user-menu-toggle" aria-label="User Menu">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2M12 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8z"/>
                            </svg>
                        </button>
                        <div class="user-dropdown">
                            <a href="<?php echo esc_url(home_url('/profile/')); ?>"><?php _e('Profile', 'batumi-theme'); ?></a>
                            <a href="<?php echo esc_url(home_url('/my-listings/')); ?>"><?php _e('My Listings', 'batumi-theme'); ?></a>
                            <a href="<?php echo esc_url(home_url('/create-service/')); ?>"><?php _e('Add Listing', 'batumi-theme'); ?></a>
                            <a href="<?php echo wp_logout_url(home_url('/')); ?>"><?php _e('Logout', 'batumi-theme'); ?></a>
                        </div>
                    </div>
                <?php else : ?>
                    <a href="<?php echo esc_url(home_url('/login/')); ?>" class="header-action-btn" title="Login">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4M10 17l5-5-5-5M15 12H3"/>
                        </svg>
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Expandable Filters Panel -->
        <div class="filters-panel">
            <div class="filters-panel-inner">
                <div class="filters-grid">
                    <!-- Category Filter -->
                    <div class="filter-group">
                        <label><?php _e('Category', 'batumi-theme'); ?></label>
                        <select name="category" class="filter-select" id="filter-category">
                            <option value=""><?php _e('All Categories', 'batumi-theme'); ?></option>
                            <?php
                            $directions = get_terms(array(
                                'taxonomy' => 'service_category',
                                'hide_empty' => false,
                            ));
                            if (!is_wp_error($directions) && !empty($directions)) {
                                foreach ($directions as $direction) {
                                    $selected = (isset($_GET['category']) && $_GET['category'] == $direction->slug) ? 'selected' : '';
                                    echo '<option value="' . esc_attr($direction->slug) . '" ' . $selected . '>' . esc_html($direction->name) . '</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>

                    <!-- Area Filter -->
                    <div class="filter-group">
                        <label><?php _e('Area', 'batumi-theme'); ?></label>
                        <select name="area" class="filter-select" id="filter-area">
                            <option value=""><?php _e('All Areas', 'batumi-theme'); ?></option>
                            <?php
                            $areas = get_terms(array(
                                'taxonomy' => 'coverage_area',
                                'hide_empty' => false,
                            ));
                            if (!is_wp_error($areas) && !empty($areas)) {
                                foreach ($areas as $area) {
                                    $selected = (isset($_GET['area']) && $_GET['area'] == $area->slug) ? 'selected' : '';
                                    echo '<option value="' . esc_attr($area->slug) . '" ' . $selected . '>' . esc_html($area->name) . '</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>

                    <!-- Price Range -->
                    <div class="filter-group">
                        <label><?php _e('Min Price', 'batumi-theme'); ?></label>
                        <input type="number" name="price_min" class="filter-input" id="filter-price-min" placeholder="0" value="<?php echo isset($_GET['price_min']) ? esc_attr($_GET['price_min']) : ''; ?>">
                    </div>

                    <div class="filter-group">
                        <label><?php _e('Max Price', 'batumi-theme'); ?></label>
                        <input type="number" name="price_max" class="filter-input" id="filter-price-max" placeholder="1000" value="<?php echo isset($_GET['price_max']) ? esc_attr($_GET['price_max']) : ''; ?>">
                    </div>

                    <!-- Sort -->
                    <div class="filter-group">
                        <label><?php _e('Sort By', 'batumi-theme'); ?></label>
                        <select name="sort" class="filter-select" id="filter-sort">
                            <option value="date" <?php selected(isset($_GET['sort']) ? $_GET['sort'] : 'date', 'date'); ?>><?php _e('Newest First', 'batumi-theme'); ?></option>
                            <option value="price_low" <?php selected(isset($_GET['sort']) ? $_GET['sort'] : '', 'price_low'); ?>><?php _e('Price: Low to High', 'batumi-theme'); ?></option>
                            <option value="price_high" <?php selected(isset($_GET['sort']) ? $_GET['sort'] : '', 'price_high'); ?>><?php _e('Price: High to Low', 'batumi-theme'); ?></option>
                            <option value="random" <?php selected(isset($_GET['sort']) ? $_GET['sort'] : '', 'random'); ?>><?php _e('Random', 'batumi-theme'); ?></option>
                        </select>
                    </div>
                </div>

                <div class="filters-actions">
                    <button type="button" class="btn btn-secondary btn-sm" id="clear-filters"><?php _e('Clear All', 'batumi-theme'); ?></button>
                    <button type="button" class="btn btn-primary btn-sm" id="apply-filters"><?php _e('Apply Filters', 'batumi-theme'); ?></button>
                </div>
            </div>
        </div>
    </header><!-- #masthead -->

    <div id="content" class="site-content fancy-layout">
