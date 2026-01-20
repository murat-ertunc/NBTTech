<?php
/**
 * Musteri Detay Sayfasi - Server-Rendered
 * URL: /customer/{id}
 * 
 * RBAC: Tab'lar server-side permission kontrolu ile render edilir.
 * Permission yoksa tab DOM'a eklenmez.
 * 
 * Tab degistirme URL'yi degistirmiyor, sadece JS state'i degisiyor
 */

// Musteri Id'si URL'den router tarafindan aktariliyor
$MusteriId = $MusteriId ?? 0;

$pageTitle = 'Müşteri Detay';
$activeNav = 'customers';
$currentPage = 'customer';

require __DIR__ . '/partials/header.php';

// Tab permission mapping - tab key => required permission
$TabPermissions = [
    'bilgi'        => 'customers.read',
    'kisiler'      => 'contacts.read',
    'gorusme'      => 'meetings.read',
    'projeler'     => 'projects.read',
    'teklifler'    => 'offers.read',
    'sozlesmeler'  => 'contracts.read',
    'takvim'       => 'calendar.read',
    'damgavergisi' => 'stamp_taxes.read',
    'teminatlar'   => 'guarantees.read',
    'faturalar'    => 'invoices.read',
    'odemeler'     => 'payments.read',
    'dosyalar'     => 'files.read'
];

// Tab gorsel bilgileri
$TabInfo = [
    'bilgi'        => ['icon' => 'bi-info-circle', 'label' => 'Bilgi'],
    'kisiler'      => ['icon' => 'bi-people', 'label' => 'Kişiler'],
    'gorusme'      => ['icon' => 'bi-chat-dots', 'label' => 'Görüşme'],
    'projeler'     => ['icon' => 'bi-kanban', 'label' => 'Projeler'],
    'teklifler'    => ['icon' => 'bi-file-earmark-text', 'label' => 'Teklif'],
    'sozlesmeler'  => ['icon' => 'bi-file-text', 'label' => 'Sözleşme'],
    'takvim'       => ['icon' => 'bi-calendar3', 'label' => 'Takvim'],
    'damgavergisi' => ['icon' => 'bi-percent', 'label' => 'Damga Vergisi'],
    'teminatlar'   => ['icon' => 'bi-shield-check', 'label' => 'Teminat'],
    'faturalar'    => ['icon' => 'bi-receipt', 'label' => 'Fatura'],
    'odemeler'     => ['icon' => 'bi-cash-stack', 'label' => 'Ödeme'],
    'dosyalar'     => ['icon' => 'bi-folder', 'label' => 'Dosyalar']
];

// Izinli tab'lari filtrele
$AllowedTabs = [];
foreach ($TabPermissions as $TabKey => $Permission) {
    if ($can($Permission)) {
        $AllowedTabs[] = $TabKey;
    }
}

// Ilk izinli tab active olacak
$DefaultTab = !empty($AllowedTabs) ? $AllowedTabs[0] : null;
?>

    <!-- ===== VIEW: MÜŞTERİ DETAY ===== -->
    <div id="view-customer-detail" data-customer-id="<?= (int)$MusteriId ?>" data-default-tab="<?= htmlspecialchars($DefaultTab ?? '', ENT_QUOTES, 'UTF-8') ?>" data-allowed-tabs="<?= htmlspecialchars(json_encode($AllowedTabs), ENT_QUOTES, 'UTF-8') ?>">
      <!-- Müşteri Başlık -->
      <div class="card mb-3 border-0 shadow-sm">
        <div class="card-body py-2">
          <div class="d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-3">
              <div>
                <h5 class="mb-0" id="customerDetailTitle">Müşteri Adı</h5>
                <small class="text-muted" id="customerDetailCode"></small>
              </div>
            </div>
            <?php if ($can('customers.update')): ?>
            <button type="button" class="btn btn-primary btn-sm" id="btnEditCustomer">
              <i class="bi bi-pencil me-1"></i>Düzenle
            </button>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <?php if (empty($AllowedTabs)): ?>
      <!-- Hic izinli tab yoksa empty state -->
      <div class="alert alert-warning">
        <i class="bi bi-shield-exclamation me-2"></i>
        Bu müşterinin detaylarını görüntülemek için yeterli yetkiniz bulunmamaktadır.
      </div>
      <?php else: ?>
      <!-- Tab Menüsü -->
      <ul class="nav nav-tabs" id="customerTabs" role="tablist">
        <?php 
        $IsFirst = true;
        foreach ($AllowedTabs as $TabKey): 
            $Info = $TabInfo[$TabKey] ?? ['icon' => 'bi-circle', 'label' => ucfirst($TabKey)];
        ?>
        <li class="nav-item" role="presentation">
          <button class="nav-link<?= $IsFirst ? ' active' : '' ?>" data-tab="<?= htmlspecialchars($TabKey, ENT_QUOTES, 'UTF-8') ?>" type="button">
            <i class="bi <?= htmlspecialchars($Info['icon'], ENT_QUOTES, 'UTF-8') ?> me-1"></i><?= htmlspecialchars($Info['label'], ENT_QUOTES, 'UTF-8') ?>
          </button>
        </li>
        <?php 
            $IsFirst = false;
        endforeach; 
        ?>
      </ul>

      <!-- Tab İçerikleri -->
      <div id="customerTabContent" class="mt-3">
        <!-- JS ile doldurulacak -->
      </div>
      <?php endif; ?>
    </div>

<?php require __DIR__ . '/partials/modals/project.php'; ?>
<?php require __DIR__ . '/partials/modals/invoice.php'; ?>
<?php require __DIR__ . '/partials/modals/payment.php'; ?>
<?php require __DIR__ . '/partials/modals/offer.php'; ?>
<?php require __DIR__ . '/partials/modals/contract.php'; ?>
<?php require __DIR__ . '/partials/modals/guarantee.php'; ?>
<?php require __DIR__ . '/partials/modals/meeting.php'; ?>
<?php require __DIR__ . '/partials/modals/contact.php'; ?>
<?php require __DIR__ . '/partials/modals/stamp-tax.php'; ?>
<?php require __DIR__ . '/partials/modals/file.php'; ?>
<?php require __DIR__ . '/partials/modals/calendar.php'; ?>
<?php require __DIR__ . '/partials/modals/entity-detail.php'; ?>
<?php require __DIR__ . '/partials/footer.php'; ?>
