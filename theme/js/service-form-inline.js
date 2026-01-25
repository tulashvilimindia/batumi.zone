// Service Form JavaScript - Handles create/edit functionality
// This file is included inline in page-create-service.php and page-edit-service.php

jQuery(document).ready(function($) {

    // ============================================
    // INITIALIZATION
    // ============================================

    // Load taxonomies
    loadCategories();
    loadAreas();

    // Initialize map
    initializeMap();

    // Setup event listeners
    setupLanguageTabs();
    setupCharacterCounts();
    setupImageUpload();
    setupFormSubmission();
    setupAutoSave();
    setupPriceModelToggle();

    // ============================================
    // TAXONOMY LOADING
    // ============================================

    function loadCategories() {
        $.ajax({
            url: '/wp-json/batumizone/v1/taxonomies/service_category',
            type: 'GET',
            success: function(response) {
                var $select = $('#service-category');
                // API returns array directly
                var terms = Array.isArray(response) ? response : (response.terms || []);
                terms.forEach(function(term) {
                    var name = term.name || term.name_original;
                    $select.append('<option value="' + term.id + '">' + name + '</option>');
                });
            },
            error: function(xhr) {
                console.error('Failed to load categories:', xhr);
            }
        });
    }

    function loadAreas() {
        $.ajax({
            url: '/wp-json/batumizone/v1/taxonomies/coverage_area',
            type: 'GET',
            success: function(response) {
                var $select = $('#coverage-area');
                // API returns array directly
                var terms = Array.isArray(response) ? response : (response.terms || []);
                terms.forEach(function(term) {
                    var name = term.name || term.name_original;
                    $select.append('<option value="' + term.id + '">' + name + '</option>');
                });
            },
            error: function(xhr) {
                console.error('Failed to load areas:', xhr);
            }
        });
    }

    // ============================================
    // LANGUAGE TABS
    // ============================================

    function setupLanguageTabs() {
        $('.lang-tab').on('click', function() {
            var lang = $(this).data('lang');

            // Update tab active state
            $('.lang-tab').removeClass('active');
            $(this).addClass('active');

            // Show corresponding content
            $('.lang-content').removeClass('active');
            $('.lang-content[data-lang="' + lang + '"]').addClass('active');
        });

        // Update language status on input
        $('input[name^="title_"], textarea[name^="desc_"]').on('input', updateLanguageStatus);
    }

    function updateLanguageStatus() {
        ['ge', 'ru', 'en'].forEach(function(lang) {
            var title = $('#title-' + lang).val().trim();
            var desc = $('#desc-' + lang).val().trim();

            var $indicator = $('.lang-indicator[data-lang="' + lang + '"] .status-text');

            if (title && desc) {
                $indicator.html('&#10003; ' + translations.complete).removeClass('incomplete').addClass('complete');
            } else if (title || desc) {
                $indicator.html('&#9888; ' + translations.missing).removeClass('complete').addClass('incomplete');
            } else {
                $indicator.html('&#10007; ' + translations.empty).removeClass('complete incomplete');
            }
        });
    }

    // ============================================
    // CHARACTER COUNTS
    // ============================================

    function setupCharacterCounts() {
        // Title character counts
        ['ge', 'ru', 'en'].forEach(function(lang) {
            $('#title-' + lang).on('input', function() {
                var count = $(this).val().length;
                $('#title-' + lang + '-count').text(count);
            });

            $('#desc-' + lang).on('input', function() {
                var count = $(this).val().length;
                $('#desc-' + lang + '-count').text(count);
            });
        });
    }

    // ============================================
    // MAP INTEGRATION
    // ============================================

    function initializeMap() {
        // Initialize Leaflet map centered on Batumi
        map = L.map('map').setView([41.6421, 41.6330], 13);

        // Add OpenStreetMap tiles
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors',
            maxZoom: 18
        }).addTo(map);

        // Add Batumi bounds overlay
        var bounds = L.rectangle([
            [41.57, 41.57],  // Southwest
            [41.70, 41.72]   // Northeast
        ], {
            color: '#2c7fb8',
            weight: 2,
            fillOpacity: 0.05,
            dashArray: '5, 10'
        }).addTo(map);

        // Click to place marker
        map.on('click', function(e) {
            var lat = e.latlng.lat;
            var lng = e.latlng.lng;

            // Update or create marker
            if (marker) {
                marker.setLatLng(e.latlng);
            } else {
                marker = L.marker(e.latlng).addTo(map);
            }

            // Update form fields
            $('#latitude').val(lat.toFixed(6));
            $('#longitude').val(lng.toFixed(6));

            // Check bounds
            checkBounds(lat, lng);
        });
    }

    function checkBounds(lat, lng) {
        var $warning = $('#bounds-warning');

        if (lat < 41.57 || lat > 41.70 || lng < 41.57 || lng > 41.72) {
            $warning.show();
        } else {
            $warning.hide();
        }
    }

    // ============================================
    // IMAGE UPLOAD
    // ============================================

    function setupImageUpload() {
        // Click to upload
        $('#image-upload-zone').on('click', function() {
            $('#image-input').click();
        });

        // File input change
        $('#image-input').on('change', function() {
            var files = this.files;
            handleFiles(files);
        });

        // Drag and drop
        var zone = $('#image-upload-zone')[0];
        if (zone) {
            zone.addEventListener('dragover', function(e) {
                e.preventDefault();
                $(this).addClass('drag-over');
            });

            zone.addEventListener('dragleave', function(e) {
                $(this).removeClass('drag-over');
            });

            zone.addEventListener('drop', function(e) {
                e.preventDefault();
                $(this).removeClass('drag-over');

                var files = e.dataTransfer.files;
                handleFiles(files);
            });
        }
    }

    function handleFiles(files) {
        // Check max images
        if (uploadedImages.length + files.length > 10) {
            showMessage('error', translations.maxImages);
            return;
        }

        // Upload each file
        Array.from(files).forEach(function(file) {
            uploadImage(file);
        });
    }

    function uploadImage(file) {
        // Validate file type
        if (!file.type.match('image.*')) {
            showMessage('error', 'Invalid file type. Only images allowed.');
            return;
        }

        // Validate file size (5MB)
        if (file.size > 5 * 1024 * 1024) {
            showMessage('error', 'Image too large. Maximum 5MB.');
            return;
        }

        // Need service ID first
        if (!serviceId) {
            // Create draft first
            showMessage('info', translations.saving);
            createDraft(function(id) {
                serviceId = id;
                uploadImageToService(file);
            });
        } else {
            uploadImageToService(file);
        }
    }

    function uploadImageToService(file) {
        var formData = new FormData();
        formData.append('file', file);

        // Show uploading indicator
        showMessage('info', translations.uploading);

        $.ajax({
            url: '/wp-json/batumizone/v1/my/services/' + serviceId + '/media',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', wpNonce);
            },
            success: function(response) {
                // Add to uploaded images
                uploadedImages.push({
                    id: response.attachment_id,
                    url: response.thumbnail
                });

                // Update gallery display
                renderGallery();
                updateImageCount();

                showMessage('success', translations.uploadSuccess);
            },
            error: function(xhr) {
                var errMsg = xhr.responseJSON ? xhr.responseJSON.message : 'Unknown error';
                showMessage('error', translations.uploadFailed + ': ' + errMsg);
            }
        });
    }

    function renderGallery() {
        var $gallery = $('#image-gallery');
        $gallery.empty();

        uploadedImages.forEach(function(img, index) {
            var $item = $('<div class="gallery-item" data-id="' + img.id + '">' +
                '<img src="' + img.url + '" alt="Image ' + (index + 1) + '">' +
                '<div class="gallery-actions">' +
                '<button type="button" class="btn-icon move-up"' + (index === 0 ? ' disabled' : '') + '>&uarr;</button>' +
                '<button type="button" class="btn-icon move-down"' + (index === uploadedImages.length - 1 ? ' disabled' : '') + '>&darr;</button>' +
                '<button type="button" class="btn-icon delete-img">&times;</button>' +
                '</div></div>');

            // Delete handler
            $item.find('.delete-img').on('click', function() {
                deleteImage(img.id, index);
            });

            // Move up handler
            $item.find('.move-up').on('click', function() {
                if (index > 0) {
                    var temp = uploadedImages[index];
                    uploadedImages[index] = uploadedImages[index - 1];
                    uploadedImages[index - 1] = temp;
                    reorderGallery();
                    renderGallery();
                }
            });

            // Move down handler
            $item.find('.move-down').on('click', function() {
                if (index < uploadedImages.length - 1) {
                    var temp = uploadedImages[index];
                    uploadedImages[index] = uploadedImages[index + 1];
                    uploadedImages[index + 1] = temp;
                    reorderGallery();
                    renderGallery();
                }
            });

            $gallery.append($item);
        });
    }

    function deleteImage(imageId, index) {
        if (!confirm(translations.deleteConfirm)) return;

        $.ajax({
            url: '/wp-json/batumizone/v1/my/services/' + serviceId + '/media/' + imageId,
            type: 'DELETE',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', wpNonce);
            },
            success: function() {
                uploadedImages.splice(index, 1);
                renderGallery();
                updateImageCount();
                showMessage('success', 'Image deleted');
            },
            error: function() {
                showMessage('error', 'Failed to delete image');
            }
        });
    }

    function reorderGallery() {
        var order = uploadedImages.map(function(img) { return img.id; });

        $.ajax({
            url: '/wp-json/batumizone/v1/my/services/' + serviceId + '/media/reorder',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ order: order }),
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', wpNonce);
            }
        });
    }

    function updateImageCount() {
        var count = uploadedImages.length;
        $('#image-count-text').text(count + ' / 10 images uploaded');
    }

    // ============================================
    // FORM DATA COLLECTION
    // ============================================

    function collectFormData() {
        var coverageArea = $('#coverage-area').val() || [];

        return {
            service_category: parseInt($('#service-category').val()) || null,
            coverage_area: coverageArea.map(function(id) { return parseInt(id); }),
            title_ge: $('#title-ge').val().trim(),
            title_ru: $('#title-ru').val().trim(),
            title_en: $('#title-en').val().trim(),
            desc_ge: $('#desc-ge').val().trim(),
            desc_ru: $('#desc-ru').val().trim(),
            desc_en: $('#desc-en').val().trim(),
            price_model: $('input[name="price_model"]:checked').val(),
            price_value: parseFloat($('#price-value').val()) || 0,
            currency: $('#currency').val(),
            latitude: parseFloat($('#latitude').val()) || null,
            longitude: parseFloat($('#longitude').val()) || null,
            neighborhood: $('#neighborhood').val().trim(),
            phone: $('#phone').val().trim(),
            whatsapp: $('#whatsapp').val().trim(),
            email: $('#email').val().trim(),
            service_tags: getSelectedTags()
        };
    }

    // ============================================
    // AUTO-SAVE
    // ============================================

    var autoSaveTimeout;
    var isDirty = false;

    function setupAutoSave() {
        // Mark dirty on any input
        $('#service-form input, #service-form textarea, #service-form select').on('input change', function() {
            isDirty = true;
            scheduleAutoSave();
        });
    }

    function scheduleAutoSave() {
        clearTimeout(autoSaveTimeout);
        autoSaveTimeout = setTimeout(function() {
            if (isDirty && serviceId) {
                autoSaveDraft();
            }
        }, 30000); // 30 seconds
    }

    function autoSaveDraft() {
        var formData = collectFormData();

        $('#auto-save-status').html('<span class="saving">' + translations.saving + '</span>');

        var url = serviceId
            ? '/wp-json/batumizone/v1/my/services/' + serviceId
            : '/wp-json/batumizone/v1/my/services';

        var method = serviceId ? 'PUT' : 'POST';

        $.ajax({
            url: url,
            type: method,
            contentType: 'application/json',
            data: JSON.stringify(formData),
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', wpNonce);
            },
            success: function(response) {
                if (!serviceId) {
                    serviceId = response.id;
                }
                isDirty = false;
                var time = new Date().toLocaleTimeString();
                $('#auto-save-status').html('<span class="saved">&#10003; ' + translations.draftSaved + ' ' + time + '</span>');

                // Hide after 3 seconds
                setTimeout(function() {
                    $('#auto-save-status').fadeOut();
                }, 3000);
            },
            error: function() {
                $('#auto-save-status').html('<span class="error">' + translations.saveFailed + '</span>');
            }
        });
    }

    // ============================================
    // FORM SUBMISSION
    // ============================================

    function setupFormSubmission() {
        // Save as draft
        $('#save-draft-btn').on('click', function(e) {
            e.preventDefault();
            saveDraft();
        });

        // Publish
        $('#service-form').on('submit', function(e) {
            e.preventDefault();
            publishService();
        });
    }

    function createDraft(callback) {
        var formData = collectFormData();

        $.ajax({
            url: '/wp-json/batumizone/v1/my/services',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(formData),
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', wpNonce);
            },
            success: function(response) {
                serviceId = response.id;
                if (callback) callback(serviceId);
            }
        });
    }

    function saveDraft() {
        var formData = collectFormData();

        showMessage('info', translations.saving);
        disableButtons();

        var url = serviceId
            ? '/wp-json/batumizone/v1/my/services/' + serviceId
            : '/wp-json/batumizone/v1/my/services';

        var method = serviceId ? 'PUT' : 'POST';

        $.ajax({
            url: url,
            type: method,
            contentType: 'application/json',
            data: JSON.stringify(formData),
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', wpNonce);
            },
            success: function(response) {
                if (!serviceId) {
                    serviceId = response.id;
                }
                showMessage('success', 'Draft saved successfully!');

                // Redirect to dashboard after 2 seconds
                setTimeout(function() {
                    window.location.href = '/my-listings/';
                }, 2000);
            },
            error: function(xhr) {
                var error = xhr.responseJSON ? xhr.responseJSON.message : 'Failed to save draft';
                showMessage('error', error);
                enableButtons();
            }
        });
    }

    function publishService() {
        var formData = collectFormData();

        // Validate client-side first
        var validation = validateForm(formData);
        if (!validation.valid) {
            displayValidationErrors(validation.errors);
            return;
        }

        showMessage('info', translations.saving);
        disableButtons();

        // Create or update first
        var url = serviceId
            ? '/wp-json/batumizone/v1/my/services/' + serviceId
            : '/wp-json/batumizone/v1/my/services';

        var method = serviceId ? 'PUT' : 'POST';

        $.ajax({
            url: url,
            type: method,
            contentType: 'application/json',
            data: JSON.stringify(formData),
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', wpNonce);
            },
            success: function(response) {
                if (!serviceId) {
                    serviceId = response.id;
                }

                // Now attempt to publish
                attemptPublish();
            },
            error: function(xhr) {
                var error = xhr.responseJSON ? xhr.responseJSON.message : 'Failed to save';
                showMessage('error', error);
                enableButtons();
            }
        });
    }

    function attemptPublish() {
        $.ajax({
            url: '/wp-json/batumizone/v1/my/services/' + serviceId + '/publish',
            type: 'POST',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', wpNonce);
            },
            success: function(response) {
                showMessage('success', translations.publishSuccess);

                // Redirect to dashboard
                setTimeout(function() {
                    window.location.href = '/my-listings/';
                }, 2000);
            },
            error: function(xhr) {
                if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.validation_errors) {
                    displayValidationErrors(xhr.responseJSON.data.validation_errors);
                } else {
                    showMessage('error', translations.publishFailed);
                }
                enableButtons();
            }
        });
    }

    // ============================================
    // VALIDATION
    // ============================================

    function validateForm(data) {
        var errors = [];

        // Check one-language rule
        var hasGE = data.title_ge && data.desc_ge;
        var hasRU = data.title_ru && data.desc_ru;
        var hasEN = data.title_en && data.desc_en;

        if (!hasGE && !hasRU && !hasEN) {
            errors.push('At least one complete language (title + description) is required');
        }

        // Check required fields
        if (!data.service_category) {
            errors.push('Service category is required');
        }

        if (!data.latitude || !data.longitude) {
            errors.push('Location is required. Please click on the map.');
        }

        if (!data.phone) {
            errors.push('Phone number is required');
        }

        // Check price
        if (!data.price_value || data.price_value <= 0) {
            errors.push('Valid price is required');
        }

        return {
            valid: errors.length === 0,
            errors: errors
        };
    }

    function displayValidationErrors(errors) {
        var $summary = $('#validation-summary');

        var html = '<div class="validation-errors">' +
            '<h3>' + translations.validationErrors + '</h3>' +
            '<ul>' + errors.map(function(err) { return '<li>' + err + '</li>'; }).join('') + '</ul>' +
            '</div>';

        $summary.html(html);

        // Scroll to top
        $('html, body').animate({ scrollTop: 0 }, 300);
    }

    // ============================================
    // HELPERS
    // ============================================

    function setupPriceModelToggle() {
        $('input[name="price_model"]').on('change', function() {
            var value = $(this).val();

            if (value === 'negotiable') {
                $('#price-value').val(0).prop('readonly', true);
            } else {
                $('#price-value').prop('readonly', false);
            }
        });
    }

    function showMessage(type, message) {
        var $messages = $('#form-messages');
        var html = '<div class="message message-' + type + '">' + message + '</div>';
        $messages.html(html);

        // Auto-hide after 5 seconds
        setTimeout(function() {
            $messages.fadeOut(function() {
                $(this).empty().show();
            });
        }, 5000);

        // Scroll to top
        $('html, body').animate({ scrollTop: 0 }, 300);
    }

    function disableButtons() {
        $('#save-draft-btn, #publish-btn').prop('disabled', true);
    }

    function enableButtons() {
        $('#save-draft-btn, #publish-btn').prop('disabled', false);
    }

    // ============================================
    // INITIALIZE ON LOAD
    // ============================================

    // Update language status on load
    updateLanguageStatus();

    // ============================================
    // TAG INPUT HANDLING
    // ============================================

    var selectedTags = [];
    var allTags = [];

    // Fetch existing tags from API
    function fetchExistingTags() {
        $.ajax({
            url: '/wp-json/batumizone/v1/taxonomies/service_tag',
            method: 'GET',
            success: function(response) {
                allTags = Array.isArray(response) ? response : [];
            }
        });
    }

    // Initialize tag input
    function initTagInput() {
        var $input = $("#service-tags");
        var $suggestions = $("#tag-suggestions");
        var $selectedContainer = $("#selected-tags");

        if (!$input.length) return;

        // Fetch existing tags
        fetchExistingTags();

        // On input, show suggestions
        $input.on('input', function() {
            var val = $(this).val().trim().toLowerCase();

            if (val.length < 2) {
                $suggestions.removeClass('active').empty();
                return;
            }

            // Check for comma - add tag
            if (val.indexOf(',') !== -1) {
                var parts = val.split(',');
                parts.forEach(function(p) {
                    var tag = p.trim();
                    if (tag.length > 0) {
                        addTag(tag);
                    }
                });
                $input.val('');
                $suggestions.removeClass('active').empty();
                return;
            }

            // Show matching suggestions
            var matches = allTags.filter(function(t) {
                return t.name.toLowerCase().indexOf(val) !== -1 &&
                       !selectedTags.some(function(s) { return s.name.toLowerCase() === t.name.toLowerCase(); });
            }).slice(0, 5);

            if (matches.length > 0) {
                var html = matches.map(function(t) {
                    return '<div class="tag-suggestion" data-id="' + t.id + '" data-name="' + t.name + '">' +
                           t.name + '<span class="tag-count">(' + t.count + ')</span></div>';
                }).join('');
                $suggestions.html(html).addClass('active');
            } else {
                $suggestions.removeClass('active').empty();
            }
        });

        // On Enter, add typed tag
        $input.on('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                var val = $(this).val().trim();
                if (val.length > 0) {
                    addTag(val);
                    $(this).val('');
                    $suggestions.removeClass('active').empty();
                }
            }
        });

        // Click suggestion
        $suggestions.on('click', '.tag-suggestion', function() {
            var name = $(this).data('name');
            addTag(name);
            $input.val('');
            $suggestions.removeClass('active').empty();
        });

        // Close suggestions on click outside
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.form-group').length) {
                $suggestions.removeClass('active').empty();
            }
        });
    }

    function addTag(name) {
        name = name.trim();
        if (!name || selectedTags.some(function(t) { return t.name.toLowerCase() === name.toLowerCase(); })) {
            return;
        }

        // Check if exists in allTags
        var existing = null;
        for (var i = 0; i < allTags.length; i++) {
            if (allTags[i].name.toLowerCase() === name.toLowerCase()) {
                existing = allTags[i];
                break;
            }
        }
        var tagObj = existing ? { id: existing.id, name: existing.name } : { id: null, name: name };

        selectedTags.push(tagObj);
        renderSelectedTags();
    }

    function removeTag(index) {
        selectedTags.splice(index, 1);
        renderSelectedTags();
    }

    function renderSelectedTags() {
        var $container = $("#selected-tags");
        var html = selectedTags.map(function(t, i) {
            return '<span class="selected-tag">' + t.name +
                   '<button type="button" class="tag-remove" data-index="' + i + '">&times;</button></span>';
        }).join('');
        $container.html(html);
    }

    function getSelectedTags() {
        return selectedTags.map(function(t) { return t.name; });
    }

    // Set tags (for edit mode)
    function setSelectedTags(tags) {
        selectedTags = tags.map(function(t) {
            return { id: t.id || null, name: t.name };
        });
        renderSelectedTags();
    }

    // Remove tag on click
    $(document).on('click', '.tag-remove', function() {
        var index = parseInt($(this).data('index'));
        removeTag(index);
    });

    // Initialize tags
    initTagInput();
});
