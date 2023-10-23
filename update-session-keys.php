<?php

require_once('../../../wp-load.php');

/** 
 * Handle AJAX request to update Paystack Secret and 
 * Public Keys.
 */

$log_message = "REQUEST CAME THROUGH:>\n\n";
error_log($log_message, 3, WP_CONTENT_DIR . '/plugins/custom-paystack-plugin/custom_keys_log.txt');

// Nonce verification
if (!isset($_POST['security'])) {
    die('Permission check failed.');
}

// Handle AJAX request
function handle_ajax_request() {
    // Check if it's a POST request with the expected action
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['paystack_public_key']) && isset($_POST['paystack_secret_key'])) {

        $data = $_POST['data'];

        // Get the new values from the AJAX request
        $paystack_public_key = sanitize_text_field($_POST['paystack_public_key']);
        $paystack_secret_key = sanitize_text_field($_POST['paystack_secret_key']);

        // Update the session variables
        // Start session
        if (!session_id()) {
            session_start();
        }
        $_SESSION['paystack_public_key'] = $paystack_public_key;
        $_SESSION['paystack_secret_key'] = $paystack_secret_key;

        // Optional: Respond with a confirmation message
        wp_send_json_success($data);
    } else {
        // Prevent further execution
        exit;
    }
}

add_action('wp_ajax_update_session_variable', 'handle_ajax_request');
