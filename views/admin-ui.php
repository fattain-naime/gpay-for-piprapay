<?php
$plugin_slug='gpay-direct'; $plugin_info=pp_get_plugin_info($plugin_slug); $settings=pp_get_plugin_setting($plugin_slug);
?>
<form id="gpaySettingsForm" method="post" action="">
  <div class="page-header"><div class="row align-items-end"><div class="col-sm"><h1 class="page-header-title">Edit Gateway</h1></div></div></div>
  <div class="row justify-content-center"><div class="col-lg-8"><div class="d-grid gap-3 gap-lg-5">
    <div class="card"><div class="card-header"><h2 class="card-title h4">Gateway Information</h2></div><div class="card-body">
      <input type="hidden" name="action" value="plugin_update-submit"><input type="hidden" name="plugin_slug" value="<?php echo $plugin_slug?>">
      <div class="row mb-4">
        <div class="col-sm-6"><label class="col-sm-12 col-form-label form-label">Name</label><div class="input-group"><input type="text" class="form-control" name="name" value="<?= htmlspecialchars($settings['name'] ?? $plugin_info['plugin_name']) ?>" readonly></div></div>
        <div class="col-sm-6"><label class="col-sm-12 col-form-label form-label">Display name</label><div class="input-group"><input type="text" class="form-control" name="display_name" value="<?= htmlspecialchars($settings['display_name'] ?? $plugin_info['plugin_name']) ?>"></div></div>
      </div>
      <div class="row mb-4">
        <div class="col-sm-6"><label class="col-sm-12 col-form-label form-label">Min amount</label><div class="input-group"><span class="input-group-text">USD</span><input type="text" class="form-control" name="min_amount" value="<?= htmlspecialchars($settings['min_amount'] ?? '0') ?>"></div></div>
        <div class="col-sm-6"><label class="col-sm-12 col-form-label form-label">Max amount</label><div class="input-group"><span class="input-group-text">USD</span><input type="text" class="form-control" name="max_amount" value="<?= htmlspecialchars($settings['max_amount'] ?? '0') ?>"></div></div>
        <div class="col-sm-6"><label class="col-sm-12 col-form-label form-label">Fixed charge</label><div class="input-group"><span class="input-group-text">USD</span><input type="text" class="form-control" name="fixed_charge" value="<?= htmlspecialchars($settings['fixed_charge'] ?? '0') ?>"></div></div>
        <div class="col-sm-6"><label class="col-sm-12 col-form-label form-label">Percent charge</label><div class="input-group"><span class="input-group-text">USD</span><input type="text" class="form-control" name="percent_charge" value="<?= htmlspecialchars($settings['percent_charge'] ?? '0') ?>"></div></div>
        <div class="col-sm-6"><label class="col-sm-12 col-form-label form-label">Status</label><div class="input-group"><?php $sg=isset($settings['status'])?strtolower($settings['status']):''; ?><select class="form-control" name="status"><option value="disable" <?= ($sg==='disable')?'selected':'' ?>>Disable</option><option value="enable" <?= ($sg==='enable')?'selected':'' ?>>Enable</option></select></div></div>
        <div class="col-sm-6"><label class="col-sm-12 col-form-label form-label">Category</label><div class="input-group"><input type="text" class="form-control" name="category" value="International" readonly></div></div>
        <div class="col-sm-6"><label class="col-sm-12 col-form-label form-label">Currency</label><div class="input-group"><input type="text" class="form-control" name="currency" value="USD" readonly></div></div>
      </div>
    </div></div>

    <div class="card"><div class="card-header"><h2 class="card-title h4">Direct Tokenization (ECv2)</h2></div><div class="card-body">
      <div class="row mb-4">
        <div class="col-sm-6"><label class="col-sm-12 col-form-label form-label">Mode</label><div class="input-group"><?php $gm=isset($settings['gpay_mode'])?strtolower($settings['gpay_mode']):'sandbox'; ?><select class="form-control" name="gpay_mode" id="gpay_mode"><option value="sandbox" <?= ($gm==='sandbox')?'selected':'' ?>>Sandbox</option><option value="live" <?= ($gm==='live')?'selected':'' ?>>Live</option></select></div><div class="text-secondary mt-2">Sandbox auto-completes after token capture (for testing).</div></div>
        <div class="col-sm-6"><label class="col-sm-12 col-form-label form-label">Merchant Name</label><div class="input-group"><input type="text" class="form-control" name="merchant_name" value="<?= htmlspecialchars($settings['merchant_name'] ?? ($plugin_info['plugin_name'])) ?>"></div></div>
      </div>
      <div class="row mb-4">
        <div class="col-sm-6"><label class="col-sm-12 col-form-label form-label">Merchant ID (PRODUCTION only)</label><div class="input-group"><input type="text" class="form-control" name="merchant_id" value="<?= htmlspecialchars($settings['merchant_id'] ?? '') ?>" placeholder="Leave blank in Sandbox"></div></div>
        <div class="col-sm-12"><label class="col-sm-12 col-form-label form-label">Public Key (ECv2)</label><textarea class="form-control" name="direct_public_key" rows="3" placeholder="Base64-encoded uncompressed P-256 EC public key"><?= htmlspecialchars($settings['direct_public_key'] ?? '') ?></textarea><div class="text-secondary mt-2">Must be Base64 of the 65-byte uncompressed point (no PEM).</div></div>
      </div>
    </div></div>

    <div id="ajaxResponse"></div><button type="submit" class="btn btn-primary btn-primary-add" style="max-width:150px;">Save Settings</button><div id="stickyBlockEndPoint"></div>
  </div></div></div>
</form>
<script>
$(function(){
  $('#gpaySettingsForm').on('submit', function(e){
    e.preventDefault();
    document.querySelector(".btn-primary-add").innerHTML = '<div class="spinner-border text-light spinner-border-sm" role="status"><span class="visually-hidden">Loading...</span></div>';
    $.ajax({url: $(this).attr('action'), type:'POST', data: $(this).serialize(), dataType:'json',
      success: function(r){ document.querySelector(".btn-primary-add").innerHTML='Save Settings'; const cls=r.status?'alert-success':'alert-danger'; $('#ajaxResponse').attr('class','alert mb-3 '+cls).html(r.message); },
      error: function(){ $('#ajaxResponse').attr('class','alert alert-danger').html('An error occurred. Please try again.'); }
    });
  });
});
</script>
