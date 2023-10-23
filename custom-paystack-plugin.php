<?php
/*
Plugin Name: Custom Paystack Plugin
Description: Dynamically change Paystack secret key based on selected store.
Version: 1.0
Author: Noah Olatoye
*/

// Hook to add custom actions when WooCommerce initializes
// add_action('woocommerce_init', 'custom_paystack_init');

if (!session_id()) {
    session_start();
}

// Hook to add custom actions when WooCommerce initializes
add_action('woocommerce_init', 'custom_paystack_init');

function custom_paystack_init() {
    // Hook to add custom action when processing the checkout
    add_action('woocommerce_checkout_process', 'custom_payment_scripts');

    // Hook to add custom settings page
    add_action('admin_menu', 'custom_paystack_add_admin_page');
}

function custom_paystack_add_admin_page() {
    add_menu_page(
        'Custom Paystack Settings',
        'Paystack Settings',
        'manage_options',
        'custom_paystack_settings',
        'custom_paystack_settings_page'
    );
}

function custom_paystack_settings_page() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    // Save settings when form is submitted
    if (isset($_POST['custom_paystack_submit'])) {
        $custom_settings = array();

        // Retrieve and sanitize input values
        $store_names = isset($_POST['store_names']) ? array_map('sanitize_text_field', $_POST['store_names']) : array();
        $secret_key = isset($_POST['secret_key']) ? array_map('sanitize_text_field', $_POST['secret_key']) : array();
        $public_key = isset($_POST['public_key']) ? array_map('sanitize_text_field', $_POST['public_key']) : array();

        // Combine store names, secret keys, and public keys into an associative array
        foreach ($store_names as $index => $store_name) {
            if (!empty($store_name) && isset($secret_key[$index]) && isset($public_key[$index])) {
                $custom_settings[$store_name] = array(
                    'secret_key' => $secret_key[$index],
                    'public_key' => $public_key[$index],
                );
            }
        }

        // Update the custom settings
        update_option('custom_paystack_settings', $custom_settings);
    }

    // Retrieve existing custom settings
    $custom_settings = get_option('custom_paystack_settings', array());

    ?>
    <div class="wrap">
        <h1>Custom Paystack Settings</h1>

        <form method="post" action="">
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="store_names">Store Name</label></th>
                    <th scope="row"><label for="secret_key">Secret Key</label></th>
                    <th scope="row"><label for="public_key">Public Key</label></th>
                    <th></th> <!-- Add a new column for delete buttons -->
                </tr>
                <?php foreach ($custom_settings as $store_name => $keys) : ?>
                    <tr>
                        <td><input type="text" name="store_names[]" value="<?php echo esc_attr($store_name); ?>" /></td>
                        <td><input type="text" name="secret_key[]" value="<?php echo esc_attr($keys['secret_key']); ?>" /></td>
                        <td><input type="text" name="public_key[]" value="<?php echo esc_attr($keys['public_key']); ?>" /></td>
                        <td><a href="?page=custom_paystack_settings&action=delete&store=<?php echo esc_attr($store_name); ?>" onclick="return confirm('Are you sure you want to delete this store?');">Delete</a></td>
                    </tr>
                <?php endforeach; ?>
                <tr>
                    <td><input type="text" name="store_names[]" /></td>
                    <td><input type="text" name="secret_key[]" /></td>
                    <td><input type="text" name="public_key[]" /></td>
                    <td></td>
                </tr>
            </table>

            <p class="submit">
                <input type="submit" name="custom_paystack_submit" class="button-primary" value="Save Changes" />
            </p>
        </form>
    </div>
    <?php
}

function get_selected_store() {
    // Check if it's an order confirmation page
    if (is_order_received_page()) {
        // Get the current user's ID
        $user_id = get_current_user_id();

        // Get the most recent order for the user
        $args = array(
            'numberposts' => 1,
            'meta_key'    => '_customer_user',
            'meta_value'  => $user_id,
            'post_type'   => 'shop_order',
            'post_status' => 'wc-pending', // Adjust the order status as needed
            'orderby'     => 'post_date',
            'order'       => 'DESC',
        );

        $orders = get_posts($args);

        // If an order is found, retrieve the store information
        if (!empty($orders)) {
            $order_id = $orders[0]->ID;

            // Adjust the meta key to match the custom field storing the selected store
            $selected_store = get_post_meta($order_id, 'billing_stores', true);

            // $log_message = "get_selected_store:\n" . print_r($selected_store, true) . "\n\n";
            // error_log($log_message, 3, WP_CONTENT_DIR . '/plugins/custom-paystack-plugin/custom_keys_log.txt');

            return sanitize_text_field($selected_store);
        }
    }

    return 'IKEJA'; // Replace with your default logic
}


/**
  This code adds a hidden input field with the name 
  paystack_secret_key to the WooCommerce checkout page.
*/ 

function get_paystack_keys_by_store($store) {
    // Implement your logic to get the Paystack keys based on the selected store
    // For example, you might use a mapping or database lookup
    $custom_settings = get_option('custom_paystack_settings', array());

    if (isset($custom_settings[$store])) {
        return $custom_settings[$store];
    } else {
        // Return default keys or handle the case when keys for the store are not found
        return array(
            'secret_key' => 'sk_test_618fee7c455204bc0a41c6700678fe91a9352b4c',
            'public_key' => 'pk_test_9836cae333b6607bb42aac46ba74f45865475db3',
        );
    }
}

