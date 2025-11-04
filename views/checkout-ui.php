<?php
// Inline authorisation endpoint
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['gpay_token'])) {
  header('Content-Type: application/json');
  $plugin_slug = 'gpay-direct';
  $plugin_info = pp_get_plugin_info($plugin_slug);
  $settings = pp_get_plugin_setting($plugin_slug);
  $payment_id = intval($_POST['payment_id'] ?? 0);
  $token = $_POST['gpay_token'] ?? '';
  $mode = isset($settings['gpay_mode']) ? strtolower($settings['gpay_mode']) : 'sandbox';
  if (!$payment_id || !$token) {
    echo json_encode(['ok' => false, 'error' => 'Missing payment_id or token']);
    exit;
  }

  $txn_digest = substr(hash('sha256', $token), 0, 18);
  if ($mode !== 'live') {
    $auth_id = 'TEST-AUTH-' . substr(hash('sha1', $txn_digest . microtime(true)), 0, 10);
    if (function_exists('pp_set_transaction_byid') && pp_set_transaction_byid($payment_id, $plugin_slug, $plugin_info['plugin_name'], 'Sandbox User', $auth_id, 'completed', 'Sandbox simulated authorisation: ' . $auth_id)) {
      echo json_encode(['ok' => true, 'redirect' => pp_get_paymentlink($payment_id)]);
      exit;
    } else {
      echo json_encode(['ok' => false, 'error' => 'Failed to mark completed in sandbox']);
      exit;
    }
  } else {
    $stub = __DIR__ . '/../ecv2-decrypt-stub.php';
    if (file_exists($stub)) {
      include_once $stub;
      try {
        $result = gpay_ecv2_decrypt_stub($token);
        $auth_id = $result['exampleAuthId'] ?? ('PENDING-' . $txn_digest);
        if (function_exists('pp_set_transaction_byid') && pp_set_transaction_byid($payment_id, $plugin_slug, $plugin_info['plugin_name'], 'Customer', $auth_id, 'pending', 'Awaiting acquirer authorisation.')) {
          echo json_encode(['ok' => true, 'redirect' => pp_get_paymentlink($payment_id)]);
          exit;
        }
      } catch (Exception $e) {
        echo json_encode(['ok' => false, 'error' => 'Decrypt stub failed: ' . $e->getMessage()]);
        exit;
      }
    }
    echo json_encode(['ok' => false, 'error' => 'Decrypt stub not found']);
    exit;
  }
}

$transaction_details = pp_get_transation($payment_id);
$setting = pp_get_settings();
$plugin_slug = 'gpay-direct';
$plugin_info = pp_get_plugin_info($plugin_slug);
$settings = pp_get_plugin_setting($plugin_slug);

$amountOriginal = $transaction_details['response'][0]['transaction_amount'];
$currencyOriginal = $transaction_details['response'][0]['transaction_currency'];
$transaction_amount = convertToDefault($amountOriginal, $currencyOriginal, $settings['currency']);
$fee_fixed = safeNumber($settings['fixed_charge']);
$fee_percent = safeNumber($settings['percent_charge']);
$transaction_fee = $fee_fixed + ($transaction_amount * ($fee_percent / 100));
$grand_total = $transaction_amount + $transaction_fee;

$mode = isset($settings['gpay_mode']) ? strtolower($settings['gpay_mode']) : 'sandbox';
$env = ($mode === 'live') ? 'PRODUCTION' : 'TEST';
$merchantName = $settings['merchant_name'] ?? 'Example Merchant';
$merchantId = $settings['merchant_id'] ?? '';
$publicKey = trim($settings['direct_public_key'] ?? '');

