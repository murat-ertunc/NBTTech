<?php








$MusteriId = $MusteriId ?? 0;
$FaturaId = $FaturaId ?? 0;
$IsEdit = $FaturaId > 0;

$pageTitle = $IsEdit ? 'Fatura Düzenle' : 'Yeni Fatura';
$activeNav = 'customers';
$currentPage = 'invoice-form';


$FormMusteriId = $MusteriId;
$FormTabKey = 'faturalar';
$FormTitle = $pageTitle;
$FormIcon = 'bi-receipt';
$FormBreadcrumb = $pageTitle;
$FormSaveButtonId = 'btnSaveInvoice';
$FormPermission = 'invoices.create,invoices.update';
$FormButtonColor = 'primary';

require __DIR__ . '/../partials/header.php';
?>

<div class="container-fluid py-4">
  <div class="row justify-content-center">
    <div class="col-12">
      <?php require __DIR__ . '/../partials/form-header.php'; ?>
      
      <div class="card-body">
        <div class="alert alert-danger d-none modal-error" id="invoiceModalError"></div>
        <input type="hidden" id="invoiceId" value="<?= (int)$FaturaId ?>">

        <!-- Temel Bilgiler Card -->
        <div class="card mb-3">
          <div class="card-header py-2 bg-light">
            <span class="fw-semibold"><i class="bi bi-info-circle me-1"></i>Temel Bilgiler</span>
          </div>
          <div class="card-body py-3">
            <div class="row mb-2 d-none">
              <label class="col-4 col-form-label col-form-label-sm fw-semibold">Müşteri <span class="text-danger">*</span></label>
              <div class="col-8">
                <select class="form-select form-select-sm" id="invoiceMusteriId" required>
                  <option value="">Seçiniz...</option>
                </select>
                <small class="form-hint">Zorunlu</small>
                <div class="invalid-feedback"></div>
              </div>
            </div>
            <div class="row mb-2">
              <label class="col-4 col-form-label col-form-label-sm fw-semibold">Proje <span class="text-danger">*</span></label>
              <div class="col-8">
                <select class="form-select form-select-sm" id="invoiceProjeId" required>
                  <option value="">Proje Seçiniz...</option>
                </select>
                <small class="form-hint">Zorunlu</small>
                <div class="invalid-feedback"></div>
              </div>
            </div>
            <div class="row mb-2">
              <label class="col-4 col-form-label col-form-label-sm fw-semibold">Fatura No</label>
              <div class="col-8">
                <input type="text" class="form-control form-control-sm" id="invoiceFaturaNo" maxlength="50" placeholder="Fatura numarası">
                <small class="form-hint">Maks 50</small>
                <div class="invalid-feedback"></div>
              </div>
            </div>
            <div class="row mb-2">
              <label class="col-4 col-form-label col-form-label-sm fw-semibold">Tarih <span class="text-danger">*</span></label>
              <div class="col-8">
                <input type="date" class="form-control form-control-sm" id="invoiceTarih" value="<?= date('Y-m-d') ?>">
                <small class="form-hint">Zorunlu</small>
                <div class="invalid-feedback"></div>
              </div>
            </div>
            <div class="row mb-2">
              <label class="col-4 col-form-label col-form-label-sm fw-semibold">Döviz Cinsi <span class="text-danger">*</span></label>
              <div class="col-8">
                <select class="form-select form-select-sm" id="invoiceDoviz">
                  <option value="">Seçiniz...</option>
                </select>
                <small class="form-hint">Zorunlu</small>
                <div class="invalid-feedback"></div>
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
              <label class="col-4 col-form-label col-form-label-sm fw-semibold">Tevkifat Oranı</label>
              <div class="col-8">
                <div class="input-group input-group-sm">
                  <input type="number" step="0.01" class="form-control" id="invoiceTevkifatOran1" placeholder="0.00" max="100">
                  <span class="input-group-text">%</span>
                </div>
              </div>
            </div>
            <div class="row">
              <label class="col-4 col-form-label col-form-label-sm fw-semibold">Müşteri Tevkifat Oranı</label>
              <div class="col-8">
                <div class="input-group input-group-sm">
                  <input type="number" step="0.01" class="form-control bg-light" id="invoiceTevkifatOran2" placeholder="0.00" readonly>
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
                <i class="bi bi-bell me-1"></i>Takvim Hatırlatması Oluştur
              </label>
            </div>
          </div>
          <div class="card-body py-3" id="takvimHatirlatmaAlani" style="display: none;">
            <div class="row mb-2">
              <label class="col-4 col-form-label col-form-label-sm fw-semibold">Hatırlatma Süresi</label>
              <div class="col-8">
                <div class="d-flex gap-2">
                  <input type="number" min="1" class="form-control form-control-sm number__input" id="invoiceTakvimSure" placeholder="Örn: 3">
                  <select class="form-select form-select-sm" id="invoiceTakvimSureTipi">
                    <option value="">Seçiniz...</option>
                    <option value="gun">gün</option>
                    <option value="hafta">hafta</option>
                    <option value="ay">ay</option>
                    <option value="yil">yıl</option>
                  </select>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Fatura Kalemleri Tablosu -->
        <div class="card">
          <div class="card-header py-2 bg-light d-flex justify-content-between align-items-center">
            <span class="fw-semibold"><i class="bi bi-list-ul me-1"></i>Fatura Kalemleri</span>
            <button type="button" class="btn btn-success btn-sm" id="btnAddInvoiceItem" data-can-any="invoices.create,invoices.update">
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

        <!-- Dosyalar Bölümü -->
        <div class="card mt-3">
          <div class="card-header py-2 bg-light d-flex justify-content-between align-items-center">
            <span class="fw-semibold"><i class="bi bi-folder me-1"></i>Dosyalar</span>
            <label class="btn btn-success btn-sm mb-0" data-can-any="invoices.create,invoices.update">
              <i class="bi bi-upload me-1"></i>Dosya Ekle
              <input type="file" id="invoiceFileInput" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif" style="display:none;">
            </label>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-sm table-bordered mb-0" id="invoiceFilesTable" style="display:none;">
                <thead class="table-light">
                  <tr>
                    <th>Dosya Adı</th>
                    <th style="width: 100px;">Boyut</th>
                    <th style="width: 150px;">Yüklenme</th>
                    <th style="width: 100px;" class="text-center">İşlem</th>
                  </tr>
                </thead>
                <tbody id="invoiceFilesBody">
                </tbody>
              </table>
            </div>
            <div id="invoiceFilesEmpty" class="text-center py-4 text-muted">
              <i class="bi bi-folder-x fs-3 d-block mb-2"></i>
              <span>Henüz dosya eklenmedi. "Dosya Ekle" butonuna tıklayarak yükleyin.</span>
            </div>
          </div>
        </div>

      </div>
      
      <?php require __DIR__ . '/../partials/form-footer.php'; ?>
    </div>
  </div>
