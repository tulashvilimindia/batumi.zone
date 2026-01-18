<?php
/**
 * Search Results Template
 *
 * @package Batumi_Theme
 * @version 0.3.0
 */

get_header();

// Get current language
$lang = batumi_get_current_language();

// Translations
$translations = array(
    'ge' => array(
        'search_results' => 'ძებნის შედეგები',
        'search_for' => 'ძებნა:',
        'results_found' => 'ნაპოვნია %d შედეგი',
        'no_results' => 'შედეგები არ მოიძებნა',
        'no_results_text' => 'სამწუხაროდ, თქვენი ძებნის მოთხოვნით არაფერი მოიძებნა. სცადეთ სხვა საკვანძო სიტყვები.',
        'search_tips' => 'ძებნის რჩევები:',
        'tip_1' => 'შეამოწმეთ მართლწერა',
        'tip_2' => 'სცადეთ უფრო ზოგადი საკვანძო სიტყვები',
        'tip_3' => 'სცადეთ ნაკლები საკვანძო სიტყვა',
        'browse_categories' => 'დაათვალიერეთ კატეგორიები',
        'all_services' => 'ყველა სერვისი',
    ),
    'ru' => array(
        'search_results' => 'Результаты поиска',
        'search_for' => 'Поиск:',
        'results_found' => 'Найдено %d результатов',
        'no_results' => 'Результаты не найдены',
        'no_results_text' => 'К сожалению, по вашему запросу ничего не найдено. Попробуйте другие ключевые слова.',
        'search_tips' => 'Советы по поиску:',
        'tip_1' => 'Проверьте правописание',
        'tip_2' => 'Попробуйте более общие ключевые слова',
        'tip_3' => 'Попробуйте меньше ключевых слов',
        'browse_categories' => 'Просмотреть категории',
        'all_services' => 'Все услуги',
    ),
    'en' => array(
        'search_results' => 'Search Results',
        'search_for' => 'Search:',
        'results_found' => '%d results found',
        'no_results' => 'No Results Found',
        'no_results_text' => 'Sorry, no results were found for your search. Try different keywords.',
        'search_tips' => 'Search Tips:',
        'tip_1' => 'Check your spelling',
        'tip_2' => 'Try more general keywords',
        'tip_3' => 'Try fewer keywords',
        'browse_categories' => 'Browse Categories',
        'all_services' => 'All Services',
    ),
);

$t = $translations[$lang] ?? $translations['en'];
$search_query = get_search_query();
?>

<main id="main" class="site-main search-results-page">
    <div class="search-container">

        <header class="search-header">
            <h1 class="search-title"><?php echo esc_html($t['search_results']); ?></h1>
            <?php if ($search_query) : ?>
                <p class="search-query">
                    <?php echo esc_html($t['search_for']); ?>
                    <span class="search-term">"<?php echo esc_html($search_query); ?>"</span>
                </p>
            <?php endif; ?>
        </header>

        <?php if (have_posts()) : ?>

            <p class="results-count">
                <?php
                global $wp_query;
                printf(esc_html($t['results_found']), $wp_query->found_posts);
                ?>
            </p>

            <div id="services-grid" class="services-grid search-results-grid">
                <?php
                while (have_posts()) :
                    the_post();

                    // Use service card template for service_listing
                    if (get_post_type() === 'service_listing') {
                        get_template_part('template-parts/content', 'service-card');
                    } else {
                        // Generic result for other post types
                        ?>
                        <article id="post-<?php the_ID(); ?>" <?php post_class('search-result-item'); ?>>
                            <h2 class="result-title">
                                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                            </h2>
                            <div class="result-excerpt">
                                <?php the_excerpt(); ?>
                            </div>
                            <a href="<?php the_permalink(); ?>" class="result-link btn btn-sm">
                                <?php echo $lang === 'ru' ? 'Подробнее' : ($lang === 'en' ? 'Read More' : 'ვრცლად'); ?>
                            </a>
                        </article>
                        <?php
                    }
                endwhile;
                ?>
            </div>

            <?php
            // Pagination
            the_posts_pagination(array(
                'mid_size' => 2,
                'prev_text' => '&laquo;',
                'next_text' => '&raquo;',
            ));
            ?>

        <?php else : ?>

            <div class="no-results">
                <div class="no-results-icon">
                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <circle cx="11" cy="11" r="8"/>
                        <path d="M21 21l-4.35-4.35"/>
                        <path d="M8 8l6 6M14 8l-6 6" stroke-linecap="round"/>
                    </svg>
                </div>

                <h2 class="no-results-title"><?php echo esc_html($t['no_results']); ?></h2>
                <p class="no-results-text"><?php echo esc_html($t['no_results_text']); ?></p>

                <div class="search-tips">
                    <h3><?php echo esc_html($t['search_tips']); ?></h3>
                    <ul>
                        <li><?php echo esc_html($t['tip_1']); ?></li>
                        <li><?php echo esc_html($t['tip_2']); ?></li>
                        <li><?php echo esc_html($t['tip_3']); ?></li>
                    </ul>
                </div>

                <div class="no-results-actions">
                    <?php
                    // Get categories for browsing
                    $categories = get_terms(array(
                        'taxonomy' => 'service_direction',
                        'hide_empty' => true,
                        'number' => 6,
                    ));

                    if (!empty($categories) && !is_wp_error($categories)) :
                    ?>
                        <h3><?php echo esc_html($t['browse_categories']); ?></h3>
                        <div class="category-links">
                            <?php foreach ($categories as $category) :
                                // Get translated name
                                $cat_name = $category->name;
                                if ($lang === 'ru') {
                                    $translated = get_term_meta($category->term_id, 'name_ru', true);
                                    if ($translated) $cat_name = $translated;
                                } elseif ($lang === 'en') {
                                    $translated = get_term_meta($category->term_id, 'name_en', true);
                                    if ($translated) $cat_name = $translated;
                                } else {
                                    $translated = get_term_meta($category->term_id, 'name_ka', true);
                                    if ($translated) $cat_name = $translated;
                                }
                            ?>
                                <a href="<?php echo esc_url(home_url('/?category=' . $category->slug)); ?>" class="category-link">
                                    <?php echo esc_html($cat_name); ?>
                                    <span class="count">(<?php echo esc_html($category->count); ?>)</span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <a href="<?php echo esc_url(home_url('/')); ?>" class="btn btn-primary">
                        <?php echo esc_html($t['all_services']); ?>
                    </a>
                </div>
            </div>

        <?php endif; ?>

    </div>
