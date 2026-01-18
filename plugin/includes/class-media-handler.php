<?php
/**
 * Media Handling for Service Listings
 * - UUID-based file renaming
 * - Safe mime type validation
 * - File size limits
 * - Gallery ordering
 *
 * @package Batumi_Zone_Core
 */

if (!defined('ABSPATH')) {
    exit;
}

class Batumi_Media_Handler {

    // Allowed mime types for uploads
    private $allowed_mime_types = array(
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/gif',
        'image/webp',
    );

    // Maximum file size in bytes (5MB)
    const MAX_FILE_SIZE = 5242880; // 5 * 1024 * 1024

    // Maximum number of images per listing
    const MAX_IMAGES_PER_LISTING = 10;

    /**
     * Initialize media handler
     */
    public function __construct() {
        // Hook into file upload process
        add_filter('wp_handle_upload_prefilter', array($this, 'validate_upload'), 10, 1);
        add_filter('wp_handle_upload', array($this, 'rename_uploaded_file'), 10, 2);

        // Add custom fields for gallery ordering
        add_action('add_meta_boxes', array($this, 'add_gallery_meta_box'));
        add_action('save_post_service_listing', array($this, 'save_gallery_order'), 10, 2);

        // Enqueue admin scripts for gallery ordering
        add_action('admin_enqueue_scripts', array($this, 'enqueue_gallery_scripts'));

        // REST API endpoints for media
        add_action('rest_api_init', array($this, 'register_media_routes'));
    }

    /**
     * Validate file upload (mime type and size)
     */
    public function validate_upload($file) {
        // Check if this is for service_listing post type
        if (!$this->is_service_listing_context()) {
            return $file;
        }

        // Validate mime type
        if (!in_array($file['type'], $this->allowed_mime_types)) {
            $file['error'] = sprintf(
                __('File type not allowed. Allowed types: %s', 'batumizone'),
                implode(', ', array('JPEG', 'PNG', 'GIF', 'WebP'))
            );
            return $file;
        }

        // Validate file size
        if ($file['size'] > self::MAX_FILE_SIZE) {
            $file['error'] = sprintf(
                __('File size exceeds maximum allowed size of %s MB.', 'batumizone'),
                self::MAX_FILE_SIZE / 1048576
            );
            return $file;
        }

        return $file;
    }

    /**
     * Rename uploaded file to UUID
     */
    public function rename_uploaded_file($upload, $context) {
        // Only rename if upload was successful
        if (isset($upload['error']) && $upload['error'] !== false) {
            return $upload;
        }

        // Check if this is for service_listing
        if (!$this->is_service_listing_context()) {
            return $upload;
        }

        $file_path = $upload['file'];
        $file_dir = dirname($file_path);
        $file_ext = pathinfo($file_path, PATHINFO_EXTENSION);

        // Generate UUID-based filename
        $new_filename = $this->generate_uuid() . '.' . $file_ext;
        $new_file_path = $file_dir . '/' . $new_filename;

        // Rename the file
        if (rename($file_path, $new_file_path)) {
            $upload['file'] = $new_file_path;
            $upload['url'] = str_replace(basename($file_path), $new_filename, $upload['url']);
        }

        return $upload;
    }