$site_name = $setting['response'][0]['site_name'];
$favicon = (isset($setting['response'][0]['favicon']) && $setting['response'][0]['favicon'] !== '--') ? $setting['response'][0]['favicon'] : 'https://cdn.builderhall.com/assets/builderpay/builder_pay_512x512.png';
$global_text = $setting['response'][0]['global_text_color'];
$primary_btn = $setting['response'][0]['primary_button_color'];
$btn_text = $setting['response'][0]['button_text_color'];
$back_link = pp_get_paymentlink($payment_id);
?>
<!doctype html>
<html>

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title><?php echo $settings['display_name'] ?> - <?php echo $site_name ?></title>
  <link rel="icon" type="image/x-icon" href="<?php echo $favicon; ?>">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
  <style>
    :root {
      --text:
        <?php echo $global_text ?>
      ;
      --btn-bg:
        <?php echo $primary_btn ?>
      ;
      --btn-text:
        <?php echo $btn_text ?>
      ;
      --card: #ffffff;
      --muted: #f8f9fa;
      --border: #e9ecef;
    }

    body {
      font-family: Inter, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial;
      background: #f5f6f8;
      color: #222;
    }

    .wrap {
      max-width: 900px;
      margin: 2rem auto;
      padding: 0 1rem
    }

    .cardx {
      background: #fff;
      border: 1px solid var(--border);
      border-radius: 16px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, .05);
      overflow: hidden;
    }

    .head {
      display: flex;
      align-items: center;
      gap: .75rem;
      padding: 1rem 1.25rem;
      background: var(--muted);
      border-bottom: 1px solid var(--border);
    }

    .head .back {
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 36px;
      height: 36px;
      border-radius: 8px;
      background: #fff;
      border: 1px solid var(--border);
    }

    .head .logo {
      width: 32px;
      height: 32px;
      border-radius: 6px;
      object-fit: cover;
      margin-left: .5rem;
      background: #fff
    }

    .title {
      font-weight: 600;
      font-size: 1rem;
      color: #333;
      margin: 0
    }

    .sublabel {
      font-size: .875rem;
      color: #6c757d
    }

    .body {
      padding: 1.25rem
    }

    .grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 1rem
    }

    .amountBox {
      display: flex;
      align-items: center;
      gap: 1rem;
      background: var(--muted);
      border: 1px solid var(--border);
      border-radius: 12px;
      padding: 1rem
    }

    .brand {
      width: 48px;
      height: 48px;
      border-radius: 10px;
      background: #fff;
      display: grid;
      place-items: center;
      border: 1px solid var(--border)
    }

    .value {
      font-size: 1.6rem;
      font-weight: 800;
      color: var(--text)
    }

    table.summary {
      width: 100%;
      margin-top: 8px
    }

    table.summary td {
      padding: .35rem 0;
      color: #495057
    }

    table.summary td:last-child {
      text-align: right;
      font-weight: 600;
      color: #111
    }

    .rightCol {
      display: flex;
      flex-direction: column;
      gap: 12px;
      align-items: center;
      /* center content horizontally */
    }

    #gpay-button-container {
      width: 100%;
      display: flex;
      justify-content: center;
    }

    #gpay-button-container>div {
      width: 100% !important;
      /* make Google button span full width */
      max-width: 400px;
      /* optional: limit width */
      display: flex;
      justify-content: center;
    }

    .btn-primary {
      background: var(--btn-bg);
      border-color: var(--btn-bg);
      color: var(--btn-text);
      width: 100%
    }

    .helper {
      font-size: .875rem;
      color: #6c757d
    }

    .footer {
      padding: 1rem;
      border-top: 1px solid var(--border);
      text-align: center;
      font-size: .875rem;
      color: #6c757d
    }

    #gpay-error {
      margin-top: 12px
    }

    @media (max-width: 768px) {
      .grid {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>

<body>
  <div class="wrap">
    <div class="cardx">
      <div class="head">
        <div class="back" onclick="location.href='<?php echo $back_link ?>'">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
            <path d="M15 18l-6-6 6-6" stroke="#333" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
          </svg>
        </div>
        <img class="logo" src="<?php echo $favicon; ?>" alt="">
        <div>
          <div class="title"><?php echo htmlspecialchars($site_name); ?></div>
          <div class="sublabel">Secure checkout • <?php echo htmlspecialchars($settings['display_name']); ?></div>
        </div>
        <div class="ms-auto small text-muted">ENV: <?php echo $env; ?></div>
      </div>

      <div class="body">
        <div class="grid">
          <div>
            <div class="amountBox">
              <div class="brand"><img
                  src="<?php echo pp_get_site_url() . '/pp-content/plugins/' . $plugin_info['plugin_dir'] . '/' . $plugin_slug . '/assets/icon.png'; ?>"
                  alt="" style="height:26px"></div>
              <div>
                <div class="sublabel">Total payable</div>
                <div class="value"><?php echo number_format($grand_total, 2) . ' ' . $settings['currency'] ?></div>
              </div>
            </div>
            <table class="summary">
              <tr>
                <td>Amount</td>
                <td><?php echo number_format($transaction_amount, 2) . ' ' . $settings['currency'] ?></td>
              </tr>
              <tr>
                <td>Fee (fixed)</td>
                <td><?php echo number_format($fee_fixed, 2) . ' ' . $settings['currency'] ?></td>
              </tr>
              <tr>
                <td>Fee (<?php echo number_format($fee_percent, 2) ?>%)</td>
                <td>
                  <?php echo number_format(($grand_total - $transaction_amount - $fee_fixed), 2) . ' ' . $settings['currency'] ?>
                </td>
              </tr>
              <tr>
                <td><strong>Grand total</strong></td>
                <td><strong><?php echo number_format($grand_total, 2) . ' ' . $settings['currency'] ?></strong></td>
              </tr>
            </table>
          </div>

          <div class="rightCol">
            <?php if (empty($publicKey)): ?>
              <div class="alert alert-danger">Google Pay DIRECT requires an ECv2 Public Key. Add it in gateway settings.
              </div>
            <?php else: ?>
              <div id="gpay-button-container"></div>
              <div class="helper">Use Google Pay to complete this payment. You’ll be redirected afterward.</div>
              <div id="gpay-error" class="alert alert-danger d-none"></div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div class="footer">Powered by <a href="https://piprapay.com/" target="_blank"
          style="color:var(--text);text-decoration:none"><strong>PipraPay</strong></a></div>
    </div>
  </div>

  <script>
    const GPAY_ENV = "<?= $env ?>";
    const MERCHANT_NAME = <?= json_encode($merchantName) ?>;
    const MERCHANT_ID = <?= json_encode($merchantId) ?>;
    const PUBLIC_KEY = <?= json_encode($publicKey) ?>;
    const TOTAL_PRICE = "<?= number_format($grand_total, 2, '.', '') ?>";
    const CURRENCY = "<?= $settings['currency'] ?>";
    const PAYMENT_ID = "<?= $payment_id ?>";
    const POST_URL = window.location.href;

    const baseRequest = { apiVersion: 2, apiVersionMinor: 0 };
    const allowedCardNetworks = ["AMEX", "DISCOVER", "JCB", "MASTERCARD", "VISA"];
    const allowedAuthMethods = ["PAN_ONLY", "CRYPTOGRAM_3DS"];

    const baseCardPaymentMethod = {
      type: "CARD",
      parameters: {
        allowedAuthMethods,
        allowedCardNetworks,
        billingAddressRequired: true,
        billingAddressParameters: { format: "FULL" }
      }
    };

    const tokenizationSpecification = {
      type: "DIRECT",
      parameters: { protocolVersion: "ECv2", publicKey: PUBLIC_KEY }
    };
    const cardPaymentMethod = Object.assign({}, baseCardPaymentMethod, { tokenizationSpecification });

    function getGooglePaymentsClient() {
      return new google.payments.api.PaymentsClient({ environment: GPAY_ENV });
    }
    function onGooglePayLoaded() {
      const client = getGooglePaymentsClient();
      const req = Object.assign({}, baseRequest, { allowedPaymentMethods: [baseCardPaymentMethod] });
      client.isReadyToPay(req).then(res => { if (res.result) addGooglePayButton(); else showError("Google Pay not available."); })
        .catch(err => showError(err?.statusMessage || "isReadyToPay failed"));
    }
    function addGooglePayButton() {
      const client = getGooglePaymentsClient();
      const button = client.createButton({ onClick: onGooglePaymentButtonClicked, allowedPaymentMethods: [baseCardPaymentMethod], buttonType: 'pay' });
      document.getElementById("gpay-button-container").appendChild(button);
    }
    function getPaymentDataRequest() {
      const req = Object.assign({}, baseRequest);
      req.allowedPaymentMethods = [cardPaymentMethod];
      req.transactionInfo = { totalPriceStatus: "FINAL", totalPrice: TOTAL_PRICE, currencyCode: CURRENCY };
      req.merchantInfo = { merchantName: MERCHANT_NAME };
      if (GPAY_ENV === "PRODUCTION" && MERCHANT_ID) req.merchantInfo.merchantId = MERCHANT_ID;
      return req;
    }
    function onGooglePaymentButtonClicked() {
      const client = getGooglePaymentsClient();
      client.loadPaymentData(getPaymentDataRequest())
        .then(processPayment)
        .catch(err => { if (err && err.statusCode !== "CANCELED") { showError("Google Pay error: " + (err.statusMessage || err.statusCode || "unknown")); } });
    }
    function processPayment(paymentData) {
      try {
        const token = paymentData.paymentMethodData.tokenizationData.token;
        $.post(POST_URL, { gpay_token: token, payment_id: PAYMENT_ID }, function (resp) {
          try {
            const r = (typeof resp === 'string') ? JSON.parse(resp) : resp;
            if (r.ok && r.redirect) { window.location.assign(r.redirect); }
            else { showError(r.error || "Authorisation failed"); }
          } catch (e) { showError("Unexpected server response"); }
        }).fail(function (jq) { showError("Network error: " + (jq.responseText || jq.status)); });
      } catch (e) { showError("Invalid payment data"); }
    }
    function showError(msg) {
      const el = document.getElementById("gpay-error");
      el.classList.remove("d-none");
      el.innerText = msg;
    }
  </script>
  <script async src="https://pay.google.com/gp/p/js/pay.js" onload="onGooglePayLoaded()"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>