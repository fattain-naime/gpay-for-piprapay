<?php
header('Content-Type: application/json');

// Include dependencies
$bootstrap = __DIR__ . '/../../../../pp-include/pp-controller.php';
if (file_exists($bootstrap)) {
    require_once $bootstrap;
} else {
    $config_only = __DIR__ . '/../../../../pp-config.php';
    if (file_exists($config_only)) {
        require_once $config_only;
    }
    echo json_encode(['ok' => false, 'error' => 'System bootstrap file missing (pp-controller.php)']);
    exit;
}
require_once __DIR__ . '/ecv2-decrypt-stub.php';

// Get plugin settings
$plugin_slug = 'gpay-direct';
$settings = pp_get_plugin_setting($plugin_slug);

if (!is_array($settings)) {
    echo json_encode(['ok' => false, 'error' => 'Google Pay configuration is missing or invalid']);
    exit;
}

$display_name = $settings['display_name'] ?? 'Google Pay';

// Get POST data
$payment_id = intval($_POST['payment_id'] ?? 0);
$token = $_POST['gpay_token'] ?? '';
$mode = isset($settings['gpay_mode']) ? strtolower((string)$settings['gpay_mode']) : 'sandbox';

if (!function_exists('pp_get_paymentlink')) {
    echo json_encode(['ok' => false, 'error' => 'Payment link helper not available']);
    exit;
}

if (!$payment_id || !$token) {
    echo json_encode(['ok' => false, 'error' => 'Missing payment_id or token']);
    exit;
}

// Handle sandbox mode
if ($mode !== 'live') {
    $txn_digest = substr(hash('sha256', $token), 0, 18);
    $auth_id = 'TEST-AUTH-' . substr(hash('sha1', $txn_digest . microtime(true)), 0, 10);
    if (function_exists('pp_set_transaction_byid') && pp_set_transaction_byid($payment_id, $plugin_slug, $display_name, 'Sandbox User', $auth_id, 'completed', 'Sandbox simulated authorisation: ' . $auth_id)) {
        echo json_encode(['ok' => true, 'redirect' => pp_get_paymentlink($payment_id)]);
        exit;
    } else {
        echo json_encode(['ok' => false, 'error' => 'Failed to mark completed in sandbox']);
        exit;
    }
}

// Handle live mode
try {
    $privateKey = $settings['direct_private_key'] ?? '';
    if (empty($privateKey)) {
        throw new Exception('Private key is not configured.');
    }

    // Decrypt the token
    $decryptedToken = gpay_ecv2_decrypt($token, $privateKey);

    // TODO: Implement your payment processor integration here
    // This is a placeholder function. You need to replace it with your
    // actual payment processor's API calls.
    $result = process_payment_with_acquirer($decryptedToken);

    if ($result['success']) {
        $auth_id = $result['transaction_id'];
        if (function_exists('pp_set_transaction_byid') && pp_set_transaction_byid($payment_id, $plugin_slug, $display_name, 'Customer', $auth_id, 'completed', 'Payment successfully processed.')) {
            echo json_encode(['ok' => true, 'redirect' => pp_get_paymentlink($payment_id)]);
            exit;
        } else {
            throw new Exception('Failed to update transaction status.');
        }
    } else {
        throw new Exception('Payment processor error: ' . $result['error_message']);
    }
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'error' => 'Decryption/Processing failed: ' . $e->getMessage()]);
    exit;
}

/**
 * Placeholder for payment processor integration.
 *
 * @param array $decryptedToken The decrypted payment token.
 * @return array An array with the result of the transaction.
 */
function process_payment_with_acquirer($decryptedToken)
{
    // TODO: Replace this with your payment processor's API calls.
    // The decrypted token contains the following fields:
    // - pan
    // - expirationMonth
    // - expirationYear
    // - cryptogram
    // - eciIndicator (optional)

    // For demonstration purposes, we'll just simulate a successful transaction.
    return [
        'success' => true,
        'transaction_id' => 'PROC-SIMULATED-' . uniqid(),
        'error_message' => ''
    ];
}
