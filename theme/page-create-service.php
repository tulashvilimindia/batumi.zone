<?php
/**
 * Template Name: Create Service
 * Description: Form for creating new service listings
 *
 * @package Batumi_Theme
 * @since 0.4.0
 */

// Redirect if not logged in
if (!is_user_logged_in()) {
    wp_redirect(home_url('/login/'));
    exit;
}

get_header();

$current_lang = function_exists('pll_current_language') ? pll_current_language() : 'ge';
$current_user = wp_get_current_user();

// Get user's phone from profile
$user_phone = get_user_meta($current_user->ID, 'phone', true);
?>

<main id="primary" class="site-main service-form-page">
    <div class="container">

        <div class="form-header">
            <a href="<?php echo home_url('/my-listings/'); ?>" class="back-link">&larr; <?php
                if ($current_lang === 'ru') {
                    echo 'Назад к панели';
                } elseif ($current_lang === 'en') {
                    echo 'Back to Dashboard';
                } else {
                    echo 'უკან დაშბორდზე';
                }
            ?></a>
            <h1 class="form-title">
                <?php
                if ($current_lang === 'ru') {
                    echo 'Создать объявление';
                } elseif ($current_lang === 'en') {
                    echo 'Create New Listing';
                } else {
                    echo 'ახალი განცხადების შექმნა';
                }
                ?>
            </h1>
            <p class="form-subtitle">
                <?php
                if ($current_lang === 'ru') {
                    echo 'Заполните информацию хотя бы на одном языке';
                } elseif ($current_lang === 'en') {
                    echo 'Fill out information in at least one language';
                } else {
                    echo 'შეავსეთ ინფორმაცია მინიმუმ ერთ ენაზე';
                }
                ?>
            </p>
        </div>

        <div id="form-messages" class="form-messages"></div>
        <div id="auto-save-status" class="auto-save-status"></div>

        <form id="service-form" class="service-form" method="post">

            <!-- Section 1: Category & Tags -->
            <div class="form-section">
                <h2 class="section-title">
                    1. <?php echo $current_lang === 'ru' ? 'Категория' : ($current_lang === 'en' ? 'Category' : 'კატეგორია'); ?> *
                </h2>

                <div class="form-group">
                    <label for="service-category" class="form-label">
                        <?php echo $current_lang === 'ru' ? 'Направление услуги' : ($current_lang === 'en' ? 'Service Direction' : 'სერვისის მიმართულება'); ?> *
                    </label>
                    <select id="service-category" name="service_category" class="form-input" required>
                        <option value=""><?php echo $current_lang === 'ru' ? 'Выберите категорию...' : ($current_lang === 'en' ? 'Select category...' : 'აირჩიეთ კატეგორია...'); ?></option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="coverage-area" class="form-label">
                        <?php echo $current_lang === 'ru' ? 'Зона покрытия (необязательно)' : ($current_lang === 'en' ? 'Coverage Area (optional)' : 'მომსახურების ზონა (არასავალდებულო)'); ?>
                    </label>
                    <select id="coverage-area" name="coverage_area[]" class="form-input" multiple size="4">
                    </select>
                    <small class="form-help">
                        <?php echo $current_lang === 'ru' ? 'Удерживайте Ctrl для выбора нескольких районов' : ($current_lang === 'en' ? 'Hold Ctrl to select multiple areas' : 'დააჭირეთ Ctrl-ს რამდენიმე ზონის ასარჩევად'); ?>
                    </small>
                </div>

                <div class="form-group">
                    <label for="service-tags" class="form-label">
                        <?php echo $current_lang === 'ru' ? 'Теги (необязательно)' : ($current_lang === 'en' ? 'Tags (optional)' : 'თეგები (არასავალდებულო)'); ?>
                    </label>
                    <input type="text" id="service-tags" name="service_tags" class="form-input tags-input" placeholder="<?php echo $current_lang === 'ru' ? 'Введите теги через запятую...' : ($current_lang === 'en' ? 'Enter tags separated by commas...' : 'შეიყვანეთ თეგები მძიმით გამოყოფილი...'); ?>">
                    <small class="form-help">
                        <?php echo $current_lang === 'ru' ? 'Добавьте ключевые слова для лучшего поиска (например: ремонт, сантехника, срочно)' : ($current_lang === 'en' ? 'Add keywords for better search (e.g.: repair, plumbing, urgent)' : 'დაამატეთ საკვანძო სიტყვები უკეთესი ძიებისთვის (მაგ.: რემონტი, სანტექნიკა, სასწრაფო)'); ?>
                    </small>
                    <div id="tag-suggestions" class="tag-suggestions"></div>
                    <div id="selected-tags" class="selected-tags"></div>
                </div>
            </div>

            <!-- Section 2: Multilingual Content -->
            <div class="form-section">
                <h2 class="section-title">
                    2. <?php echo $current_lang === 'ru' ? 'Название и описание' : ($current_lang === 'en' ? 'Title & Description' : 'სახელი და აღწერა'); ?> *
                </h2>
                <p class="section-help">
                    <?php echo $current_lang === 'ru' ? 'Требуется хотя бы один язык. Рекомендуется заполнить все три.' : ($current_lang === 'en' ? 'At least one language required. All three recommended.' : 'მინიმუმ ერთი ენა აუცილებელია. სამივე ენის შევსება რეკომენდირებულია.'); ?>
                </p>

                <div class="language-tabs">
                    <button type="button" class="lang-tab active" data-lang="ge">&#x1F1EC;&#x1F1EA; ქართული</button>
                    <button type="button" class="lang-tab" data-lang="ru">&#x1F1F7;&#x1F1FA; Русский</button>
                    <button type="button" class="lang-tab" data-lang="en">&#x1F1EC;&#x1F1E7; English</button>
                </div>

                <div class="language-status" id="language-status">
                    <span class="lang-indicator" data-lang="ge"><strong>GE:</strong> <span class="status-text">&times; <?php echo $current_lang === 'ru' ? 'Пусто' : ($current_lang === 'en' ? 'Empty' : 'ცარიელი'); ?></span></span>
                    <span class="lang-indicator" data-lang="ru"><strong>RU:</strong> <span class="status-text">&times; <?php echo $current_lang === 'ru' ? 'Пусто' : ($current_lang === 'en' ? 'Empty' : 'ცარიელი'); ?></span></span>
                    <span class="lang-indicator" data-lang="en"><strong>EN:</strong> <span class="status-text">&times; <?php echo $current_lang === 'ru' ? 'Пусто' : ($current_lang === 'en' ? 'Empty' : 'ცარიელი'); ?></span></span>
                </div>

                <!-- Georgian -->
                <div class="lang-content active" data-lang="ge">
                    <div class="form-group">
                        <label for="title-ge" class="form-label">სათაური (ქართული) *</label>
                        <input type="text" id="title-ge" name="title_ge" class="form-input" maxlength="100" placeholder="მაგ.: პროფესიონალური სამშენებლო მომსახურება">
                        <small class="char-count"><span id="title-ge-count">0</span>/100</small>
                    </div>
                    <div class="form-group">
                        <label for="desc-ge" class="form-label">აღწერა (ქართული) *</label>
                        <textarea id="desc-ge" name="desc_ge" class="form-input" rows="6" maxlength="2000" placeholder="ჩამოთვალეთ თქვენი მომსახურებები, გამოცდილება და პრიორიტეტები..."></textarea>
                        <small class="char-count"><span id="desc-ge-count">0</span>/2000</small>
                    </div>
                </div>

                <!-- Russian -->
                <div class="lang-content" data-lang="ru">
                    <div class="form-group">
                        <label for="title-ru" class="form-label">Название (Русский) *</label>
                        <input type="text" id="title-ru" name="title_ru" class="form-input" maxlength="100" placeholder="Например: Профессиональные строительные услуги">
                        <small class="char-count"><span id="title-ru-count">0</span>/100</small>
                    </div>
                    <div class="form-group">
                        <label for="desc-ru" class="form-label">Описание (Русский) *</label>
                        <textarea id="desc-ru" name="desc_ru" class="form-input" rows="6" maxlength="2000" placeholder="Перечислите ваши услуги, опыт и приоритеты..."></textarea>
                        <small class="char-count"><span id="desc-ru-count">0</span>/2000</small>
                    </div>
                </div>

                <!-- English -->
                <div class="lang-content" data-lang="en">
                    <div class="form-group">
                        <label for="title-en" class="form-label">Title (English) *</label>
                        <input type="text" id="title-en" name="title_en" class="form-input" maxlength="100" placeholder="E.g.: Professional Construction Services">
                        <small class="char-count"><span id="title-en-count">0</span>/100</small>
                    </div>
                    <div class="form-group">
                        <label for="desc-en" class="form-label">Description (English) *</label>
                        <textarea id="desc-en" name="desc_en" class="form-input" rows="6" maxlength="2000" placeholder="List your services, experience, and priorities..."></textarea>
                        <small class="char-count"><span id="desc-en-count">0</span>/2000</small>
                    </div>
                </div>
            </div>

            <!-- Section 3: Pricing -->
            <div class="form-section">
                <h2 class="section-title">
                    3. <?php echo $current_lang === 'ru' ? 'Цена' : ($current_lang === 'en' ? 'Pricing' : 'ფასი'); ?> *
                </h2>

                <div class="form-group">
                    <label class="form-label">
                        <?php echo $current_lang === 'ru' ? 'Модель ценообразования' : ($current_lang === 'en' ? 'Price Model' : 'ფასის მოდელი'); ?> *
                    </label>
                    <div class="radio-group">
                        <label class="radio-option">
                            <input type="radio" name="price_model" value="fixed" checked>
                            <span><?php echo $current_lang === 'ru' ? 'Фиксированная цена' : ($current_lang === 'en' ? 'Fixed Price' : 'ფიქსირებული ფასი'); ?></span>
                        </label>
                        <label class="radio-option">
                            <input type="radio" name="price_model" value="hourly">
                            <span><?php echo $current_lang === 'ru' ? 'Почасовая оплата' : ($current_lang === 'en' ? 'Hourly Rate' : 'საათობრივი ანაზღაურება'); ?></span>
                        </label>
                        <label class="radio-option">
                            <input type="radio" name="price_model" value="daily">
                            <span><?php echo $current_lang === 'ru' ? 'Дневная ставка' : ($current_lang === 'en' ? 'Daily Rate' : 'დღიური განაკვეთი'); ?></span>
                        </label>
                        <label class="radio-option">
                            <input type="radio" name="price_model" value="negotiable">
                            <span><?php echo $current_lang === 'ru' ? 'Договорная' : ($current_lang === 'en' ? 'Negotiable' : 'შეთანხმებით'); ?></span>
                        </label>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="price-value" class="form-label">
                            <?php echo $current_lang === 'ru' ? 'Цена' : ($current_lang === 'en' ? 'Price' : 'ფასი'); ?> *
                        </label>
                        <input type="number" id="price-value" name="price_value" class="form-input" min="0" step="0.01" placeholder="0.00" required>
                    </div>
                    <div class="form-group">
                        <label for="currency" class="form-label">
                            <?php echo $current_lang === 'ru' ? 'Валюта' : ($current_lang === 'en' ? 'Currency' : 'ვალუტა'); ?> *
                        </label>
                        <select id="currency" name="currency" class="form-input" required>
                            <option value="GEL" selected>GEL (&lari;)</option>
                            <option value="USD">USD ($)</option>
                            <option value="EUR">EUR (&euro;)</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Section 4: Location -->
            <div class="form-section">
                <h2 class="section-title">
                    4. <?php echo $current_lang === 'ru' ? 'Местоположение' : ($current_lang === 'en' ? 'Location' : 'ადგილმდებარეობა'); ?> *
                </h2>
                <p class="section-help">
                    <?php echo $current_lang === 'ru' ? 'Нажмите на карту, чтобы установить местоположение вашей услуги' : ($current_lang === 'en' ? 'Click on the map to set your service location' : 'დააწკაპუნეთ რუკაზე მომსახურების ადგილის დასადგენად'); ?>
                </p>

                <div id="map" class="map-container"></div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="latitude" class="form-label">
                            <?php echo $current_lang === 'ru' ? 'Широта' : ($current_lang === 'en' ? 'Latitude' : 'განედი'); ?> *
                        </label>
                        <input type="number" id="latitude" name="latitude" class="form-input" step="0.000001" placeholder="41.642167" required readonly>
                    </div>
                    <div class="form-group">
                        <label for="longitude" class="form-label">
                            <?php echo $current_lang === 'ru' ? 'Долгота' : ($current_lang === 'en' ? 'Longitude' : 'გრძედი'); ?> *
                        </label>
                        <input type="number" id="longitude" name="longitude" class="form-input" step="0.000001" placeholder="41.633028" required readonly>
                    </div>
                </div>

                <div id="bounds-warning" class="warning-message" style="display: none;">
                    &#9888; <?php echo $current_lang === 'ru' ? 'Местоположение за пределами Батуми. Услуга может не быть опубликована.' : ($current_lang === 'en' ? 'Location is outside Batumi bounds. Service may not be published.' : 'მდებარეობა ბათუმის საზღვრებს გარეთაა. სერვისი შეიძლება ვერ გამოქვეყნდეს.'); ?>
                </div>

                <div class="form-group">
                    <label for="neighborhood" class="form-label">
                        <?php echo $current_lang === 'ru' ? 'Район (необязательно)' : ($current_lang === 'en' ? 'Neighborhood (optional)' : 'უბანი (არასავალდებულო)'); ?>
                    </label>
                    <input type="text" id="neighborhood" name="neighborhood" class="form-input" placeholder="<?php echo $current_lang === 'ru' ? 'Например: Старый Батуми' : ($current_lang === 'en' ? 'E.g.: Old Batumi' : 'მაგ.: ძველი ბათუმი'); ?>">
                </div>
            </div>

            <!-- Section 5: Contact Information -->
            <div class="form-section">
                <h2 class="section-title">
                    5. <?php echo $current_lang === 'ru' ? 'Контактная информация' : ($current_lang === 'en' ? 'Contact Information' : 'საკონტაქტო ინფორმაცია'); ?> *
                </h2>

                <div class="form-group">
                    <label for="phone" class="form-label">
                        <?php echo $current_lang === 'ru' ? 'Телефон' : ($current_lang === 'en' ? 'Phone' : 'ტელეფონი'); ?> *
                    </label>
                    <input type="tel" id="phone" name="phone" class="form-input" pattern="[0-9+\-\s()]+" placeholder="+995 555 12 34 56" value="<?php echo esc_attr($user_phone); ?>" required>
                    <small class="form-help">
                        <?php echo $current_lang === 'ru' ? 'Обязательное поле для публикации' : ($current_lang === 'en' ? 'Required to publish' : 'აუცილებელია გამოსაქვეყნებლად'); ?>
                    </small>
                </div>

                <div class="form-group">
                    <label for="whatsapp" class="form-label">
                        <?php echo $current_lang === 'ru' ? 'WhatsApp (необязательно)' : ($current_lang === 'en' ? 'WhatsApp (optional)' : 'WhatsApp (არასავალდებულო)'); ?>
                    </label>
                    <input type="tel" id="whatsapp" name="whatsapp" class="form-input" pattern="[0-9+\-\s()]+" placeholder="+995 555 12 34 56">
                </div>

                <div class="form-group">
                    <label for="email" class="form-label">
                        <?php echo $current_lang === 'ru' ? 'Email (необязательно)' : ($current_lang === 'en' ? 'Email (optional)' : 'ელფოსტა (არასავალდებულო)'); ?>
                    </label>
                    <input type="email" id="email" name="email" class="form-input" placeholder="contact@example.com">
                </div>
            </div>

            <!-- Section 6: Images -->
            <div class="form-section">
                <h2 class="section-title">
                    6. <?php echo $current_lang === 'ru' ? 'Фотографии' : ($current_lang === 'en' ? 'Images' : 'სურათები'); ?> (<?php echo $current_lang === 'ru' ? 'необязательно, макс. 10' : ($current_lang === 'en' ? 'optional, max 10' : 'არასავალდებულო, მაქს. 10'); ?>)
                </h2>

                <div class="image-upload-zone" id="image-upload-zone">
                    <div class="upload-prompt">
                        <span class="upload-icon">&#128247;</span>
                        <p><?php echo $current_lang === 'ru' ? 'Нажмите или перетащите изображения сюда' : ($current_lang === 'en' ? 'Click or drag images here' : 'დააწკაპუნეთ ან გადაიტანეთ სურათები აქ'); ?></p>
                        <small><?php echo $current_lang === 'ru' ? 'JPEG, PNG, GIF, WebP - Максимум 5MB на изображение' : ($current_lang === 'en' ? 'JPEG, PNG, GIF, WebP - Max 5MB per image' : 'JPEG, PNG, GIF, WebP - მაქს. 5MB სურათზე'); ?></small>
                    </div>
                    <input type="file" id="image-input" accept="image/*" multiple style="display: none;">
                </div>

                <div id="image-gallery" class="image-gallery"></div>

                <div id="image-count" class="image-count">
                    <span id="image-count-text">0 / 10 <?php echo $current_lang === 'ru' ? 'изображений загружено' : ($current_lang === 'en' ? 'images uploaded' : 'სურათი აიტვირთა'); ?></span>
                </div>
            </div>

            <!-- Section 7: Validation Summary & Publish -->
            <div class="form-section">
                <h2 class="section-title">
                    7. <?php echo $current_lang === 'ru' ? 'Проверка и публикация' : ($current_lang === 'en' ? 'Validation & Publish' : 'ვალიდაცია და გამოქვეყნება'); ?>
                </h2>

                <div id="validation-summary" class="validation-summary">
                    <!-- Populated by JavaScript -->
                </div>

                <div class="form-actions">
                    <button type="button" id="save-draft-btn" class="btn btn-secondary">
                        <?php echo $current_lang === 'ru' ? 'Сохранить черновик' : ($current_lang === 'en' ? 'Save as Draft' : 'შენახვა მონახაზად'); ?>
                    </button>
                    <button type="submit" id="publish-btn" class="btn btn-primary">
                        <?php echo $current_lang === 'ru' ? 'Опубликовать' : ($current_lang === 'en' ? 'Publish Listing' : 'განცხადების გამოქვეყნება'); ?>
                    </button>
                </div>
            </div>

        </form>

    </div>
