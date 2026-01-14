<?php
/**
 * Fatura Modal - Ekle/Düzenle (XL Modal)
 */
?>
<!-- Fatura Modal -->
<div class="modal fade" id="invoiceModal" tabindex="-1">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white py-2">
        <h6 class="modal-title" id="invoiceModalTitle"><i class="bi bi-receipt me-2"></i>Yeni Fatura</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-danger d-none modal-error" id="invoiceModalError"></div>
        <input type="hidden" id="invoiceId">

        <!-- Temel Bilgiler Card -->
        <div class="card mb-3">
          <div class="card-header py-2 bg-light">
            <span class="fw-semibold"><i class="bi bi-info-circle me-1"></i>Temel Bilgiler</span>
          </div>
          <div class="card-body py-3">
            <div class="row mb-2">
              <label class="col-4 col-form-label col-form-label-sm fw-semibold">Müşteri <span class="text-danger">*</span></label>
              <div class="col-8">
                <select class="form-select form-select-sm" id="invoiceMusteriId" required>
                  <option value="">Seçiniz...</option>
                </select>
              </div>
            </div>
            <div class="row mb-2">
              <label class="col-4 col-form-label col-form-label-sm fw-semibold">Proje <span class="text-danger">*</span></label>
              <div class="col-8">
                <select class="form-select form-select-sm" id="invoiceProjeId" required>
                  <option value="">Proje Seçiniz...</option>
                </select>
              </div>
            </div>
            <div class="row mb-2">
              <label class="col-4 col-form-label col-form-label-sm fw-semibold">Fatura No</label>
              <div class="col-8">
                <input type="text" class="form-control form-control-sm" id="invoiceFaturaNo" maxlength="50" placeholder="Fatura numarası">
              </div>
            </div>
            <div class="row mb-2">
              <label class="col-4 col-form-label col-form-label-sm fw-semibold">Tarih</label>
              <div class="col-8">
                <input type="date" class="form-control form-control-sm" id="invoiceTarih" value="<?= date('Y-m-d') ?>">
              </div>
            </div>
            <div class="row mb-2">
              <label class="col-4 col-form-label col-form-label-sm fw-semibold">Tutar</label>
              <div class="col-8">
                <div class="input-group input-group-sm">
                  <input type="number" step="0.01" class="form-control" id="invoiceTutar">
                  <select class="form-select" id="invoiceDoviz" style="max-width: 120px;">
                    <option value="TRY">TRY</option>
                    <option value="USD">USD</option>
                    <option value="EUR">EUR</option>
                  </select>
                </div>
              </div>
            </div>
            <div class="row mb-2">
              <label class="col-4 col-form-label col-form-label-sm fw-semibold">Açıklama</label>
              <div class="col-8">
                <input type="text" class="form-control form-control-sm" id="invoiceAciklama" placeholder="Fatura açıklaması">
              </div>
            </div>
            <div class="row">
              <div class="col-4"></div>
              <div class="col-8">
                <div class="form-check">
                  <input type="checkbox" class="form-check-input" id="invoiceSupheliAlacak">
                  <label class="form-check-label fw-semibold" for="invoiceSupheliAlacak">Şüpheli Alacak</label>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Tevkifat Alanı -->
        <div class="card mb-3">
          <div class="card-header py-2 bg-light d-flex align-items-center">
            <div class="form-check mb-0">
              <input type="checkbox" class="form-check-input" id="invoiceTevkifatAktif">
              <label class="form-check-label fw-semibold" for="invoiceTevkifatAktif">
                <i class="bi bi-percent me-1"></i>Tevkifat Uygula
              </label>
            </div>
          </div>
          <div class="card-body py-3" id="tevkifatAlani" style="display: none;">
            <div class="row mb-2">
              <label class="col-4 col-form-label col-form-label-sm fw-semibold">Tevkifat Oranı 1</label>
              <div class="col-8">
                <div class="input-group input-group-sm">
                  <input type="number" step="0.01" class="form-control" id="invoiceTevkifatOran1" placeholder="0.00">
                  <span class="input-group-text">%</span>
                </div>
              </div>
            </div>
            <div class="row">
              <label class="col-4 col-form-label col-form-label-sm fw-semibold">Tevkifat Oranı 2</label>
              <div class="col-8">
                <div class="input-group input-group-sm">
                  <input type="number" step="0.01" class="form-control" id="invoiceTevkifatOran2" placeholder="0.00">
                  <span class="input-group-text">%</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Takvim Hatırlatma Alanı -->
        <div class="card mb-3">
          <div class="card-header py-2 bg-light d-flex align-items-center">
            <div class="form-check mb-0">
              <input type="checkbox" class="form-check-input" id="invoiceTakvimAktif">
              <label class="form-check-label fw-semibold" for="invoiceTakvimAktif">
                <i class="bi bi-calendar-event me-1"></i>Takvim Hatırlatması Oluştur
              </label>
            </div>
          </div>
          <div class="card-body py-3" id="takvimAlani" style="display: none;">
            <div class="row mb-2">
              <label class="col-4 col-form-label col-form-label-sm fw-semibold">Süre</label>
              <div class="col-8">
                <input type="number" class="form-control form-control-sm" id="invoiceTakvimSure" min="1" max="999" maxlength="3" placeholder="Süre">
              </div>
            </div>
            <div class="row mb-2">
              <label class="col-4 col-form-label col-form-label-sm fw-semibold">Süre Tipi</label>
              <div class="col-8">
                <select class="form-select form-select-sm" id="invoiceTakvimSureTipi">
                  <option value="gun">Gün</option>
                  <option value="hafta">Hafta</option>
                  <option value="ay">Ay</option>
                  <option value="yil">Yıl</option>
                </select>
              </div>
            </div>
            <div class="row">
              <div class="col-4"></div>
              <div class="col-8">
                <small class="text-muted">
                  <i class="bi bi-info-circle me-1"></i>Fatura tarihinden itibaren hesaplanır
                </small>
              </div>
            </div>
          </div>
        </div>

        <!-- Fatura Kalemleri Tablosu -->
        <div class="card">
          <div class="card-header py-2 bg-light d-flex justify-content-between align-items-center">
            <span class="fw-semibold"><i class="bi bi-list-ul me-1"></i>Fatura Kalemleri</span>
            <button type="button" class="btn btn-success btn-sm" id="btnAddInvoiceItem">
              <i class="bi bi-plus-lg me-1"></i>Kalem Ekle
            </button>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-sm table-bordered mb-0" id="invoiceItemsTable">
                <thead class="table-light">
                  <tr>
                    <th style="width: 50px;" class="text-center">#</th>
                    <th style="width: 80px;">Miktar</th>
                    <th>Açıklama</th>
                    <th style="width: 80px;">KDV %</th>
                    <th style="width: 120px;">Birim Fiyat</th>
                    <th style="width: 120px;">Tutar</th>
                    <th style="width: 50px;" class="text-center">İşlem</th>
                  </tr>
                </thead>
                <tbody id="invoiceItemsBody">
                  <!-- Dinamik satırlar buraya eklenecek -->
                </tbody>
                <tfoot class="table-light">
                  <tr>
                    <td colspan="5" class="text-end fw-semibold">TOPLAM</td>
                    <td><input type="text" class="form-control form-control-sm bg-light" id="invoiceItemsToplam" value="0" readonly></td>
                    <td></td>
                  </tr>
                  <tr>
                    <td colspan="5" class="text-end fw-semibold">KDV</td>
                    <td><input type="text" class="form-control form-control-sm bg-light" id="invoiceItemsKdv" value="0" readonly></td>
                    <td></td>
                  </tr>
                  <tr>
                    <td colspan="5" class="text-end fw-bold">GENEL TOPLAM</td>
                    <td><input type="text" class="form-control form-control-sm bg-light fw-bold" id="invoiceItemsGenelToplam" value="0" readonly></td>
                    <td></td>
                  </tr>
                </tfoot>
              </table>
            </div>
            <div id="invoiceItemsEmpty" class="text-center py-4 text-muted">
              <i class="bi bi-inbox fs-3 d-block mb-2"></i>
              <span>Henüz kalem eklenmedi. "Kalem Ekle" butonuna tıklayarak başlayın.</span>
            </div>
          </div>
        </div>

      </div>
      <div class="modal-footer py-2">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">İptal</button>
        <button type="button" class="btn btn-primary btn-sm" id="btnSaveInvoice"><i class="bi bi-check-lg me-1"></i>Kaydet</button>
      </div>
    </div>
  </div>
