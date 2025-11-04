<?php
$plugin_meta = [
  'Plugin Name' => 'Google Pay',
  'Description' => 'Google Pay DIRECT Payment Gateway for PipraPay',
  'Version' => '1.0.0',
  'Author' => 'Fattain Naime'
];

function gpay_admin_page(){ $v=__DIR__.'/views/admin-ui.php'; if(file_exists($v)) include $v; else echo '<div class="alert alert-warning">Admin UI not found.</div>'; }
function gpay_checkout_page($payment_id){ $v=__DIR__.'/views/checkout-ui.php'; if(file_exists($v)) include $v; else echo '<div class="alert alert-warning">Checkout UI not found.</div>'; }