</main>

<style>
/* Search Results Page Styles */
.search-results-page {
    padding: 2rem 0;
    min-height: 60vh;
}

.search-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 1.5rem;
}

.search-header {
    text-align: center;
    margin-bottom: 2rem;
}

.search-title {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    color: var(--text-primary, #1a1a1a);
}

.search-query {
    font-size: 1.125rem;
    color: var(--text-secondary, #666);
}

.search-term {
    color: var(--brand-primary, #667eea);
    font-weight: 600;
}

.results-count {
    text-align: center;
    margin-bottom: 1.5rem;
    color: var(--text-secondary, #666);
    font-size: 0.9375rem;
}

.search-results-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1.5rem;
}

/* No Results Styles */
.no-results {
    text-align: center;
    padding: 3rem 1rem;
    max-width: 600px;
    margin: 0 auto;
}

.no-results-icon {
    margin-bottom: 1.5rem;
    color: var(--text-muted, #999);
}

.no-results-icon svg {
    width: 80px;
    height: 80px;
}

.no-results-title {
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 0.75rem;
    color: var(--text-primary, #1a1a1a);
}

.no-results-text {
    color: var(--text-secondary, #666);
    margin-bottom: 2rem;
    font-size: 1.0625rem;
}

.search-tips {
    background-color: var(--bg-secondary, #f8f9fa);
    border-radius: 8px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    text-align: left;
}

.search-tips h3 {
    font-size: 1rem;
    font-weight: 600;
    margin-bottom: 0.75rem;
    color: var(--text-primary, #1a1a1a);
}

.search-tips ul {
    list-style: disc;
    margin-left: 1.25rem;
    color: var(--text-secondary, #666);
}

.search-tips li {
    margin-bottom: 0.375rem;
}

.no-results-actions h3 {
    font-size: 1rem;
    font-weight: 600;
    margin-bottom: 1rem;
    color: var(--text-primary, #1a1a1a);
}

.category-links {
    display: flex;
    flex-wrap: wrap;
    gap: 0.625rem;
    justify-content: center;
    margin-bottom: 1.5rem;
}

.category-link {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.5rem 1rem;
    background-color: var(--bg-secondary, #f0f0f0);
    border-radius: 20px;
    color: var(--text-primary, #333);
    font-size: 0.875rem;
    text-decoration: none;
    transition: all 0.2s ease;
}

.category-link:hover {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.category-link .count {
    font-size: 0.75rem;
    opacity: 0.7;
}

/* Generic search result item (for non-service posts) */
.search-result-item {
    background: var(--bg-card, white);
    border-radius: 8px;
    padding: 1.5rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.search-result-item .result-title {
    font-size: 1.125rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.search-result-item .result-title a {
    color: var(--text-primary, #1a1a1a);
    text-decoration: none;
}

.search-result-item .result-title a:hover {
    color: var(--brand-primary, #667eea);
}

.search-result-item .result-excerpt {
    color: var(--text-secondary, #666);
    font-size: 0.9375rem;
    margin-bottom: 1rem;
}

/* Dark mode for search page */
[data-theme="dark"] .search-results-page {
    background-color: var(--bg-primary, #1a1a2e);
}

[data-theme="dark"] .search-title {
    color: var(--text-primary, #e8e8e8);
}

[data-theme="dark"] .search-query {
    color: var(--text-secondary, #b0b0b0);
}

[data-theme="dark"] .no-results-title {
    color: var(--text-primary, #e8e8e8);
}

[data-theme="dark"] .no-results-text {
    color: var(--text-secondary, #b0b0b0);
}

[data-theme="dark"] .no-results-icon {
    color: var(--text-muted, #808080);
}

[data-theme="dark"] .search-tips {
    background-color: var(--bg-secondary, #16213e);
}

[data-theme="dark"] .search-tips h3 {
    color: var(--text-primary, #e8e8e8);
}

[data-theme="dark"] .search-tips ul {
    color: var(--text-secondary, #b0b0b0);
}

[data-theme="dark"] .category-link {
    background-color: var(--bg-input, #252545);
    color: var(--text-primary, #e8e8e8);
}

[data-theme="dark"] .search-result-item {
    background-color: var(--bg-card, #1e1e3f);
}

[data-theme="dark"] .search-result-item .result-title a {
    color: var(--text-primary, #e8e8e8);
}

/* Responsive */
@media (max-width: 768px) {
    .search-container {
        padding: 0 1rem;
    }

    .search-title {
        font-size: 1.5rem;
    }

    .search-results-grid {
        grid-template-columns: 1fr;
    }

    .no-results-icon svg {
        width: 60px;
        height: 60px;
    }
}
</style>

<?php get_footer(); ?>