add_action('woocommerce_checkout_after_customer_details', 'add_paystack_secret_key_field');

function add_paystack_secret_key_field() {
    // Get the selected store
    $selected_store = get_selected_store();

    // Get Paystack keys based on the selected store
    $paystack_settings = get_paystack_keys_by_store($selected_store);

    // Output the hidden input fields with the retrieved keys
    echo '<input type="hidden" name="paystack_secret_key" value="' . esc_attr($paystack_settings['secret_key']) . '" />';
    echo '<input type="hidden" name="paystack_public_key" value="' . esc_attr($paystack_settings['public_key']) . '" />';
}

// Create a function to handle payment scripts and keys
function custom_payment_scripts() {
    // Get the selected store
    $selected_store = get_selected_store();

    // Get Paystack keys based on the selected store
    $paystack_keys = get_paystack_keys_by_store($selected_store);

    $paystack_secret_key = isset($_POST['paystack_secret_key']) ? sanitize_text_field($_POST['paystack_secret_key']) : '';
    $paystack_public_key = isset($_POST['paystack_public_key']) ? sanitize_text_field($_POST['paystack_public_key']) : '';

    // Log the selected keys
     // Log the selected keys
    // $log_message = "Headers:\n" . implode("\n", $paystack_keys) . "\n\n";
    // $log_message .= "- Secret Key: $paystack_secret_key\n";
    // $log_message .= "- Public Key: $paystack_public_key\n";
    // error_log($log_message, 3, WP_CONTENT_DIR . '/custom_keys_log.txt');

    $_SESSION['paystack_public_key'] = 'pk_test_9836cae333b6607bb42aac46ba74f45865475db3';

    if($paystack_secret_key){
        $_SESSION['paystack_secret_key'] = $paystack_secret_key;
        $_SESSION['paystack_public_key'] = $paystack_public_key;
    }

    return array(
        'secret_key' => $paystack_secret_key,
        'public_key' => $paystack_public_key,
    );
}

// Hook to replace the existing payment_scripts function in the Paystack official plugin
remove_action('wp_enqueue_scripts', array('WC_Gateway_Paystack', 'payment_scripts'));
add_action('woocommerce_checkout_process', 'custom_payment_scripts');

// Add a function to update Paystack keys
function update_paystack_keys($secret_key, $public_key) {
    $paystack_gateway = WC()->payment_gateways()->payment_gateways()['paystack']; // Adjust 'paystack' if necessary

    // $log_message = "update_paystack_keys:\n" . implode("\n", $paystack_gateway) . "\n\n";
    // error_log($log_message, 3, WP_CONTENT_DIR . '/custom_keys_log.txt');
    
    $paystack_gateway->secret_key = $secret_key;
    $paystack_gateway->public_key = $public_key;
}

/**
  The validate_billing_stores_field function is hooked to woocommerce_checkout_process. 
  It checks if the billing_stores field is set in the $_POST data and if it's empty. 
  If it is, it adds an error notice to indicate that the field is required.
*/

// Hook to add custom validation rule for billing_stores
add_action('woocommerce_checkout_process', 'validate_billing_stores_field');

function validate_billing_stores_field() {
    // Check if billing_stores is set and not empty
    if (empty($_POST['billing_stores'])) {
        wc_add_notice(__('Please select a store.'), 'error');
    }
}

// Hook to add an asterisk (*) to indicate required field in the checkout form
add_filter('woocommerce_checkout_fields', 'add_required_billing_stores');

function add_required_billing_stores($fields) {
    // Set billing_stores as required
    $fields['billing']['billing_stores']['required'] = true;

    return $fields;
}


/**
  This code enqueues a script named custom-paystack-script.js and 
  passes the Paystack settings to it using wp_localize_script. 
  You should create this script in your theme directory.
*/

add_action('wp_enqueue_scripts', 'enqueue_custom_paystack_script');

function enqueue_custom_paystack_script() {
    if (is_checkout()) {
        wp_enqueue_script('custom-paystack-script', plugin_dir_url(__FILE__) . 'assets/js/custom-paystack-script.js', array('jquery'), null, true);

        // Pass the Paystack settings to the script
        $paystack_settings = get_option('custom_paystack_settings', array());
        wp_localize_script('custom-paystack-script', 'paystackSettings', $paystack_settings);
    }
}


add_action('wp_enqueue_scripts', 'enqueue_stores_toggle');

function enqueue_stores_toggle() {
    // if (is_checkout()) {
        // Enqueue the JavaScript script
        wp_enqueue_script('stores-toggle', plugin_dir_url(__FILE__) . 'assets/js/stores-toggle.js', array('jquery'), null, true);

    // }
}

// Hook to enqueue front-end styles
add_action('wp_enqueue_scripts', 'enqueue_custom_plugin_styles');

function enqueue_custom_plugin_styles() {
    // Enqueue your custom CSS file
    wp_enqueue_style('custom-plugin-styles', plugin_dir_url(__FILE__) . 'assets/css/custom-styles.css');
}
