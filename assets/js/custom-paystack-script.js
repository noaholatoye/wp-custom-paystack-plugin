/**
 * This script uses jQuery to handle the change event of
 * the billing_stores radio input. It checks if the selected
 * store exists in the Paystack settings passed from the server
 * and updates the Paystack secret key field on the checkout
 * page accordingly.
 */

jQuery(document).ready(function ($) {
	// Handle change event of billing_stores radio input
	$('input[name="billing_stores"]').change(function () {
		var selectedStore = $(this).val();

		// Check if the selected store exists in the Paystack settings
		if (paystackSettings.hasOwnProperty(selectedStore)) {
			var keys = paystackSettings[selectedStore];

			// Update the Paystack secret key and public key fields on the checkout page
			$('input[name="paystack_secret_key"]').val(keys.secret_key);
			$('input[name="paystack_public_key"]').val(keys.public_key);

			// Trigger a custom event to notify other scripts about the key update
			$(document).trigger("paystackKeysUpdated", [
				keys.secret_key,
				keys.public_key,
			]);

			// Send AJAX request to update session.
			const ajax_nonce = `<?php echo wp_create_nonce('update_session_nonce'); ?>`;

			jQuery.ajax({
				url: "/wp-content/plugins/custom-paystack-plugin/update-session-keys.php",
				type: "POST",
				data: {
					action: "wp_ajax_update_session_variable",
					paystack_secret_key: keys.secret_key,
					paystack_public_key: keys.public_key,
					security: ajax_nonce,
				},
				success: function (response) {
					// Optional: Handle the response from the server
					console.log(response);
				},
			});
		}
	});
});