</div>

<script>
// Fatura kalemleri için global sayaç (çakışma olmaması için window'a ata)
if (typeof window.invoiceItemCounter === 'undefined') {
    window.invoiceItemCounter = 0;
}

document.addEventListener('DOMContentLoaded', function() {
    // Tevkifat toggle
    const tevkifatCheckbox = document.getElementById('invoiceTevkifatAktif');
    const tevkifatAlani = document.getElementById('tevkifatAlani');
    if (tevkifatCheckbox && tevkifatAlani) {
        tevkifatCheckbox.addEventListener('change', function() {
            tevkifatAlani.style.display = this.checked ? 'block' : 'none';
        });
    }

    // Takvim toggle
    const takvimCheckbox = document.getElementById('invoiceTakvimAktif');
    const takvimAlani = document.getElementById('takvimAlani');
    if (takvimCheckbox && takvimAlani) {
        takvimCheckbox.addEventListener('change', function() {
            takvimAlani.style.display = this.checked ? 'block' : 'none';
        });
    }

    // Kalem ekle butonu
    const btnAddItem = document.getElementById('btnAddInvoiceItem');
    if (btnAddItem) {
        btnAddItem.addEventListener('click', function() {
            addInvoiceItemRow();
        });
    }

    // Fatura kalemleri hesaplama (event delegation)
    const itemsBody = document.getElementById('invoiceItemsBody');
    if (itemsBody) {
        itemsBody.addEventListener('input', function(e) {
            if (e.target.classList.contains('item-miktar') || 
                e.target.classList.contains('item-kdv') || 
                e.target.classList.contains('item-birimfiyat')) {
                calculateInvoiceItemRow(e.target.closest('tr'));
                calculateInvoiceItemsTotals();
            }
        });

        // Kalem silme (event delegation)
        itemsBody.addEventListener('click', function(e) {
            const deleteBtn = e.target.closest('.btn-delete-item');
            if (deleteBtn) {
                const row = deleteBtn.closest('tr');
                if (row) {
                    row.remove();
                    updateItemRowNumbers();
                    calculateInvoiceItemsTotals();
                    checkEmptyItems();
                }
            }
        });
    }

    // Sayfa yüklendiğinde boş durumu kontrol et
    checkEmptyItems();
});

