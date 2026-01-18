<?php
/**
 * WebP Image Converter
 *
 * Converts uploaded images to WebP format and manages image optimization.
 *
 * @package Batumi_Zone_Core
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Batumi_WebP_Converter {

    /**
     * WebP quality setting (0-100)
     */
    private $quality = 85;

    /**
     * Whether to delete original images after conversion
     */
    private $delete_originals = true;

    /**
     * Supported image types for conversion
     */
    private $supported_types = array('image/jpeg', 'image/png', 'image/gif');

    /**
     * Constructor
     */
    public function __construct() {
        // Hook into WordPress upload process
        add_filter('wp_handle_upload', array($this, 'convert_on_upload'), 10, 2);

        // Hook into attachment deletion to clean up WebP files
        add_action('delete_attachment', array($this, 'delete_webp_files'), 10, 1);

        // Register REST API endpoint for bulk conversion
        add_action('rest_api_init', array($this, 'register_routes'));

        // Register WP-CLI commands if available
        if (defined('WP_CLI') && WP_CLI) {
            WP_CLI::add_command('batumi webp', array($this, 'cli_convert'));
        }
    }

    /**
     * Register REST API routes
     */
    public function register_routes() {
        register_rest_route('batumizone/v1', '/webp/convert', array(
            'methods'             => 'POST',
            'callback'            => array($this, 'api_bulk_convert'),
            'permission_callback' => array($this, 'can_manage_options'),
        ));

        register_rest_route('batumizone/v1', '/webp/status', array(
            'methods'             => 'GET',
            'callback'            => array($this, 'api_get_status'),
            'permission_callback' => array($this, 'can_manage_options'),
        ));

        register_rest_route('batumizone/v1', '/webp/cleanup', array(
            'methods'             => 'POST',
            'callback'            => array($this, 'api_cleanup_originals'),
            'permission_callback' => array($this, 'can_manage_options'),
        ));
    }

    /**
     * Permission check for admin operations
     */
    public function can_manage_options() {
        return current_user_can('manage_options');
    }

    /**
     * Convert image on upload
     *
     * @param array $upload Upload data from WordPress
     * @param string $context Upload context
     * @return array Modified upload data
     */
    public function convert_on_upload($upload, $context) {
        // Only process images
        if (!isset($upload['type']) || !in_array($upload['type'], $this->supported_types)) {
            return $upload;
        }

        // Don't process if already WebP
        if ($upload['type'] === 'image/webp') {
            return $upload;
        }

        $original_file = $upload['file'];
        $webp_file = $this->convert_to_webp($original_file);

        if ($webp_file && file_exists($webp_file)) {
            // Update upload data to point to WebP file
            $upload['file'] = $webp_file;
            $upload['url'] = str_replace(
                wp_basename($original_file),
                wp_basename($webp_file),
                $upload['url']
            );
            $upload['type'] = 'image/webp';

            // Delete original if configured
            if ($this->delete_originals && file_exists($original_file)) {
                @unlink($original_file);
            }

            // Log conversion
            $this->log('Converted: ' . wp_basename($original_file) . ' -> ' . wp_basename($webp_file));
        }

        return $upload;
    }

    /**
     * Convert a single image to WebP
     *
     * @param string $source_path Full path to source image
     * @return string|false Path to WebP file or false on failure
     */
    public function convert_to_webp($source_path) {
        if (!file_exists($source_path)) {
            $this->log('Source file not found: ' . $source_path);
            return false;
        }

        // Get image info
        $image_info = @getimagesize($source_path);
        if (!$image_info) {
            $this->log('Invalid image: ' . $source_path);
            return false;
        }

        $mime_type = $image_info['mime'];

        // Generate WebP filename
        $path_info = pathinfo($source_path);
        $webp_path = $path_info['dirname'] . '/' . $path_info['filename'] . '.webp';

        // Skip if WebP already exists
        if (file_exists($webp_path)) {
            return $webp_path;
        }

        // Try Imagick first (better quality)
        if (class_exists('Imagick')) {
            try {
                $imagick = new Imagick($source_path);
                $imagick->setImageFormat('webp');
                $imagick->setImageCompressionQuality($this->quality);

                // Optimize for web
                $imagick->stripImage();

                // Write WebP file
                $imagick->writeImage($webp_path);
                $imagick->destroy();

                if (file_exists($webp_path)) {
                    return $webp_path;
                }
            } catch (Exception $e) {
                $this->log('Imagick conversion failed: ' . $e->getMessage());
            }
        }

        // Fallback to GD
        if (function_exists('imagecreatefromjpeg') && function_exists('imagewebp')) {
            $image = null;

            switch ($mime_type) {
                case 'image/jpeg':
                    $image = @imagecreatefromjpeg($source_path);
                    break;
                case 'image/png':
                    $image = @imagecreatefrompng($source_path);
                    // Handle transparency
                    if ($image) {
                        imagepalettetotruecolor($image);
                        imagealphablending($image, true);
                        imagesavealpha($image, true);
                    }
                    break;
                case 'image/gif':
                    $image = @imagecreatefromgif($source_path);
                    break;
            }

            if ($image) {
                $success = @imagewebp($image, $webp_path, $this->quality);
                imagedestroy($image);

                if ($success && file_exists($webp_path)) {
                    return $webp_path;
                }
            }
        }

        $this->log('Conversion failed for: ' . $source_path);
        return false;
    }

    /**
     * Bulk convert existing images
     *
     * @param int $batch_size Number of images to process
     * @param int $offset Starting offset
     * @return array Results of conversion
     */
    public function bulk_convert($batch_size = 50, $offset = 0) {
        global $wpdb;

        // Get attachments that are not WebP
        $attachments = $wpdb->get_results($wpdb->prepare(
            "SELECT ID, guid FROM {$wpdb->posts}
            WHERE post_type = 'attachment'
            AND post_mime_type IN ('image/jpeg', 'image/png', 'image/gif')
            ORDER BY ID ASC
            LIMIT %d OFFSET %d",
            $batch_size,
            $offset
        ));

        $results = array(
            'processed' => 0,
            'converted' => 0,
            'failed' => 0,
            'skipped' => 0,
            'details' => array(),
        );

        foreach ($attachments as $attachment) {
            $file_path = get_attached_file($attachment->ID);

            if (!$file_path || !file_exists($file_path)) {
                $results['skipped']++;
                continue;
            }

            $results['processed']++;

            // Convert main image
            $webp_path = $this->convert_to_webp($file_path);

            if ($webp_path && file_exists($webp_path)) {
                // Update attachment metadata
                $this->update_attachment_to_webp($attachment->ID, $file_path, $webp_path);
                $results['converted']++;
                $results['details'][] = array(
                    'id' => $attachment->ID,
                    'original' => wp_basename($file_path),
                    'webp' => wp_basename($webp_path),
                    'status' => 'converted',
                );
            } else {
                $results['failed']++;
                $results['details'][] = array(
                    'id' => $attachment->ID,
                    'original' => wp_basename($file_path),
                    'status' => 'failed',
                );
            }
        }

        // Get total count for progress
        $total = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->posts}
            WHERE post_type = 'attachment'
            AND post_mime_type IN ('image/jpeg', 'image/png', 'image/gif')"
        );

        $results['total'] = intval($total);
        $results['remaining'] = max(0, intval($total) - $offset - $batch_size);

        return $results;
    }

    /**
     * Update attachment metadata after WebP conversion
     *
     * @param int $attachment_id Attachment ID
     * @param string $original_path Original file path
     * @param string $webp_path WebP file path
     */
    private function update_attachment_to_webp($attachment_id, $original_path, $webp_path) {
        global $wpdb;

        // Update attached file path
        update_attached_file($attachment_id, $webp_path);

        // Update mime type
        $wpdb->update(
            $wpdb->posts,
            array('post_mime_type' => 'image/webp'),
            array('ID' => $attachment_id)
        );

        // Update guid
        $new_url = str_replace(
            wp_basename($original_path),
            wp_basename($webp_path),
            wp_get_attachment_url($attachment_id)
        );
        $wpdb->update(
            $wpdb->posts,
            array('guid' => $new_url),
            array('ID' => $attachment_id)
        );

        // Update attachment metadata
        $metadata = wp_get_attachment_metadata($attachment_id);
        if ($metadata && isset($metadata['file'])) {
            // Update main file reference
            $metadata['file'] = str_replace(
                wp_basename($original_path),
                wp_basename($webp_path),
                $metadata['file']
            );

            // Convert thumbnails
            if (!empty($metadata['sizes'])) {
                foreach ($metadata['sizes'] as $size => $size_data) {
                    $size_original = dirname($original_path) . '/' . $size_data['file'];
                    $size_webp = $this->convert_to_webp($size_original);

                    if ($size_webp && file_exists($size_webp)) {
                        $metadata['sizes'][$size]['file'] = wp_basename($size_webp);
                        $metadata['sizes'][$size]['mime-type'] = 'image/webp';

                        // Delete original thumbnail
                        if ($this->delete_originals && file_exists($size_original)) {
                            @unlink($size_original);
                        }
                    }
                }
            }

            wp_update_attachment_metadata($attachment_id, $metadata);
        }

        // Delete original file
        if ($this->delete_originals && file_exists($original_path)) {
            @unlink($original_path);
        }

        // Clear any caches
        clean_post_cache($attachment_id);
    }

    /**
     * Delete WebP files when attachment is deleted
     *
     * @param int $attachment_id Attachment ID
     */
    public function delete_webp_files($attachment_id) {
        $file = get_attached_file($attachment_id);

        if ($file && pathinfo($file, PATHINFO_EXTENSION) === 'webp') {
            // File is already WebP, WordPress will handle deletion
            return;
        }

        // Also try to delete WebP version if exists
        if ($file) {
            $webp_file = preg_replace('/\.(jpe?g|png|gif)$/i', '.webp', $file);
            if (file_exists($webp_file)) {
                @unlink($webp_file);
            }
        }
    }

    /**
     * Get conversion status/statistics
     *
     * @return array Status information
     */
    public function get_status() {
        global $wpdb;

        $stats = array();

        // Count by mime type
        $counts = $wpdb->get_results(
            "SELECT post_mime_type, COUNT(*) as count
            FROM {$wpdb->posts}
            WHERE post_type = 'attachment'
            AND post_mime_type LIKE 'image/%'
            GROUP BY post_mime_type"
        );

        $stats['by_type'] = array();
        $stats['total_images'] = 0;
        $stats['webp_count'] = 0;
        $stats['convertible'] = 0;

        foreach ($counts as $row) {
            $stats['by_type'][$row->post_mime_type] = intval($row->count);
            $stats['total_images'] += intval($row->count);

            if ($row->post_mime_type === 'image/webp') {
                $stats['webp_count'] = intval($row->count);
            } elseif (in_array($row->post_mime_type, $this->supported_types)) {
                $stats['convertible'] += intval($row->count);
            }
        }

        // Calculate percentage
        $stats['webp_percentage'] = $stats['total_images'] > 0
            ? round(($stats['webp_count'] / $stats['total_images']) * 100, 1)
            : 0;

        // Disk space analysis (sample-based for performance)
        $sample = $wpdb->get_results(
            "SELECT ID FROM {$wpdb->posts}
            WHERE post_type = 'attachment'
            AND post_mime_type IN ('image/jpeg', 'image/png', 'image/gif')
            ORDER BY RAND()
            LIMIT 10"
        );

        $potential_savings = 0;
        $sample_count = 0;

        foreach ($sample as $attachment) {
            $file = get_attached_file($attachment->ID);
            if ($file && file_exists($file)) {
                $original_size = filesize($file);
                // Estimate WebP will be ~30% smaller on average
                $potential_savings += $original_size * 0.3;
                $sample_count++;
            }
        }

        if ($sample_count > 0) {
            $avg_savings = $potential_savings / $sample_count;
            $stats['estimated_savings'] = $this->format_bytes($avg_savings * $stats['convertible']);
        } else {
            $stats['estimated_savings'] = '0 B';
        }

        return $stats;
    }

    /**
     * API endpoint for bulk conversion
     */
    public function api_bulk_convert($request) {
        $batch_size = isset($request['batch_size']) ? intval($request['batch_size']) : 50;
        $offset = isset($request['offset']) ? intval($request['offset']) : 0;

        $results = $this->bulk_convert($batch_size, $offset);

        return new WP_REST_Response($results, 200);
    }

    /**
     * API endpoint for status
     */
    public function api_get_status($request) {
        return new WP_REST_Response($this->get_status(), 200);
    }

    /**
     * API endpoint to cleanup remaining originals
     */
    public function api_cleanup_originals($request) {
        $upload_dir = wp_upload_dir();
        $base_dir = $upload_dir['basedir'];

        $cleaned = 0;
        $freed_space = 0;

        // Find original files that have WebP versions
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($base_dir, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (!$file->isFile()) continue;

            $ext = strtolower($file->getExtension());
            if (!in_array($ext, array('jpg', 'jpeg', 'png', 'gif'))) continue;

            $webp_path = preg_replace('/\.(jpe?g|png|gif)$/i', '.webp', $file->getPathname());

            if (file_exists($webp_path)) {
                $size = $file->getSize();
                if (@unlink($file->getPathname())) {
                    $cleaned++;
                    $freed_space += $size;
                }
            }
        }

        return new WP_REST_Response(array(
            'cleaned' => $cleaned,
            'freed_space' => $this->format_bytes($freed_space),
            'freed_bytes' => $freed_space,
        ), 200);
    }

    /**
     * Format bytes to human-readable string
     */
    private function format_bytes($bytes) {
        $units = array('B', 'KB', 'MB', 'GB');
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Log message for debugging
     */
    private function log($message) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[Batumi WebP] ' . $message);
        }
    }

    /**
     * WP-CLI command for bulk conversion
     */
    public function cli_convert($args, $assoc_args) {
        $batch_size = isset($assoc_args['batch']) ? intval($assoc_args['batch']) : 50;
        $offset = 0;
        $total_converted = 0;
        $total_failed = 0;

        WP_CLI::log('Starting WebP conversion...');

        do {
            $results = $this->bulk_convert($batch_size, $offset);

            $total_converted += $results['converted'];
            $total_failed += $results['failed'];

            WP_CLI::log(sprintf(
                'Batch: %d processed, %d converted, %d failed',
                $results['processed'],
                $results['converted'],
                $results['failed']
            ));

            $offset += $batch_size;

        } while ($results['remaining'] > 0);

        WP_CLI::success(sprintf(
            'Conversion complete. %d converted, %d failed.',
            $total_converted,
            $total_failed
        ));
    }
}

// Initialize the WebP converter
new Batumi_WebP_Converter();
