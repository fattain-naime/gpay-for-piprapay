<?php
// Metadata describing this payment gateway plugin. The values here are displayed
// throughout the PipraPay admin area and should accurately describe the plugin.
$plugin_meta = [
    'Plugin Name' => 'Google Pay',
    'Description' => 'Google Pay DIRECT Payment Gateway for PipraPay',
    'Version' => '1.3.1',
    'Author' => 'Fattain Naime'
];

/**
 * Render the admin settings page for this gateway.
 *
 * The function name must be unique to avoid collisions with other gateways.
 * Because the plugin slug contains a hyphen ("gpay-direct"), replace
 * hyphens with underscores when naming functions.
 */
function gpay_direct_admin_page()
{
    $viewFile = __DIR__ . '/views/admin-ui.php';
    if (file_exists($viewFile)) {
        include $viewFile;
    } else {
        echo '<div class="alert alert-warning">Admin UI not found.</div>';
    }
}

/**
 * Render the checkout page for this gateway.
 *
 * This function accepts a payment ID and loads the checkout view. As with
 * gpay_direct_admin_page(), ensure the function name is unique by
 * replacing hyphens in the plugin slug with underscores.
 *
 * @param int $payment_id The ID of the payment being processed
 */
function gpay_direct_checkout_page($payment_id)
{
    $viewFile = __DIR__ . '/views/checkout-ui.php';
    if (file_exists($viewFile)) {
        include $viewFile;
    } else {
        echo '<div class="alert alert-warning">Checkout UI not found.</div>';
    }
}
