<?php
/**
 * Ortak Form Header Partial
 * Modal -> Sayfa dönüşümü için standart header
 * 
 * Gerekli değişkenler:
 *   $FormMusteriId - Müşteri ID
 *   $FormTabKey - Tab key (kisiler, gorusme, projeler, teklifler, sozlesmeler, takvim, damgavergisi, teminatlar, faturalar, odemeler, dosyalar)
 *   $FormTitle - Form başlığı (Yeni Kişi, Görüşme Düzenle vb)
 *   $FormIcon - Bootstrap icon class (bi-people, bi-chat-dots vb)
 *   $FormColor - Header rengi (primary, success, info, warning, danger)
 *   $FormBreadcrumb - Breadcrumb text
 */

$FormMusteriId = $FormMusteriId ?? 0;
$FormTabKey = $FormTabKey ?? 'bilgi';
$FormTitle = $FormTitle ?? 'Form';
$FormIcon = $FormIcon ?? 'bi-file-text';
$FormColor = $FormColor ?? 'primary';
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
  <div class="card-header bg-<?= htmlspecialchars($FormColor, ENT_QUOTES, 'UTF-8') ?> text-white">
    <h5 class="card-title mb-0">
      <i class="bi <?= htmlspecialchars($FormIcon, ENT_QUOTES, 'UTF-8') ?> me-2"></i><?= htmlspecialchars($FormTitle, ENT_QUOTES, 'UTF-8') ?>
    </h5>
  </div>