    /**
     * Generate UUID v4
     */
    private function generate_uuid() {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // Version 4
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // Variant

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    /**
     * Check if we're in service_listing context
     */
    private function is_service_listing_context() {
        global $post;

        // Check global post
        if (isset($post) && $post->post_type === 'service_listing') {
            return true;
        }

        // Check $_POST for post_id
        if (isset($_POST['post_id'])) {
            $post_type = get_post_type($_POST['post_id']);
            if ($post_type === 'service_listing') {
                return true;
            }
        }

        // Check $_REQUEST for post_id (for admin-ajax)
        if (isset($_REQUEST['post_id'])) {
            $post_type = get_post_type($_REQUEST['post_id']);
            if ($post_type === 'service_listing') {
                return true;
            }
        }

        return false;
    }

    /**
     * Add gallery meta box to service listing edit screen
     */
    public function add_gallery_meta_box() {
        add_meta_box(
            'batumizone_gallery',
            __('Service Gallery', 'batumizone'),
            array($this, 'render_gallery_meta_box'),
            'service_listing',
            'normal',
            'high'
        );
    }

    /**
     * Render gallery meta box
     */
    public function render_gallery_meta_box($post) {
        wp_nonce_field('batumizone_gallery_nonce', 'batumizone_gallery_nonce');

        $gallery_ids = get_post_meta($post->ID, '_gallery_image_ids', true);
        $gallery_ids = !empty($gallery_ids) ? explode(',', $gallery_ids) : array();

        ?>
        <div id="batumizone-gallery-container">
            <p><?php _e('Upload and arrange service images (max 10 images, 5MB each).', 'batumizone'); ?></p>

            <div id="batumizone-gallery-images" class="batumizone-gallery-sortable">
                <?php
                foreach ($gallery_ids as $attachment_id) {
                    $attachment_id = intval($attachment_id);
                    if ($attachment_id) {
                        $image_url = wp_get_attachment_image_url($attachment_id, 'thumbnail');
                        if ($image_url) {
                            echo '<div class="batumizone-gallery-image" data-attachment-id="' . esc_attr($attachment_id) . '">';
                            echo '<img src="' . esc_url($image_url) . '" alt="" />';
                            echo '<button type="button" class="batumizone-remove-image">&times;</button>';
                            echo '</div>';
                        }
                    }
                }
                ?>
            </div>

            <p>
                <button type="button" class="button button-primary" id="batumizone-add-gallery-image">
                    <?php _e('Add Images', 'batumizone'); ?>
                </button>
            </p>

            <input type="hidden" id="batumizone-gallery-ids" name="batumizone_gallery_ids" value="<?php echo esc_attr(implode(',', $gallery_ids)); ?>" />
        </div>

        <style>
            .batumizone-gallery-sortable {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
                gap: 10px;
                margin: 15px 0;
                padding: 10px;
                background: #f5f5f5;
                border: 1px solid #ddd;
                border-radius: 4px;
                min-height: 60px;
            }
            .batumizone-gallery-image {
                position: relative;
                cursor: move;
                border: 2px solid #ddd;
                border-radius: 4px;
                overflow: hidden;
                background: white;
            }
            .batumizone-gallery-image img {
                width: 100%;
                height: 120px;
                object-fit: cover;
                display: block;
            }
            .batumizone-remove-image {
                position: absolute;
                top: 5px;
                right: 5px;
                background: rgba(255, 0, 0, 0.8);
                color: white;
                border: none;
                width: 24px;
                height: 24px;
                border-radius: 50%;
                cursor: pointer;
                font-size: 18px;
                line-height: 1;
                padding: 0;
            }
            .batumizone-remove-image:hover {
                background: rgba(255, 0, 0, 1);
            }
        </style>
        <?php
    }

    /**
     * Save gallery order
     */
    public function save_gallery_order($post_id, $post) {
        // Check nonce
        if (!isset($_POST['batumizone_gallery_nonce']) ||
            !wp_verify_nonce($_POST['batumizone_gallery_nonce'], 'batumizone_gallery_nonce')) {
            return;
        }

        // Check autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Check permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Save gallery IDs
        if (isset($_POST['batumizone_gallery_ids'])) {
            $gallery_ids = sanitize_text_field($_POST['batumizone_gallery_ids']);
            update_post_meta($post_id, '_gallery_image_ids', $gallery_ids);
        }
    }

    /**
     * Enqueue gallery scripts
     */
    public function enqueue_gallery_scripts($hook) {
        global $post;

        if (($hook === 'post.php' || $hook === 'post-new.php') &&
            isset($post) && $post->post_type === 'service_listing') {

            wp_enqueue_media();
            wp_enqueue_script('jquery-ui-sortable');

            wp_add_inline_script('jquery-ui-sortable', $this->get_gallery_script());
        }
    }

    /**
     * Get gallery JavaScript
     */
    private function get_gallery_script() {
        return "
        jQuery(document).ready(function($) {
            var galleryFrame;

            // Make gallery sortable
            $('#batumizone-gallery-images').sortable({
                update: function() {
                    updateGalleryIds();
                }
            });

            // Add images
            $('#batumizone-add-gallery-image').on('click', function(e) {
                e.preventDefault();

                if (galleryFrame) {
                    galleryFrame.open();
                    return;
                }

                galleryFrame = wp.media({
                    title: 'Select Images',
                    button: { text: 'Add to Gallery' },
                    multiple: true,
                    library: { type: 'image' }
                });

                galleryFrame.on('select', function() {
                    var selection = galleryFrame.state().get('selection');
                    var currentCount = $('#batumizone-gallery-images .batumizone-gallery-image').length;

                    if (currentCount + selection.length > " . self::MAX_IMAGES_PER_LISTING . ") {
                        alert('Maximum " . self::MAX_IMAGES_PER_LISTING . " images allowed.');
                        return;
                    }

                    selection.each(function(attachment) {
                        attachment = attachment.toJSON();
                        var imageHtml = '<div class=\"batumizone-gallery-image\" data-attachment-id=\"' + attachment.id + '\">' +
                            '<img src=\"' + attachment.sizes.thumbnail.url + '\" alt=\"\" />' +
                            '<button type=\"button\" class=\"batumizone-remove-image\">&times;</button>' +
                            '</div>';
                        $('#batumizone-gallery-images').append(imageHtml);
                    });

                    updateGalleryIds();
                });

                galleryFrame.open();
            });

            // Remove image
            $(document).on('click', '.batumizone-remove-image', function() {
                $(this).parent().remove();
                updateGalleryIds();
            });

            function updateGalleryIds() {
                var ids = [];
                $('#batumizone-gallery-images .batumizone-gallery-image').each(function() {
                    ids.push($(this).data('attachment-id'));
                });
                $('#batumizone-gallery-ids').val(ids.join(','));
            }
        });
        ";
    }

    /**
     * Register REST API routes for media
     */
    public function register_media_routes() {
        // Get gallery images for a service
        register_rest_route('batumizone/v1', '/services/(?P<id>\d+)/gallery', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_gallery_images'),
            'permission_callback' => '__return_true',
        ));
    }

