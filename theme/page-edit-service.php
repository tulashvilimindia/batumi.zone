<?php
/**
 * Template Name: Edit Service
 * Description: Form for editing existing service listings
 *
 * @package Batumi_Theme
 * @since 0.4.0
 */

// Redirect if not logged in
if (!is_user_logged_in()) {
    wp_redirect(home_url('/login/'));
    exit;
}

// Get service ID from URL parameter
$service_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$service_id) {
    wp_redirect(home_url('/my-listings/'));
    exit;
}

get_header();

$current_lang = function_exists('pll_current_language') ? pll_current_language() : 'ge';
$current_user = wp_get_current_user();
?>

<main id="primary" class="site-main service-form-page">
    <div class="container">

        <div class="form-header">
            <a href="<?php echo home_url('/my-listings/'); ?>" class="back-link">← <?php
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
                    echo 'Редактировать объявление';
                } elseif ($current_lang === 'en') {
                    echo 'Edit Listing';
                } else {
                    echo 'განცხადების რედაქტირება';
                }
                ?>
            </h1>
            <p class="form-subtitle">
                <?php
                if ($current_lang === 'ru') {
                    echo 'Обновите информацию и сохраните изменения';
                } elseif ($current_lang === 'en') {
                    echo 'Update information and save changes';
                } else {
                    echo 'განაახლეთ ინფორმაცია და შეინახეთ ცვლილებები';
                }
                ?>
            </p>
        </div>

        <div id="form-messages" class="form-messages"></div>
        <div id="auto-save-status" class="auto-save-status"></div>

        <div id="loading-indicator" class="loading-indicator">
            <div class="spinner">⏳</div>
            <p><?php echo $current_lang === 'ru' ? 'Загрузка...' : ($current_lang === 'en' ? 'Loading...' : 'იტვირთება...'); ?></p>
        </div>

        <!-- Form will be populated by JavaScript -->
        <form id="service-form" class="service-form" method="post" style="display: none;">

            <!-- Same structure as create form -->
            <!-- Section 1: Category -->
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

            <!-- Section 2: Multilingual Content (same as create) -->
            <div class="form-section">
                <h2 class="section-title">
                    2. <?php echo $current_lang === 'ru' ? 'Название и описание' : ($current_lang === 'en' ? 'Title & Description' : 'სახელი და აღწერა'); ?> *
                </h2>
                <p class="section-help">
                    <?php echo $current_lang === 'ru' ? 'Требуется хотя бы один язык. Рекомендуется заполнить все три.' : ($current_lang === 'en' ? 'At least one language required. All three recommended.' : 'მინიმუმ ერთი ენა აუცილებელია. სამივე ენის შევსება რეკომენდირებულია.'); ?>
                </p>

                <div class="language-tabs">
                    <button type="button" class="lang-tab active" data-lang="ge">🇬🇪 ქართული</button>
                    <button type="button" class="lang-tab" data-lang="ru">🇷🇺 Русский</button>
                    <button type="button" class="lang-tab" data-lang="en">🇬🇧 English</button>
                </div>

                <div class="language-status" id="language-status">
                    <span class="lang-indicator" data-lang="ge"><strong>GE:</strong> <span class="status-text">✗ Empty</span></span>
                    <span class="lang-indicator" data-lang="ru"><strong>RU:</strong> <span class="status-text">✗ Empty</span></span>
                    <span class="lang-indicator" data-lang="en"><strong>EN:</strong> <span class="status-text">✗ Empty</span></span>
                </div>

                <!-- Georgian -->
                <div class="lang-content active" data-lang="ge">
                    <div class="form-group">
                        <label for="title-ge" class="form-label">სათაური (ქართული) *</label>
                        <input type="text" id="title-ge" name="title_ge" class="form-input" maxlength="100">
                        <small class="char-count"><span id="title-ge-count">0</span>/100</small>
                    </div>
                    <div class="form-group">
                        <label for="desc-ge" class="form-label">აღწერა (ქართული) *</label>
                        <textarea id="desc-ge" name="desc_ge" class="form-input" rows="6" maxlength="2000"></textarea>
                        <small class="char-count"><span id="desc-ge-count">0</span>/2000</small>
                    </div>
                </div>

                <!-- Russian -->
                <div class="lang-content" data-lang="ru">
                    <div class="form-group">
                        <label for="title-ru" class="form-label">Название (Русский) *</label>
                        <input type="text" id="title-ru" name="title_ru" class="form-input" maxlength="100">
                        <small class="char-count"><span id="title-ru-count">0</span>/100</small>
                    </div>
                    <div class="form-group">
                        <label for="desc-ru" class="form-label">Описание (Русский) *</label>
                        <textarea id="desc-ru" name="desc_ru" class="form-input" rows="6" maxlength="2000"></textarea>
                        <small class="char-count"><span id="desc-ru-count">0</span>/2000</small>
                    </div>
                </div>

                <!-- English -->
                <div class="lang-content" data-lang="en">
                    <div class="form-group">
                        <label for="title-en" class="form-label">Title (English) *</label>
                        <input type="text" id="title-en" name="title_en" class="form-input" maxlength="100">
                        <small class="char-count"><span id="title-en-count">0</span>/100</small>
                    </div>
                    <div class="form-group">
                        <label for="desc-en" class="form-label">Description (English) *</label>
                        <textarea id="desc-en" name="desc_en" class="form-input" rows="6" maxlength="2000"></textarea>
                        <small class="char-count"><span id="desc-en-count">0</span>/2000</small>
                    </div>
                </div>
            </div>

            <!-- Section 3: Pricing (same as create) -->
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
                            <input type="radio" name="price_model" value="fixed">
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
                        <input type="number" id="price-value" name="price_value" class="form-input" min="0" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label for="currency" class="form-label">
                            <?php echo $current_lang === 'ru' ? 'Валюта' : ($current_lang === 'en' ? 'Currency' : 'ვალუტა'); ?> *
                        </label>
                        <select id="currency" name="currency" class="form-input" required>
                            <option value="GEL">GEL (₾)</option>
                            <option value="USD">USD ($)</option>
                            <option value="EUR">EUR (€)</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Section 4: Location (same as create) -->
            <div class="form-section">
                <h2 class="section-title">
                    4. <?php echo $current_lang === 'ru' ? 'Местоположение' : ($current_lang === 'en' ? 'Location' : 'ადგილმდებარეობა'); ?> *
                </h2>

                <div id="map" class="map-container"></div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="latitude" class="form-label">
                            <?php echo $current_lang === 'ru' ? 'Широта' : ($current_lang === 'en' ? 'Latitude' : 'განედი'); ?> *
                        </label>
                        <input type="number" id="latitude" name="latitude" class="form-input" step="0.000001" required readonly>
                    </div>
                    <div class="form-group">
                        <label for="longitude" class="form-label">
                            <?php echo $current_lang === 'ru' ? 'Долгота' : ($current_lang === 'en' ? 'Longitude' : 'გრძედი'); ?> *
                        </label>
                        <input type="number" id="longitude" name="longitude" class="form-input" step="0.000001" required readonly>
                    </div>
                </div>

                <div id="bounds-warning" class="warning-message" style="display: none;">
                    ⚠️ <?php echo $current_lang === 'ru' ? 'Местоположение за пределами Батуми' : ($current_lang === 'en' ? 'Location is outside Batumi bounds' : 'მდებარეობა ბათუმის საზღვრებს გარეთაა'); ?>
                </div>

                <div class="form-group">
                    <label for="neighborhood" class="form-label">
                        <?php echo $current_lang === 'ru' ? 'Район (необязательно)' : ($current_lang === 'en' ? 'Neighborhood (optional)' : 'უბანი (არასავალდებულო)'); ?>
                    </label>
                    <input type="text" id="neighborhood" name="neighborhood" class="form-input">
                </div>
            </div>

            <!-- Section 5: Contact Information (same as create) -->
            <div class="form-section">
                <h2 class="section-title">
                    5. <?php echo $current_lang === 'ru' ? 'Контактная информация' : ($current_lang === 'en' ? 'Contact Information' : 'საკონტაქტო ინფორმაცია'); ?> *
                </h2>

                <div class="form-group">
                    <label for="phone" class="form-label">
                        <?php echo $current_lang === 'ru' ? 'Телефон' : ($current_lang === 'en' ? 'Phone' : 'ტელეფონი'); ?> *
                    </label>
                    <input type="tel" id="phone" name="phone" class="form-input" pattern="[0-9+\-\s()]+" required>
                </div>

                <div class="form-group">
                    <label for="whatsapp" class="form-label">
                        <?php echo $current_lang === 'ru' ? 'WhatsApp (необязательно)' : ($current_lang === 'en' ? 'WhatsApp (optional)' : 'WhatsApp (არასავალდებულო)'); ?>
                    </label>
                    <input type="tel" id="whatsapp" name="whatsapp" class="form-input" pattern="[0-9+\-\s()]+">
                </div>

                <div class="form-group">
                    <label for="email" class="form-label">
                        <?php echo $current_lang === 'ru' ? 'Email (необязательно)' : ($current_lang === 'en' ? 'Email (optional)' : 'ელფოსტა (არასავალდებულო)'); ?>
                    </label>
                    <input type="email" id="email" name="email" class="form-input">
                </div>
            </div>

            <!-- Section 6: Images (same as create) -->
            <div class="form-section">
                <h2 class="section-title">
                    6. <?php echo $current_lang === 'ru' ? 'Фотографии' : ($current_lang === 'en' ? 'Images' : 'სურათები'); ?> (<?php echo $current_lang === 'ru' ? 'макс. 10' : ($current_lang === 'en' ? 'max 10' : 'მაქს. 10'); ?>)
                </h2>

                <div class="image-upload-zone" id="image-upload-zone">
                    <div class="upload-prompt">
                        <span class="upload-icon">📷</span>
                        <p><?php echo $current_lang === 'ru' ? 'Нажмите или перетащите изображения' : ($current_lang === 'en' ? 'Click or drag images here' : 'დააწკაპუნეთ ან გადაიტანეთ სურათები'); ?></p>
                    </div>
                    <input type="file" id="image-input" accept="image/*" multiple style="display: none;">
                </div>

                <div id="image-gallery" class="image-gallery"></div>

                <div id="image-count" class="image-count">
                    <span id="image-count-text">0 / 10 images</span>
                </div>
            </div>

            <!-- Section 7: Actions -->
            <div class="form-section">
                <h2 class="section-title">
                    7. <?php echo $current_lang === 'ru' ? 'Сохранить изменения' : ($current_lang === 'en' ? 'Save Changes' : 'ცვლილებების შენახვა'); ?>
                </h2>

                <div id="validation-summary" class="validation-summary"></div>

                <div class="form-actions">
                    <button type="button" id="save-draft-btn" class="btn btn-secondary">
                        <?php echo $current_lang === 'ru' ? 'Сохранить изменения' : ($current_lang === 'en' ? 'Save Changes' : 'ცვლილებების შენახვა'); ?>
                    </button>
                    <button type="submit" id="publish-btn" class="btn btn-primary">
                        <?php echo $current_lang === 'ru' ? 'Сохранить и опубликовать' : ($current_lang === 'en' ? 'Save & Publish' : 'შენახვა და გამოქვეყნება'); ?>
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
var serviceId = <?php echo $service_id; ?>;
var uploadedImages = [];
var map, marker;

