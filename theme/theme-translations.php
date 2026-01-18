<?php
/**
 * Dynamic Theme Translations
 * Provides translations based on URL language path
 *
 * @package Batumi_Theme
 */

if (!defined('ABSPATH')) {
    exit;
}

class Batumi_Theme_Translations {

    private $translations = array();
    private $current_lang = 'ka';

    public function __construct() {
        $this->set_current_language();
        $this->load_translations();

        // Override translation functions
        add_filter('gettext', array($this, 'translate_text'), 10, 3);
    }

    /**
     * Detect current language from URL
     */
    private function set_current_language() {
        $request_uri = $_SERVER['REQUEST_URI'];

        if (strpos($request_uri, '/ru/') === 0 || strpos($request_uri, '/ru') === 0) {
            $this->current_lang = 'ru';
        } elseif (strpos($request_uri, '/en/') === 0 || strpos($request_uri, '/en') === 0) {
            $this->current_lang = 'en';
        } else {
            $this->current_lang = 'ka'; // Georgian default
        }
    }

    /**
     * Load all translations
     */
    private function load_translations() {
        $this->translations = array(
            // User menu items
            'Profile' => array(
                'ka' => 'პროფილი',
                'ru' => 'Профиль',
                'en' => 'Profile'
            ),
            'My Listings' => array(
                'ka' => 'ჩემი განცხადებები',
                'ru' => 'Мои объявления',
                'en' => 'My Listings'
            ),
            'Add Listing' => array(
                'ka' => 'განცხადების დამატება',
                'ru' => 'Добавить объявление',
                'en' => 'Add Listing'
            ),
            'Logout' => array(
                'ka' => 'გასვლა',
                'ru' => 'Выход',
                'en' => 'Logout'
            ),
            'Login' => array(
                'ka' => 'შესვლა',
                'ru' => 'Вход',
                'en' => 'Login'
            ),
            'Register' => array(
                'ka' => 'რეგისტრაცია',
                'ru' => 'Регистрация',
                'en' => 'Register'
            ),
            // Filter labels
            'All Categories' => array(
                'ka' => 'ყველა კატეგორია',
                'ru' => 'Все категории',
                'en' => 'All Categories'
            ),
            'All Areas' => array(
                'ka' => 'ყველა უბანი',
                'ru' => 'Все районы',
                'en' => 'All Areas'
            ),
            'Category' => array(
                'ka' => 'კატეგორია',
                'ru' => 'Категория',
                'en' => 'Category'
            ),
            'Area' => array(
                'ka' => 'უბანი',
                'ru' => 'Район',
                'en' => 'Area'
            ),
            'Min Price' => array(
                'ka' => 'მინ. ფასი',
                'ru' => 'Мин. цена',
                'en' => 'Min Price'
            ),
            'Max Price' => array(
                'ka' => 'მაქს. ფასი',
                'ru' => 'Макс. цена',
                'en' => 'Max Price'
            ),
            'Sort By' => array(
                'ka' => 'დალაგება',
                'ru' => 'Сортировка',
                'en' => 'Sort By'
            ),
            'Newest First' => array(
                'ka' => 'ახალი პირველი',
                'ru' => 'Сначала новые',
                'en' => 'Newest First'
            ),
            'Price: Low to High' => array(
                'ka' => 'ფასი: დაბალიდან მაღალამდე',
                'ru' => 'Цена: от низкой к высокой',
                'en' => 'Price: Low to High'
            ),
            'Price: High to Low' => array(
                'ka' => 'ფასი: მაღლიდან დაბლამდე',
                'ru' => 'Цена: от высокой к низкой',
                'en' => 'Price: High to Low'
            ),
            'Random' => array(
                'ka' => 'შემთხვევითი',
                'ru' => 'Случайно',
                'en' => 'Random'
            ),
            'Clear All' => array(
                'ka' => 'გასუფთავება',
                'ru' => 'Очистить всё',
                'en' => 'Clear All'
            ),
            'Apply Filters' => array(
                'ka' => 'ფილტრების გამოყენება',
                'ru' => 'Применить фильтры',
                'en' => 'Apply Filters'
            ),
            'Filters' => array(
                'ka' => 'ფილტრები',
                'ru' => 'Фильтры',
                'en' => 'Filters'
            ),
            'Search services...' => array(
                'ka' => 'მოძებნეთ სერვისები...',
                'ru' => 'Поиск услуг...',
                'en' => 'Search services...'
            ),
            'Search' => array(
                'ka' => 'ძებნა',
                'ru' => 'Поиск',
                'en' => 'Search'
            ),
            // Service card labels
            'Contact' => array(
                'ka' => 'კონტაქტი',
                'ru' => 'Контакт',
                'en' => 'Contact'
            ),
            'Details' => array(
                'ka' => 'დეტალები',
                'ru' => 'Подробнее',
                'en' => 'Details'
            ),
            'Call' => array(
                'ka' => 'დარეკვა',
                'ru' => 'Позвонить',
                'en' => 'Call'
            ),
            'per hour' => array(
                'ka' => 'საათში',
                'ru' => 'в час',
                'en' => 'per hour'
            ),
            'per day' => array(
                'ka' => 'დღეში',
                'ru' => 'в день',
                'en' => 'per day'
            ),
            'per m²' => array(
                'ka' => 'კვ.მ-ზე',
                'ru' => 'за м²',
                'en' => 'per m²'
            ),
            'Negotiable' => array(
                'ka' => 'შეთანხმებით',
                'ru' => 'Договорная',
                'en' => 'Negotiable'
            ),
            'Free' => array(
                'ka' => 'უფასო',
                'ru' => 'Бесплатно',
                'en' => 'Free'
            ),
            // Favorites
            'Add to Favorites' => array(
                'ka' => 'რჩეულებში დამატება',
                'ru' => 'Добавить в избранное',
                'en' => 'Add to Favorites'
            ),
            'Remove from Favorites' => array(
                'ka' => 'რჩეულებიდან წაშლა',
                'ru' => 'Удалить из избранного',
                'en' => 'Remove from Favorites'
            ),
            'Favorites' => array(
                'ka' => 'რჩეულები',
                'ru' => 'Избранное',
                'en' => 'Favorites'
            ),
            // Report
            'Report' => array(
                'ka' => 'შეტყობინება',
                'ru' => 'Пожаловаться',
                'en' => 'Report'
            ),
            'Report this listing' => array(
                'ka' => 'შეატყობინეთ ამ განცხადების შესახებ',
                'ru' => 'Пожаловаться на это объявление',
                'en' => 'Report this listing'
            )
        );
    }

    /**
     * Translate text
     */
    public function translate_text($translated, $text, $domain) {
        // Only translate for our theme
        if ($domain !== 'batumi-theme') {
            return $translated;
        }

        if (isset($this->translations[$text])) {
            return $this->translations[$text][$this->current_lang];
        }

        return $translated;
    }

    /**
     * Get current language
     */
    public function get_current_language() {
        return $this->current_lang;
    }
}

// Initialize
new Batumi_Theme_Translations();
