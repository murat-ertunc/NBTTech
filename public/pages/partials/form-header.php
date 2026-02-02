<?php













$FormMusteriId = $FormMusteriId ?? 0;
$FormTabKey = $FormTabKey ?? 'bilgi';
$FormTitle = $FormTitle ?? 'Form';
$FormIcon = $FormIcon ?? 'bi-file-text';
$FormBreadcrumb = $FormBreadcrumb ?? $FormTitle;

$BackUrl = "/customer/{$FormMusteriId}?tab={$FormTabKey}";
?>
<!-- Breadcrumb ve Geri Butonu -->
<div class="d-flex justify-content-between align-items-center mb-4">
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb mb-0">
      <li class="breadcrumb-item"><a href="/customer/<?= (int)$FormMusteriId ?>">Müşteri</a></li>
      <li class="breadcrumb-item active"><?= htmlspecialchars($FormBreadcrumb, ENT_QUOTES, 'UTF-8') ?></li>
    </ol>
  </nav>
  <a href="<?= htmlspecialchars($BackUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary">
    <i class="bi bi-arrow-left me-1"></i>Listeye Dön
  </a>
</div>

<!-- Form Card Header -->
<div class="card shadow-sm">
  <div class="card-header bg-primary text-white">
    <h5 class="card-title mb-0">
      <i class="bi <?= htmlspecialchars($FormIcon, ENT_QUOTES, 'UTF-8') ?> me-2"></i><?= htmlspecialchars($FormTitle, ENT_QUOTES, 'UTF-8') ?>
    </h5>
  </div>
