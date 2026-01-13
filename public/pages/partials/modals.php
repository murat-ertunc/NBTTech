<?php
/**
 * Modals Partial - Tüm Modal Formları
 * Server-Rendered Sayfa Mimarisi
 * 
 * Kullanım:
 *   require __DIR__ . '/partials/modals.php';
 */
?>

<!-- Müşteri Ekle/Düzenle Modal -->
<div class="modal fade" id="customerModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="customerModalTitle">Yeni Müşteri</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-danger d-none modal-error" id="customerModalError"></div>
        <input type="hidden" id="customerId">
        
        <!-- Temel Bilgiler -->
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">Unvan <span class="text-danger">*</span></label>
          <div class="col-12 col-md-8">
            <input type="text" class="form-control" id="customerUnvan" required>
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">Müşteri Kodu</label>
          <div class="col-12 col-md-8">
            <input type="text" class="form-control" id="customerMusteriKodu" placeholder="Örn: MUS-001">
          </div>
        </div>
        
        <!-- Vergi Bilgileri -->
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">Vergi Dairesi</label>
          <div class="col-12 col-md-8">
            <input type="text" class="form-control" id="customerVergiDairesi">
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">Vergi Numarası</label>
          <div class="col-12 col-md-8">
            <input type="text" class="form-control" id="customerVergiNo" maxlength="11">
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">Mersis No</label>
          <div class="col-12 col-md-8">
            <input type="text" class="form-control" id="customerMersisNo" maxlength="16" placeholder="Mersis numarası">
          </div>
        </div>
        
        <!-- İletişim Bilgileri -->
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">Telefon</label>
          <div class="col-12 col-md-8">
            <input type="tel" class="form-control" id="customerTelefon" placeholder="(5xx) xxx xx xx">
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">Faks</label>
          <div class="col-12 col-md-8">
            <input type="tel" class="form-control" id="customerFaks">
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">Web Sitesi</label>
          <div class="col-12 col-md-8">
            <input type="url" class="form-control" id="customerWeb" placeholder="https://www.example.com">
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">Adres</label>
          <div class="col-12 col-md-8">
            <textarea class="form-control" id="customerAdres" rows="2"></textarea>
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">Açıklama</label>
          <div class="col-12 col-md-8">
            <textarea class="form-control" id="customerAciklama" rows="2"></textarea>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
        <button type="button" class="btn btn-primary" id="btnSaveCustomer">Kaydet</button>
      </div>
    </div>
  </div>
</div>

<!-- Fatura Modal -->
<div class="modal fade" id="invoiceModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="invoiceModalTitle">Yeni Fatura</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-danger d-none modal-error" id="invoiceModalError"></div>
        <input type="hidden" id="invoiceId">
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">Müşteri <span class="text-danger">*</span></label>
          <div class="col-12 col-md-8">
            <select class="form-select" id="invoiceMusteriId" required>
              <option value="">Seçiniz...</option>
            </select>
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">Proje <span class="text-danger">*</span></label>
          <div class="col-12 col-md-8">
            <select class="form-select" id="invoiceProjeId" required>
              <option value="">Proje Seçiniz...</option>
            </select>
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">Tarih</label>
          <div class="col-12 col-md-8">
            <input type="date" class="form-control" id="invoiceTarih" value="<?= date('Y-m-d') ?>">
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">Tutar</label>
          <div class="col-12 col-md-8">
            <div class="input-group">
              <input type="number" step="0.01" class="form-control" id="invoiceTutar">
              <select class="form-select" id="invoiceDoviz" style="max-width: 90px;">
                <option value="TRY">TRY</option>
                <option value="USD">USD</option>
                <option value="EUR">EUR</option>
              </select>
            </div>
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">Açıklama</label>
          <div class="col-12 col-md-8">
            <textarea class="form-control" id="invoiceAciklama" rows="2"></textarea>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
        <button type="button" class="btn btn-primary" id="btnSaveInvoice">Kaydet</button>
      </div>
    </div>
  </div>
</div>

