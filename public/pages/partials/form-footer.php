<?php












$FormMusteriId = $FormMusteriId ?? 0;
$FormTabKey = $FormTabKey ?? 'bilgi';
$FormSaveButtonId = $FormSaveButtonId ?? 'btnSaveForm';
$FormPermission = $FormPermission ?? '';
$FormButtonColor = $FormButtonColor ?? 'primary';

$BackUrl = "/customer/{$FormMusteriId}?tab={$FormTabKey}";
?>
  <div class="card-footer d-flex justify-content-between">
    <a href="<?= htmlspecialchars($BackUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-secondary">
      <i class="bi bi-x-lg me-1"></i>İptal
    </a>
    <button type="button" class="btn btn-<?= htmlspecialchars($FormButtonColor, ENT_QUOTES, 'UTF-8') ?>" id="<?= htmlspecialchars($FormSaveButtonId, ENT_QUOTES, 'UTF-8') ?>" data-can-any="<?= htmlspecialchars($FormPermission, ENT_QUOTES, 'UTF-8') ?>">
      <i class="bi bi-check-lg me-1"></i>Kaydet
    </button>
  </div>
</div><!-- /.card -->