var translations = {
    saving: '<?php echo $current_lang === 'ru' ? 'Сохранение...' : ($current_lang === 'en' ? 'Saving...' : 'ინახება...'); ?>',
    draftSaved: '<?php echo $current_lang === 'ru' ? 'Изменения сохранены в' : ($current_lang === 'en' ? 'Changes saved at' : 'ცვლილებები შენახულია'); ?>',
    saveFailed: '<?php echo $current_lang === 'ru' ? 'Не удалось сохранить' : ($current_lang === 'en' ? 'Failed to save' : 'შენახვა ვერ მოხერხდა'); ?>',
    uploading: '<?php echo $current_lang === 'ru' ? 'Загрузка...' : ($current_lang === 'en' ? 'Uploading...' : 'იტვირთება...'); ?>',
    uploadSuccess: '<?php echo $current_lang === 'ru' ? 'Изображение загружено' : ($current_lang === 'en' ? 'Image uploaded' : 'სურათი აიტვირთა'); ?>',
    uploadFailed: '<?php echo $current_lang === 'ru' ? 'Не удалось загрузить' : ($current_lang === 'en' ? 'Failed to upload' : 'ატვირთვა ვერ მოხერხდა'); ?>',
    maxImages: '<?php echo $current_lang === 'ru' ? 'Максимум 10 изображений' : ($current_lang === 'en' ? 'Maximum 10 images' : 'მაქსიმუმ 10 სურათი'); ?>',
    deleteConfirm: '<?php echo $current_lang === 'ru' ? 'Удалить это изображение?' : ($current_lang === 'en' ? 'Delete this image?' : 'წაშალოთ ეს სურათი?'); ?>',
    publishSuccess: '<?php echo $current_lang === 'ru' ? 'Изменения сохранены!' : ($current_lang === 'en' ? 'Changes saved successfully!' : 'ცვლილებები შენახულია!'); ?>',
    publishFailed: '<?php echo $current_lang === 'ru' ? 'Не удалось сохранить' : ($current_lang === 'en' ? 'Failed to save' : 'შენახვა ვერ მოხერხდა'); ?>',
    validationErrors: '<?php echo $current_lang === 'ru' ? 'Исправьте следующие ошибки:' : ($current_lang === 'en' ? 'Please fix the following errors:' : 'გამოასწორეთ შემდეგი შეცდომები:'); ?>',
    complete: '<?php echo $current_lang === 'ru' ? 'Завершено' : ($current_lang === 'en' ? 'Complete' : 'დასრულებული'); ?>',
    missing: '<?php echo $current_lang === 'ru' ? 'Отсутствует' : ($current_lang === 'en' ? 'Missing' : 'არასრული'); ?>',
    empty: '<?php echo $current_lang === 'ru' ? 'Пусто' : ($current_lang === 'en' ? 'Empty' : 'ცარიელი'); ?>',
    loadFailed: '<?php echo $current_lang === 'ru' ? 'Не удалось загрузить объявление' : ($current_lang === 'en' ? 'Failed to load listing' : 'განცხადების ჩატვირთვა ვერ მოხერხდა'); ?>'
};