<!-- Ödeme Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="paymentModalTitle">Yeni Ödeme</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-danger d-none modal-error" id="paymentModalError"></div>
        <input type="hidden" id="paymentId">
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">Müşteri <span class="text-danger">*</span></label>
          <div class="col-12 col-md-8">
            <select class="form-select" id="paymentMusteriId" required>
              <option value="">Seçiniz...</option>
            </select>
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">Fatura</label>
          <div class="col-12 col-md-8">
            <select class="form-select" id="paymentFaturaId">
              <option value="">Fatura Seçiniz (Opsiyonel)...</option>
            </select>
            <small class="text-muted">Fatura seçerseniz ödeme o faturaya bağlanır.</small>
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">Tarih</label>
          <div class="col-12 col-md-8">
            <input type="date" class="form-control" id="paymentTarih" value="<?= date('Y-m-d') ?>">
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">Tutar</label>
          <div class="col-12 col-md-8">
            <input type="number" step="0.01" class="form-control" id="paymentTutar">
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">Açıklama</label>
          <div class="col-12 col-md-8">
            <textarea class="form-control" id="paymentAciklama" rows="2"></textarea>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
        <button type="button" class="btn btn-primary" id="btnSavePayment">Kaydet</button>
      </div>
    </div>
  </div>
</div>

<!-- Proje Modal -->
<div class="modal fade" id="projectModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="projectModalTitle">Yeni Proje</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-danger d-none modal-error" id="projectModalError"></div>
        <input type="hidden" id="projectId">
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">Müşteri <span class="text-danger">*</span></label>
          <div class="col-12 col-md-8">
            <select class="form-select" id="projectMusteriId" required>
              <option value="">Seçiniz...</option>
            </select>
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">Proje Adı <span class="text-danger">*</span></label>
          <div class="col-12 col-md-8">
            <input type="text" class="form-control" id="projectName" required>
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">Başlangıç</label>
          <div class="col-12 col-md-8">
            <input type="date" class="form-control" id="projectStart">
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">Bitiş</label>
          <div class="col-12 col-md-8">
            <input type="date" class="form-control" id="projectEnd">
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">Durum</label>
          <div class="col-12 col-md-8">
            <select class="form-select" id="projectStatus">
              <option value="1">Aktif</option>
              <option value="2">Tamamlandı</option>
              <option value="3">İptal</option>
            </select>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
        <button type="button" class="btn btn-primary" id="btnSaveProject">Kaydet</button>
      </div>
    </div>
  </div>
</div>

<!-- Teklif Modal -->
<div class="modal fade" id="offerModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="offerModalTitle">Yeni Teklif</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-danger d-none modal-error" id="offerModalError"></div>
        <input type="hidden" id="offerId">
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">Müşteri <span class="text-danger">*</span></label>
          <div class="col-12 col-md-8">
            <select class="form-select" id="offerMusteriId" required>
              <option value="">Seçiniz...</option>
            </select>
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">Proje <span class="text-danger">*</span></label>
          <div class="col-12 col-md-8">
            <select class="form-select" id="offerProjeId" required>
              <option value="">Proje Seçiniz...</option>
            </select>
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">Teklif No</label>
          <div class="col-12 col-md-8">
            <input type="text" class="form-control" id="offerNo">
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">Konu</label>
          <div class="col-12 col-md-8">
            <input type="text" class="form-control" id="offerSubject">
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">Tutar</label>
          <div class="col-12 col-md-8">
            <div class="input-group">
              <input type="number" step="0.01" class="form-control" id="offerAmount">
              <select class="form-select" id="offerCurrency" style="max-width: 90px;">
                <option value="TRY">TRY</option>
                <option value="USD">USD</option>
                <option value="EUR">EUR</option>
              </select>
            </div>
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">Tarih</label>
          <div class="col-12 col-md-8">
            <input type="date" class="form-control" id="offerDate" value="<?= date('Y-m-d') ?>">
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">Geçerlilik</label>
          <div class="col-12 col-md-8">
            <input type="date" class="form-control" id="offerValidDate">
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">Durum</label>
          <div class="col-12 col-md-8">
            <select class="form-select" id="offerStatus">
              <option value="0">Taslak</option>
              <option value="1">Gönderildi</option>
              <option value="2">Onaylandı</option>
              <option value="3">Reddedildi</option>
            </select>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
        <button type="button" class="btn btn-primary" id="btnSaveOffer">Kaydet</button>
      </div>
    </div>
  </div>
</div>

