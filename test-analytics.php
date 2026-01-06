<?php
/**
 * Temporary Analytics Test
 * Add this code to test analytics, then remove it.
 */

add_action('admin_notices', function() {
    if (!current_user_can('manage_options')) {
        return;
    }

    if (isset($_GET['test_wpseopilot_analytics'])) {
        $analytics = \WPSEOPilot\Plugin::instance()->get('analytics');

        if (!$analytics) {
            echo '<div class="notice notice-error"><p><strong>Analytics service not found!</strong></p></div>';
            return;
        }

        $result = $analytics->test_tracking();

        echo '<div class="notice notice-' . ($result['success'] ? 'success' : 'error') . '" style="padding: 20px;">';
        echo '<h2>WP SEO Pilot Analytics Test</h2>';
        echo '<p><strong>Status:</strong> ' . ($result['success'] ? '✅ Request Sent' : '❌ Failed') . '</p>';

        if (!$result['success']) {
            echo '<p><strong>Error:</strong> ' . esc_html($result['error']) . '</p>';
        } else {
            echo '<p><strong>Response Code:</strong> ' . esc_html($result['code']) . '</p>';
            echo '<p><strong>Response Body:</strong> <code>' . esc_html(substr($result['body'], 0, 200)) . '</code></p>';
        }

        echo '<p><strong>Tracking URL:</strong><br><small>' . esc_html($result['url']) . '</small></p>';

        echo '<hr>';
        echo '<p><em>If response code is 200 or 204, the request was successful. Check your Matomo dashboard.</em></p>';
        echo '</div>';
    }
});