</div>

<script>
// Fatura kalemleri için global sayaç (çakışma olmaması için window'a ata)
if (typeof window.invoiceItemCounter === 'undefined') {
    window.invoiceItemCounter = 0;
}

document.addEventListener('DOMContentLoaded', function() {
    // Müşteri ve Proje seçeneklerini yükle (sayfa için)
    const musteriSelect = document.getElementById('invoiceMusteriId');
    const projeSelect = document.getElementById('invoiceProjeId');
    const dovizSelect = document.getElementById('invoiceDoviz');
    const invoiceId = document.getElementById('invoiceId').value;
    
    // InvoiceModule dosya state'ini initialize et
    if (typeof InvoiceModule !== 'undefined') {
        InvoiceModule.resetState();
        
        // Dosya yükleme input event listener
        const fileInput = document.getElementById('invoiceFileInput');
        if (fileInput) {
            fileInput.addEventListener('change', function() {
                InvoiceModule.handleFileSelect(this);
            });
        }
    }
    
    // PHP'den gelen müşteri ID (URL path'ten)
    const phpMusteriId = '<?= (int)$MusteriId ?>';
    
    // Müşteri listesini yükle (gizli alan için)
    if (musteriSelect) {
        NbtApi.get('/api/customers').then(response => {
            const customers = response.data || [];
            customers.forEach(c => {
                musteriSelect.innerHTML += `<option value="${c.Id}">${NbtUtils.escapeHtml(c.MusteriAdi || '')}</option>`;
            });
            
            // Müşteri seçimini yap
            if (phpMusteriId && phpMusteriId !== '0') {
                musteriSelect.value = phpMusteriId;
            }
        }).catch(err => {
            console.error('Müşteriler yüklenemedi:', err);
        });
        
        // Müşteri değiştiğinde projeleri yükle
        musteriSelect.addEventListener('change', function() {
            loadProjects(this.value);
        });
    }
    
    // Yeni fatura modunda projeleri doğrudan yükle (müşteri API'sinden bağımsız)
    if (!invoiceId || invoiceId === '0') {
        if (phpMusteriId && phpMusteriId !== '0') {
            loadProjects(phpMusteriId);
        }
    }
    
    // Döviz seçeneklerini yükle
    if (dovizSelect) {
        NbtParams.populateCurrencySelect(dovizSelect);
    }
    
    // Projeleri yükle fonksiyonu (meetings/new ile AYNI mantık)
    async function loadProjects(musteriId) {
      if (!projeSelect) return;
      if (window.NbtProjectSelect) {
        await window.NbtProjectSelect.loadForCustomer(projeSelect, musteriId);
        return;
      }
      projeSelect.innerHTML = '<option value="">Proje Seçiniz...</option>';
      if (!musteriId) return;
      try {
        const response = await NbtApi.get(`/api/projects?musteri_id=${musteriId}`);
        let projects = response.data || [];
        const pasifKodlar = await NbtParams.getPasifDurumKodlari('proje', true);
        projects = projects.filter(p => !pasifKodlar.includes(String(p.Durum)));
        const uniq = new Map();
        projects.forEach(p => {
          const key = String(p.Id);
          if (!uniq.has(key)) uniq.set(key, p);
        });
        Array.from(uniq.values()).forEach(p => {
          projeSelect.innerHTML += `<option value="${p.Id}">${NbtUtils.escapeHtml(p.ProjeAdi || '')}</option>`;
        });
      } catch (err) {
        console.error('Projeler yüklenemedi:', err);
      }
    }
    
    // Edit modunda veri yükle
    if (invoiceId && invoiceId !== '0') {
        loadInvoiceData(invoiceId);
    }
    
    async function loadInvoiceData(id) {
        try {
            const invoice = await NbtApi.get(`/api/invoices/${id}`);
            if (invoice) {
                // Önce müşteriyi seç, sonra projeleri yükle
                musteriSelect.value = invoice.MusteriId || '';
                await loadProjects(invoice.MusteriId);
                if (projeSelect) {
                  projeSelect.value = invoice.ProjeId || '';
                }
                
                document.getElementById('invoiceFaturaNo').value = invoice.FaturaNo || '';
                document.getElementById('invoiceTarih').value = invoice.Tarih?.split('T')[0] || '';
                dovizSelect.value = invoice.DovizCinsi || invoice.ParaBirimi || NbtParams.getDefaultCurrency();
                document.getElementById('invoiceSupheliAlacak').checked = parseInt(invoice.SupheliAlacak, 10) === 1;
                
                // Tevkifat
                if (parseInt(invoice.TevkifatAktif, 10) === 1) {
                    document.getElementById('invoiceTevkifatAktif').checked = true;
                    document.getElementById('tevkifatAlani').style.display = 'block';
                    document.getElementById('invoiceTevkifatOran1').value = parseFloat(invoice.TevkifatOran1) || '';
                    document.getElementById('invoiceTevkifatOran2').value = parseFloat(invoice.TevkifatOran2) || '';
                }
                
                // Takvim hatırlatma
                if (parseInt(invoice.TakvimAktif, 10) === 1) {
                  document.getElementById('invoiceTakvimAktif').checked = true;
                  document.getElementById('takvimHatirlatmaAlani').style.display = 'block';
                  if (invoice.TakvimSure) {
                    document.getElementById('invoiceTakvimSure').value = invoice.TakvimSure;
                  }
                  if (invoice.TakvimSureTipi) {
                    document.getElementById('invoiceTakvimSureTipi').value = invoice.TakvimSureTipi;
                  }
                }

                // Kalemler
                if (invoice.Kalemler && Array.isArray(invoice.Kalemler)) {
                    loadInvoiceItemsUI(invoice.Kalemler);
                }
                
                // Dosyalar
                if (invoice.Dosyalar && Array.isArray(invoice.Dosyalar)) {
                    InvoiceModule.loadInvoiceFiles(invoice.Dosyalar);
                }
            }
        } catch (err) {
            console.error('Fatura yüklenemedi:', err);
            NbtToast.error('Fatura bilgileri yüklenemedi');
        }
    }

    // Tevkifat toggle
    const tevkifatCheckbox = document.getElementById('invoiceTevkifatAktif');
    const tevkifatAlani = document.getElementById('tevkifatAlani');
    if (tevkifatCheckbox && tevkifatAlani) {
        tevkifatCheckbox.addEventListener('change', function() {
            tevkifatAlani.style.display = this.checked ? 'block' : 'none';
            // Tevkifat devre dışı bırakıldığında hesaplamaları güncelle
            if (!this.checked) {
                const tevkifatOran1 = document.getElementById('invoiceTevkifatOran1');
                const tevkifatOran2 = document.getElementById('invoiceTevkifatOran2');
                if (tevkifatOran1) tevkifatOran1.value = '';
                if (tevkifatOran2) tevkifatOran2.value = '';
            }
            calculateInvoiceItemsTotals();
        });
    }

    // Tevkifat Oranı 1 değiştiğinde Tevkifat Oranı 2'yi otomatik hesapla
    const tevkifatOran1 = document.getElementById('invoiceTevkifatOran1');
    const tevkifatOran2 = document.getElementById('invoiceTevkifatOran2');
    if (tevkifatOran1 && tevkifatOran2) {
        tevkifatOran1.addEventListener('input', function() {
            const oran1 = parseFloat(this.value) || 0;
            const oran2 = Math.max(0, 100 - oran1);
            tevkifatOran2.value = oran2.toFixed(2);
            
            // KDV hesaplamalarını güncelle
            calculateInvoiceItemsTotals();
        });
    }

    // Kalem ekle butonu
    const btnAddItem = document.getElementById('btnAddInvoiceItem');
    if (btnAddItem) {
        // Önceki event listener'ları temizle
        const newBtn = btnAddItem.cloneNode(true);
        btnAddItem.parentNode.replaceChild(newBtn, btnAddItem);
        
        newBtn.addEventListener('click', function() {
            const tbody = document.getElementById('invoiceItemsBody');
            const currentItemCount = tbody ? tbody.querySelectorAll('tr').length : 0;
            
            if (currentItemCount >= 10) {
                alert('Maksimum 10 kalem ekleyebilirsiniz.');
                return;
            }
            
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
    
    // Kaydet butonu
    const btnSave = document.getElementById('btnSaveInvoice');
    if (btnSave) {
        btnSave.addEventListener('click', function() {
            saveInvoice();
        });
    }

    // Takvim hatırlatma toggle
    const takvimCheckbox = document.getElementById('invoiceTakvimAktif');
    const takvimAlani = document.getElementById('takvimHatirlatmaAlani');
    if (takvimCheckbox && takvimAlani) {
      takvimCheckbox.addEventListener('change', function() {
        takvimAlani.style.display = this.checked ? 'block' : 'none';
        if (!this.checked) {
          const sureEl = document.getElementById('invoiceTakvimSure');
          const tipEl = document.getElementById('invoiceTakvimSureTipi');
          if (sureEl) sureEl.value = '';
          if (tipEl) tipEl.value = '';
        }
      });
    }
    
    async function saveInvoice() {
      if (btnSave) {
        btnSave.disabled = true;
        btnSave.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Kaydediliyor...';
      }
        const id = document.getElementById('invoiceId').value;
        const musteriId = parseInt(document.getElementById('invoiceMusteriId').value);
        const projeId = parseInt(document.getElementById('invoiceProjeId').value);
        
        // Validasyon
        if (!musteriId) {
            NbtToast.error('Müşteri seçiniz');
          if (btnSave) {
            btnSave.disabled = false;
            btnSave.innerHTML = '<i class="bi bi-check-lg me-1"></i>Kaydet';
          }
            return;
        }
        if (!projeId) {
            NbtToast.error('Proje seçiniz');
          if (btnSave) {
            btnSave.disabled = false;
            btnSave.innerHTML = '<i class="bi bi-check-lg me-1"></i>Kaydet';
          }
            return;
        }
        
        const tarih = document.getElementById('invoiceTarih').value;
        if (!tarih) {
            NbtToast.error('Tarih zorunludur');
          if (btnSave) {
            btnSave.disabled = false;
            btnSave.innerHTML = '<i class="bi bi-check-lg me-1"></i>Kaydet';
          }
            return;
        }
        
        const kalemler = getInvoiceItems();
        if (!kalemler || kalemler.length === 0) {
            NbtToast.error('En az bir fatura kalemi eklemelisiniz');
          if (btnSave) {
            btnSave.disabled = false;
            btnSave.innerHTML = '<i class="bi bi-check-lg me-1"></i>Kaydet';
          }
            return;
        }
        
        // Tutar'ı kalemlerden hesapla
        const genelToplamEl = document.getElementById('invoiceItemsGenelToplam');
        const tutar = parseFloat(genelToplamEl?.value) || 0;
        
        const tevkifatAktif = document.getElementById('invoiceTevkifatAktif')?.checked ? 1 : 0;
        const takvimAktif = document.getElementById('invoiceTakvimAktif')?.checked ? 1 : 0;
        const takvimSureRaw = document.getElementById('invoiceTakvimSure')?.value || '';
        const takvimSure = parseInt(takvimSureRaw, 10);
        const takvimSureTipi = document.getElementById('invoiceTakvimSureTipi')?.value || '';
        const allowedSureTipleri = ['gun', 'hafta', 'ay', 'yil'];

        if (takvimAktif === 1 && (takvimSureRaw !== '' || takvimSureTipi !== '')) {
          if (!takvimSure || takvimSure <= 0) {
            NbtToast.error('Takvim hatırlatma süresi geçersiz');
            if (btnSave) {
              btnSave.disabled = false;
              btnSave.innerHTML = '<i class="bi bi-check-lg me-1"></i>Kaydet';
            }
            return;
          }
          if (!allowedSureTipleri.includes(takvimSureTipi)) {
            NbtToast.error('Takvim hatırlatma birimi geçersiz');
            if (btnSave) {
              btnSave.disabled = false;
              btnSave.innerHTML = '<i class="bi bi-check-lg me-1"></i>Kaydet';
            }
            return;
          }
        }
        
        const data = {
            MusteriId: musteriId,
            ProjeId: projeId,
            Tarih: tarih,
            Tutar: tutar,
            DovizCinsi: document.getElementById('invoiceDoviz')?.value || NbtParams.getDefaultCurrency(),
            FaturaNo: document.getElementById('invoiceFaturaNo')?.value.trim() || null,
            SupheliAlacak: document.getElementById('invoiceSupheliAlacak')?.checked ? 1 : 0,
            TevkifatAktif: tevkifatAktif,
            TevkifatOran1: tevkifatAktif ? (parseFloat(document.getElementById('invoiceTevkifatOran1')?.value) || null) : null,
            TevkifatOran2: tevkifatAktif ? (parseFloat(document.getElementById('invoiceTevkifatOran2')?.value) || null) : null,
            TakvimAktif: takvimAktif,
            TakvimSure: takvimAktif && takvimSure > 0 ? takvimSure : null,
            TakvimSureTipi: takvimAktif && allowedSureTipleri.includes(takvimSureTipi) ? takvimSureTipi : null,
            Kalemler: kalemler
        };
        
        try {
            let faturaId;
            if (id && id !== '0') {
                await NbtApi.put(`/api/invoices/${id}`, data);
                faturaId = id;
                NbtToast.success('Fatura güncellendi');
            } else {
                const response = await NbtApi.post('/api/invoices', data);
                faturaId = response.id || response.Id || response.data?.id || response.data?.Id;
                NbtToast.success('Fatura eklendi');
            }
            
            // Dosya işlemleri
            if (faturaId && typeof InvoiceModule !== 'undefined') {
                await InvoiceModule.deleteMarkedFiles();
                await InvoiceModule.uploadPendingFiles(faturaId);
            }
            
            // Müşteri detay sayfasına dön
            if (musteriId) {
                window.location.href = `/customer/${musteriId}?tab=faturalar`;
            }
        } catch (err) {
            NbtToast.error(err.message || 'Kayıt sırasında hata oluştu');
        } finally {
          if (btnSave) {
            btnSave.disabled = false;
            btnSave.innerHTML = '<i class="bi bi-check-lg me-1"></i>Kaydet';
          }
        }
    }
    
    function getInvoiceItems() {
        const kalemler = [];
        const rows = document.querySelectorAll('#invoiceItemsBody .invoice-item-row');
        
        rows.forEach((row, index) => {
            const miktar = parseFloat(row.querySelector('.item-miktar').value) || 0;
            const aciklama = row.querySelector('.item-aciklama').value.trim();
            const kdvOran = parseFloat(row.querySelector('.item-kdv').value) || 0;
            const birimFiyat = parseFloat(row.querySelector('.item-birimfiyat').value) || 0;
            const tutar = parseFloat(row.querySelector('.item-tutar').value) || 0;
            
            // Sadece dolu kalemleri ekle
            if (miktar > 0 || aciklama || birimFiyat > 0) {
                kalemler.push({
                    Sira: index + 1,
                    Miktar: miktar,
                    Aciklama: aciklama,
                    KdvOran: kdvOran,
                    BirimFiyat: birimFiyat,
                    Tutar: tutar
                });
            }
        });
        return kalemler;
    }
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
        <td class="text-center align-middle row-number">
          ${rowNum}
        </td>
        <td>
          <input type="number" class="form-control form-control-sm item-miktar nbt-money-input" value="${data?.Miktar ? formatNum(data.Miktar, 0) : ''}" min="0" placeholder="0">
        </td>
        <td>
          <input type="text" class="form-control form-control-sm item-aciklama" value="${data?.Aciklama || ''}" placeholder="Açıklama girin...">
        </td>
        <td>
          <select class="form-control form-control-sm item-kdv">
            <option ${formatNum(data?.KdvOran) == 1 ? 'selected' : ''}>1</option>
            <option ${formatNum(data?.KdvOran) == 8 ? 'selected' : ''}>8</option>
            <option ${formatNum(data?.KdvOran) == 18 ? 'selected' : ''}>18</option>
            <option ${formatNum(data?.KdvOran) == 20 ? 'selected' : ''}>20</option>
          </select>
        </td>
        <td>
          <input type="number" class="form-control form-control-sm item-birimfiyat nbt-money-input" value="${data?.BirimFiyat ? formatNum(data.BirimFiyat) : ''}" step="0.01" min="0" placeholder="0,00">
        </td>
        <td>
          <input type="number" class="form-control form-control-sm item-tutar bg-light" value="${formatNum(data?.Tutar)}" readonly>
        </td>
        <td class="text-center align-middle">
            <button type="button" class="btn btn-outline-danger btn-sm btn-delete-item" title="Sil" data-can-any="invoices.create,invoices.update">
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

    // Tevkifat oranını al ve KDV'ye uygula
    const tevkifatAktif = document.getElementById('invoiceTevkifatAktif')?.checked || false;
    const tevkifatOran1 = tevkifatAktif ? (parseFloat(document.getElementById('invoiceTevkifatOran1')?.value) || 0) : 0;
    
    // Tevkifat oranı varsa KDV'yi ona göre düşür
    let kdvSonrasi = toplamKdv;
    if (tevkifatAktif) {
      if (tevkifatOran1 === 0) {
        kdvSonrasi = 0;
      } else if (tevkifatOran1 > 0) {
        kdvSonrasi = toplamKdv * (tevkifatOran1 / 100);
      }
    }

    const toplamEl = document.getElementById('invoiceItemsToplam');
    const kdvEl = document.getElementById('invoiceItemsKdv');
    const genelToplamEl = document.getElementById('invoiceItemsGenelToplam');
    
    if (toplamEl) toplamEl.value = toplam.toFixed(2);
    if (kdvEl) kdvEl.value = kdvSonrasi.toFixed(2);
    if (genelToplamEl) genelToplamEl.value = (toplam + kdvSonrasi).toFixed(2);
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

<?php require __DIR__ . '/../partials/footer.php'; ?>