<!-- Sözleşme Modal -->
<div class="modal fade" id="contractModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="contractModalTitle">Yeni Sözleşme</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-danger d-none modal-error" id="contractModalError"></div>
        <input type="hidden" id="contractId">
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">Müşteri <span class="text-danger">*</span></label>
          <div class="col-12 col-md-8">
            <select class="form-select" id="contractMusteriId" required>
              <option value="">Seçiniz...</option>
            </select>
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">Proje <span class="text-danger">*</span></label>
          <div class="col-12 col-md-8">
            <select class="form-select" id="contractProjeId" required>
              <option value="">Proje Seçiniz...</option>
            </select>
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">Sözleşme No</label>
          <div class="col-12 col-md-8">
            <input type="text" class="form-control" id="contractNo">
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">Başlangıç</label>
          <div class="col-12 col-md-8">
            <input type="date" class="form-control" id="contractStart">
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">Bitiş</label>
          <div class="col-12 col-md-8">
            <input type="date" class="form-control" id="contractEnd">
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">Tutar</label>
          <div class="col-12 col-md-8">
            <div class="input-group">
              <input type="number" step="0.01" class="form-control" id="contractAmount">
              <select class="form-select" id="contractCurrency" style="max-width: 90px;">
                <option value="TRY">TRY</option>
                <option value="USD">USD</option>
                <option value="EUR">EUR</option>
              </select>
            </div>
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">Durum</label>
          <div class="col-12 col-md-8">
            <select class="form-select" id="contractStatus">
              <option value="1">Aktif</option>
              <option value="2">Pasif</option>
              <option value="3">İptal</option>
            </select>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
        <button type="button" class="btn btn-primary" id="btnSaveContract">Kaydet</button>
      </div>
    </div>
  </div>
</div>

<!-- Teminat Modal -->
<div class="modal fade" id="guaranteeModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="guaranteeModalTitle">Yeni Teminat</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-danger d-none modal-error" id="guaranteeModalError"></div>
        <input type="hidden" id="guaranteeId">
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">Müşteri <span class="text-danger">*</span></label>
          <div class="col-12 col-md-8">
            <select class="form-select" id="guaranteeMusteriId" required>
              <option value="">Seçiniz...</option>
            </select>
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">Proje <span class="text-danger">*</span></label>
          <div class="col-12 col-md-8">
            <select class="form-select" id="guaranteeProjeId" required>
              <option value="">Proje Seçiniz...</option>
            </select>
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">Belge No</label>
          <div class="col-12 col-md-8">
            <input type="text" class="form-control" id="guaranteeNo">
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">Tür</label>
          <div class="col-12 col-md-8">
            <select class="form-select" id="guaranteeType">
              <option value="Nakit">Nakit</option>
              <option value="Teminat Mektubu">Teminat Mektubu</option>
              <option value="Çek">Çek</option>
              <option value="Senet">Senet</option>
            </select>
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">Banka</label>
          <div class="col-12 col-md-8">
            <input type="text" class="form-control" id="guaranteeBank">
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">Tutar</label>
          <div class="col-12 col-md-8">
            <div class="input-group">
              <input type="number" step="0.01" class="form-control" id="guaranteeAmount">
              <select class="form-select" id="guaranteeCurrency" style="max-width: 90px;">
                <option value="TRY">TRY</option>
                <option value="USD">USD</option>
                <option value="EUR">EUR</option>
              </select>
            </div>
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">Vade Tarihi</label>
          <div class="col-12 col-md-8">
            <input type="date" class="form-control" id="guaranteeDate">
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">Durum</label>
          <div class="col-12 col-md-8">
            <select class="form-select" id="guaranteeStatus">
              <option value="1">Bekliyor</option>
              <option value="2">İade Edildi</option>
              <option value="3">Tahsil Edildi</option>
              <option value="4">Yandı</option>
            </select>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
        <button type="button" class="btn btn-primary" id="btnSaveGuarantee">Kaydet</button>
      </div>
    </div>
  </div>
</div>