function addInvoiceItemRow(data = null) {
    const tbody = document.getElementById('invoiceItemsBody');
    if (!tbody) return;

    window.invoiceItemCounter++;
    const rowNum = tbody.querySelectorAll('tr').length + 1;
    
    // Ondalık değerleri formatla (başındaki 0'ı koru)
    const formatNum = (val, decimals = 2) => {
        if (val === null || val === undefined || val === '') return '0';
        const num = parseFloat(val);
        return isNaN(num) ? '0' : num.toFixed(decimals);
    };
    
    const tr = document.createElement('tr');
    tr.className = 'invoice-item-row';
    tr.dataset.row = window.invoiceItemCounter;
    
    tr.innerHTML = `
        <td class="text-center align-middle row-number">${rowNum}</td>
        <td><input type="number" class="form-control form-control-sm item-miktar" value="${formatNum(data?.Miktar, 0)}" min="0"></td>
        <td><input type="text" class="form-control form-control-sm item-aciklama" value="${data?.Aciklama || ''}" placeholder="Açıklama girin..."></td>
        <td><input type="number" class="form-control form-control-sm item-kdv" value="${formatNum(data?.KdvOran)}" min="0" max="100" step="0.01"></td>
        <td><input type="number" class="form-control form-control-sm item-birimfiyat" value="${formatNum(data?.BirimFiyat)}" step="0.01" min="0"></td>
        <td><input type="number" class="form-control form-control-sm item-tutar bg-light" value="${formatNum(data?.Tutar)}" readonly></td>
        <td class="text-center align-middle">
            <button type="button" class="btn btn-outline-danger btn-sm btn-delete-item" title="Sil">
                <i class="bi bi-trash"></i>
            </button>
        </td>
    `;
    
    tbody.appendChild(tr);
    
    // Hesaplamaları güncelle
    calculateInvoiceItemRow(tr);
    calculateInvoiceItemsTotals();
    checkEmptyItems();
    
    // Yeni eklenen satıra focus
    tr.querySelector('.item-miktar').focus();
}