// Load existing service data on page load
jQuery(document).ready(function($) {
    loadServiceData();
});

function loadServiceData() {
    var $ = jQuery;

    $.ajax({
        url: `/wp-json/batumizone/v1/my/services/${serviceId}`,
        type: 'GET',
        beforeSend: function(xhr) {
            xhr.setRequestHeader('X-WP-Nonce', wpNonce);
        },
        success: function(service) {
            // Hide loading, show form
            $('#loading-indicator').hide();
            $('#service-form').show();

            // Populate form fields
            populateForm(service);

            // Initialize form functionality (from service-form-inline.js)
            initializeEditForm();
        },
        error: function(xhr) {
            $('#loading-indicator').html(`<div class="message message-error">${translations.loadFailed}</div>`);

            // Redirect to dashboard after 3 seconds
            setTimeout(function() {
                window.location.href = '/my-listings/';
            }, 3000);
        }
    });
}

function populateForm(service) {
    var $ = jQuery;

    // Taxonomies (will be selected after loading)
    setTimeout(function() {
        if (service.service_category) {
            $('#service-category').val(service.service_category.term_id);
        }

        if (service.coverage_area && service.coverage_area.length > 0) {
            var areaIds = service.coverage_area.map(area => area.term_id);
            $('#coverage-area').val(areaIds);
        }
    }, 500); // Wait for taxonomy dropdowns to load

    // Multilingual content
    $('#title-ge').val(service.title_ge || '');
    $('#title-ru').val(service.title_ru || '');
    $('#title-en').val(service.title_en || '');
    $('#desc-ge').val(service.desc_ge || '');
    $('#desc-ru').val(service.desc_ru || '');
    $('#desc-en').val(service.desc_en || '');

    // Pricing
    $(`input[name="price_model"][value="${service.price_model}"]`).prop('checked', true);
    $('#price-value').val(service.price_value || 0);
    $('#currency').val(service.currency || 'GEL');

    // Location
    $('#latitude').val(service.latitude || '');
    $('#longitude').val(service.longitude || '');
    $('#neighborhood').val(service.neighborhood || '');

    // Place marker on map
    if (service.latitude && service.longitude) {
        setTimeout(function() {
            if (map) {
                var latLng = L.latLng(service.latitude, service.longitude);
                if (marker) {
                    marker.setLatLng(latLng);
                } else {
                    marker = L.marker(latLng).addTo(map);
                }
                map.setView(latLng, 15);
            }
        }, 1000);
    }

    // Contact
    $('#phone').val(service.contact?.phone || '');
    $('#whatsapp').val(service.contact?.whatsapp || '');
    $('#email').val(service.contact?.email || '');

    // Images - load gallery
    if (service.gallery && service.gallery.length > 0) {
        uploadedImages = service.gallery.map(img => ({
            id: img.id,
            url: img.thumbnail
        }));

        setTimeout(function() {
            if (typeof renderGallery === 'function') {
                renderGallery();
                updateImageCount();
            }
        }, 500);
    }

    // Trigger character counts and language status
    $('#title-ge, #title-ru, #title-en, #desc-ge, #desc-ru, #desc-en').trigger('input');
}

function initializeEditForm() {
    // Include the same service-form-inline.js functionality
    <?php include get_template_directory() . '/js/service-form-inline.js'; ?>
}
</script>

<?php
get_footer();