<!-- Kullanıcı Ekle Modal -->
<div class="modal fade" id="userModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="userModalTitle">Yeni Kullanıcı</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-danger d-none modal-error" id="userModalError"></div>
        <input type="hidden" id="userId">
        <div class="mb-3">
          <label class="form-label">Ad Soyad <span class="text-danger">*</span></label>
          <input type="text" class="form-control" id="userAdSoyad" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Kullanıcı Adı <span class="text-danger">*</span></label>
          <input type="text" class="form-control" id="userKullaniciAdi" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Şifre <span class="text-danger">*</span></label>
          <input type="password" class="form-control" id="userSifre">
          <small class="text-muted">Düzenlemede boş bırakırsanız şifre değişmez.</small>
        </div>
        <div class="mb-3">
          <label class="form-label">Rol</label>
          <select class="form-select" id="userRol">
            <option value="user">Kullanıcı</option>
            <option value="admin">Admin</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
        <button type="button" class="btn btn-primary" id="btnSaveUser">Kaydet</button>
      </div>
    </div>
  </div>
</div>

<!-- Şifre Değiştir Modal -->
<div class="modal fade" id="passwordModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-key me-2"></i>Şifre Değiştir</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-danger d-none modal-error" id="passwordModalError"></div>
        <div class="mb-3">
          <label class="form-label">Mevcut Şifre</label>
          <input type="password" class="form-control" id="currentPassword" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Yeni Şifre</label>
          <input type="password" class="form-control" id="newPassword" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Yeni Şifre (Tekrar)</label>
          <input type="password" class="form-control" id="confirmPassword" required>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
        <button type="button" class="btn btn-primary" id="btnChangePassword">Değiştir</button>
      </div>
    </div>
  </div>
</div>

<!-- Görüşme Modal -->
<div class="modal fade" id="meetingModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="meetingModalTitle"><i class="bi bi-chat-dots me-2"></i>Yeni Görüşme</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-danger d-none modal-error" id="meetingModalError"></div>
        <input type="hidden" id="meetingId">
        <input type="hidden" id="meetingMusteriId">
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">Proje <span class="text-danger">*</span></label>
          <div class="col-12 col-md-8">
            <select class="form-select" id="meetingProjeId" required>
              <option value="">Proje Seçiniz...</option>
            </select>
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">Tarih <span class="text-danger">*</span></label>
          <div class="col-12 col-md-8">
            <input type="date" class="form-control" id="meetingTarih" required>
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">Konu <span class="text-danger">*</span></label>
          <div class="col-12 col-md-8">
            <input type="text" class="form-control" id="meetingKonu" required>
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">Görüşülen Kişi</label>
          <div class="col-12 col-md-8">
            <input type="text" class="form-control" id="meetingKisi">
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">Notlar</label>
          <div class="col-12 col-md-8">
            <textarea class="form-control" id="meetingNotlar" rows="3"></textarea>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
        <button type="button" class="btn btn-primary" id="btnSaveMeeting">Kaydet</button>
      </div>
    </div>
  </div>
</div>

<!-- Kişi Modal -->
<div class="modal fade" id="contactModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="contactModalTitle"><i class="bi bi-person me-2"></i>Yeni Kişi</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-danger d-none modal-error" id="contactModalError"></div>
        <input type="hidden" id="contactId">
        <input type="hidden" id="contactMusteriId">
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">Ad Soyad <span class="text-danger">*</span></label>
          <div class="col-12 col-md-8">
            <input type="text" class="form-control" id="contactAdSoyad" required>
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">Ünvan / Pozisyon</label>
          <div class="col-12 col-md-8">
            <input type="text" class="form-control" id="contactUnvan">
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">Telefon</label>
          <div class="col-12 col-md-8">
            <input type="tel" class="form-control" id="contactTelefon">
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">Dahili No</label>
          <div class="col-12 col-md-8">
            <input type="text" class="form-control" id="contactDahiliNo">
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">E-posta</label>
          <div class="col-12 col-md-8">
            <input type="email" class="form-control" id="contactEmail">
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">Notlar</label>
          <div class="col-12 col-md-8">
            <textarea class="form-control" id="contactNotlar" rows="2"></textarea>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
        <button type="button" class="btn btn-primary" id="btnSaveContact">Kaydet</button>
      </div>
    </div>
  </div>
</div>