function calculateInvoiceItemRow(row) {
    if (!row) return;
    
    const miktar = parseFloat(row.querySelector('.item-miktar').value) || 0;
    const birimFiyat = parseFloat(row.querySelector('.item-birimfiyat').value) || 0;
    const tutar = miktar * birimFiyat;
    
    row.querySelector('.item-tutar').value = tutar.toFixed(2);
}

function calculateInvoiceItemsTotals() {
    const rows = document.querySelectorAll('#invoiceItemsBody .invoice-item-row');
    let toplam = 0;
    let toplamKdv = 0;

    rows.forEach(row => {
        const tutar = parseFloat(row.querySelector('.item-tutar').value) || 0;
        const kdvOran = parseFloat(row.querySelector('.item-kdv').value) || 0;
        const satirKdv = tutar * (kdvOran / 100);
        
        toplam += tutar;
        toplamKdv += satirKdv;
    });

    const toplamEl = document.getElementById('invoiceItemsToplam');
    const kdvEl = document.getElementById('invoiceItemsKdv');
    const genelToplamEl = document.getElementById('invoiceItemsGenelToplam');
    
    if (toplamEl) toplamEl.value = toplam.toFixed(2);
    if (kdvEl) kdvEl.value = toplamKdv.toFixed(2);
    if (genelToplamEl) genelToplamEl.value = (toplam + toplamKdv).toFixed(2);
}

function updateItemRowNumbers() {
    const rows = document.querySelectorAll('#invoiceItemsBody .invoice-item-row');
    rows.forEach((row, index) => {
        const numCell = row.querySelector('.row-number');
        if (numCell) numCell.textContent = index + 1;
    });
}

function checkEmptyItems() {
    const tbody = document.getElementById('invoiceItemsBody');
    const emptyDiv = document.getElementById('invoiceItemsEmpty');
    const table = document.getElementById('invoiceItemsTable');
    
    if (!tbody || !emptyDiv || !table) return;
    
    const hasItems = tbody.querySelectorAll('tr').length > 0;
    emptyDiv.style.display = hasItems ? 'none' : 'block';
    table.style.display = hasItems ? 'table' : 'none';
}

function resetInvoiceItemsUI() {
    const tbody = document.getElementById('invoiceItemsBody');
    if (tbody) tbody.innerHTML = '';
    window.invoiceItemCounter = 0;
    calculateInvoiceItemsTotals();
    checkEmptyItems();
}

function loadInvoiceItemsUI(kalemler) {
    resetInvoiceItemsUI();
    if (kalemler && Array.isArray(kalemler)) {
        kalemler.forEach(kalem => {
            addInvoiceItemRow(kalem);
        });
    }
}

// Global erişim için
window.addInvoiceItemRow = addInvoiceItemRow;
window.resetInvoiceItemsUI = resetInvoiceItemsUI;
window.loadInvoiceItemsUI = loadInvoiceItemsUI;
window.calculateInvoiceItemsTotals = calculateInvoiceItemsTotals;
</script>