</main>

<!-- Leaflet.js for Map -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
var currentLang = '<?php echo $current_lang; ?>';
var wpNonce = '<?php echo wp_create_nonce('wp_rest'); ?>';
var serviceId = null; // Will be set after first save
var uploadedImages = [];
var map, marker;

var translations = {
    saving: '<?php echo $current_lang === 'ru' ? 'Сохранение...' : ($current_lang === 'en' ? 'Saving...' : 'ინახება...'); ?>',
    draftSaved: '<?php echo $current_lang === 'ru' ? 'Черновик сохранен в' : ($current_lang === 'en' ? 'Draft saved at' : 'მონახაზი შენახულია'); ?>',
    saveFailed: '<?php echo $current_lang === 'ru' ? 'Не удалось сохранить' : ($current_lang === 'en' ? 'Failed to save' : 'შენახვა ვერ მოხერხდა'); ?>',
    uploading: '<?php echo $current_lang === 'ru' ? 'Загрузка...' : ($current_lang === 'en' ? 'Uploading...' : 'იტვირთება...'); ?>',
    uploadSuccess: '<?php echo $current_lang === 'ru' ? 'Изображение загружено' : ($current_lang === 'en' ? 'Image uploaded' : 'სურათი აიტვირთა'); ?>',
    uploadFailed: '<?php echo $current_lang === 'ru' ? 'Не удалось загрузить изображение' : ($current_lang === 'en' ? 'Failed to upload image' : 'სურათის ატვირთვა ვერ მოხერხდა'); ?>',
    maxImages: '<?php echo $current_lang === 'ru' ? 'Максимум 10 изображений' : ($current_lang === 'en' ? 'Maximum 10 images' : 'მაქსიმუმ 10 სურათი'); ?>',
    deleteConfirm: '<?php echo $current_lang === 'ru' ? 'Удалить это изображение?' : ($current_lang === 'en' ? 'Delete this image?' : 'წაშალოთ ეს სურათი?'); ?>',
    publishSuccess: '<?php echo $current_lang === 'ru' ? 'Объявление опубликовано успешно!' : ($current_lang === 'en' ? 'Listing published successfully!' : 'განცხადება წარმატებით გამოქვეყნდა!'); ?>',
    publishFailed: '<?php echo $current_lang === 'ru' ? 'Не удалось опубликовать' : ($current_lang === 'en' ? 'Failed to publish' : 'გამოქვეყნება ვერ მოხერხდა'); ?>',
    validationErrors: '<?php echo $current_lang === 'ru' ? 'Исправьте следующие ошибки:' : ($current_lang === 'en' ? 'Please fix the following errors:' : 'გამოასწორეთ შემდეგი შეცდომები:'); ?>',
    complete: '<?php echo $current_lang === 'ru' ? 'Завершено' : ($current_lang === 'en' ? 'Complete' : 'დასრულებული'); ?>',
    missing: '<?php echo $current_lang === 'ru' ? 'Отсутствует' : ($current_lang === 'en' ? 'Missing' : 'არასრული'); ?>',
    empty: '<?php echo $current_lang === 'ru' ? 'Пусто' : ($current_lang === 'en' ? 'Empty' : 'ცარიელი'); ?>'
};

// Include service-form.js functionality inline
<?php include get_template_directory() . '/js/service-form-inline.js'; ?>
</script>

<?php
get_footer();