<!-- Damga Vergisi Modal -->
<div class="modal fade" id="stampTaxModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="stampTaxModalTitle"><i class="bi bi-percent me-2"></i>Yeni Damga Vergisi</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-danger d-none modal-error" id="stampTaxModalError"></div>
        <input type="hidden" id="stampTaxId">
        <input type="hidden" id="stampTaxMusteriId">
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">Proje <span class="text-danger">*</span></label>
          <div class="col-12 col-md-8">
            <select class="form-select" id="stampTaxProjeId" required>
              <option value="">Proje Seçiniz...</option>
            </select>
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">Tarih <span class="text-danger">*</span></label>
          <div class="col-12 col-md-8">
            <input type="date" class="form-control" id="stampTaxTarih" required>
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">Tutar <span class="text-danger">*</span></label>
          <div class="col-12 col-md-8">
            <div class="input-group">
              <input type="number" step="0.01" class="form-control" id="stampTaxTutar" required>
              <select class="form-select" id="stampTaxDovizCinsi" style="max-width: 90px;">
                <option value="TRY">TRY</option>
                <option value="USD">USD</option>
                <option value="EUR">EUR</option>
              </select>
            </div>
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">Belge No</label>
          <div class="col-12 col-md-8">
            <input type="text" class="form-control" id="stampTaxBelgeNo">
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">Açıklama</label>
          <div class="col-12 col-md-8">
            <textarea class="form-control" id="stampTaxAciklama" rows="2"></textarea>
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">PDF Dosya</label>
          <div class="col-12 col-md-8">
            <input type="file" class="form-control" id="stampTaxDosya" accept=".pdf,application/pdf">
            <div class="form-text text-muted">Sadece PDF. Maks. 10MB.</div>
            <div class="invalid-feedback" id="stampTaxDosyaError"></div>
            <div class="mt-2 d-none" id="stampTaxCurrentFile">
              <span class="badge bg-secondary me-2"><i class="bi bi-file-pdf me-1"></i><span id="stampTaxCurrentFileName"></span></span>
              <button type="button" class="btn btn-sm btn-outline-danger" id="btnRemoveStampTaxFile"><i class="bi bi-x"></i></button>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
        <button type="button" class="btn btn-primary" id="btnSaveStampTax">Kaydet</button>
      </div>
    </div>
  </div>
</div>

<!-- Dosya Yükleme Modal -->
<div class="modal fade" id="fileModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="fileModalTitle"><i class="bi bi-upload me-2"></i>Dosya Yükle</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-danger d-none modal-error" id="fileModalError"></div>
        <input type="hidden" id="fileMusteriId">
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">Proje <span class="text-danger">*</span></label>
          <div class="col-12 col-md-8">
            <select class="form-select" id="fileProjeId" required>
              <option value="">Proje Seçiniz...</option>
            </select>
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">Dosya Seç <span class="text-danger">*</span></label>
          <div class="col-12 col-md-8">
            <input type="file" class="form-control" id="fileInput" required
              accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,image/jpeg,image/png,image/gif">
            <div class="form-text text-muted">PDF, Word, Excel, Resim. Maks. 10MB.</div>
            <div class="invalid-feedback" id="fileInputError"></div>
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">Açıklama</label>
          <div class="col-12 col-md-8">
            <input type="text" class="form-control" id="fileAciklama">
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
        <button type="button" class="btn btn-primary" id="btnSaveFile">Yükle</button>
      </div>
    </div>
  </div>
</div>

<!-- Genel Detay Görüntüleme Modal (Read-Only) -->
<div class="modal fade" id="entityDetailModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-light">
        <h5 class="modal-title" id="entityDetailModalTitle"><i class="bi bi-eye me-2"></i>Detay</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="entityDetailModalBody">
        <!-- Dinamik içerik buraya gelecek -->
      </div>
      <div class="modal-footer d-flex justify-content-between">
        <button type="button" class="btn btn-outline-danger" id="btnEntityDetailDelete">
          <i class="bi bi-trash me-1"></i>Sil
        </button>
        <div>
          <button type="button" class="btn btn-outline-secondary d-none" id="btnEntityDetailPage">
            <i class="bi bi-arrow-up-right-square me-1"></i>Detay Sayfası
          </button>
          <button type="button" class="btn btn-outline-primary" id="btnEntityDetailEdit">
            <i class="bi bi-pencil me-1"></i>Düzenle
          </button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
        </div>
      </div>
    </div>
  </div>
</div>