    /**
     * Get gallery images via REST API
     */
    public function get_gallery_images($request) {
        $post_id = $request->get_param('id');

        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'service_listing' || $post->post_status !== 'publish') {
            return new WP_Error('not_found', 'Service not found', array('status' => 404));
        }

        $gallery_ids = get_post_meta($post_id, '_gallery_image_ids', true);
        $gallery_ids = !empty($gallery_ids) ? explode(',', $gallery_ids) : array();

        $images = array();
        foreach ($gallery_ids as $attachment_id) {
            $attachment_id = intval($attachment_id);
            if ($attachment_id) {
                $images[] = array(
                    'id' => $attachment_id,
                    'thumbnail' => wp_get_attachment_image_url($attachment_id, 'thumbnail'),
                    'medium' => wp_get_attachment_image_url($attachment_id, 'medium'),
                    'large' => wp_get_attachment_image_url($attachment_id, 'large'),
                    'full' => wp_get_attachment_image_url($attachment_id, 'full'),
                    'alt' => get_post_meta($attachment_id, '_wp_attachment_image_alt', true),
                );
            }
        }

        return new WP_REST_Response($images, 200);
    }

    /**
     * Get gallery images for a post (helper function)
     */
    public function get_post_gallery_images($post_id) {
        $gallery_ids = get_post_meta($post_id, '_gallery_image_ids', true);
        $gallery_ids = !empty($gallery_ids) ? explode(',', $gallery_ids) : array();

        $images = array();
        foreach ($gallery_ids as $attachment_id) {
            $attachment_id = intval($attachment_id);
            if ($attachment_id) {
                $images[] = array(
                    'id' => $attachment_id,
                    'url' => wp_get_attachment_image_url($attachment_id, 'large'),
                    'thumbnail' => wp_get_attachment_image_url($attachment_id, 'thumbnail'),
                );
            }
        }

        return $images;
    }
}
