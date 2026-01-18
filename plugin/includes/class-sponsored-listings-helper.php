<?php
// Helper functions for Sponsored Listings - included in main class

/**
 * Update post meta for promoted service
 */
private function set_promotion_meta($service_id, $priority) {
    update_post_meta($service_id, "_is_promoted", 1);
    update_post_meta($service_id, "_promotion_priority", $priority);
    update_post_meta($service_id, "_promotion_updated", current_time("timestamp"));
}

/**
 * Remove promotion meta from service
 */
private function remove_promotion_meta($service_id) {
    delete_post_meta($service_id, "_is_promoted");
    delete_post_meta($service_id, "_promotion_priority");
    update_post_meta($service_id, "_promotion_updated", current_time("timestamp"));
}
