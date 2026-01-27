<?php
/**
 * Müşteri Modal - Ekle/Düzenle
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
          <label class="col-12 col-md-4 col-form-label">Ünvan <span class="text-danger">*</span></label>
          <div class="col-12 col-md-8">
            <input type="text" class="form-control" id="customerUnvan" maxlength="150" required>
            <small class="form-hint">Zorunlu, Min 2, Maks 150</small>
            <div class="invalid-feedback"></div>
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">Müşteri Kodu</label>
          <div class="col-12 col-md-8">
            <input type="text" class="form-control" id="customerMusteriKodu" maxlength="10" placeholder="Örn: ABC1234567" style="text-transform: uppercase;">
            <small class="form-hint">Maks 10 karakter</small>
            <div class="invalid-feedback"></div>
          </div>
        </div>
        
        <!-- Vergi Bilgileri -->
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">Vergi Dairesi <span class="text-danger">*</span></label>
          <div class="col-12 col-md-8">
            <input type="text" class="form-control" id="customerVergiDairesi" maxlength="50" required>
            <small class="form-hint">Zorunlu, Maks 50</small>
            <div class="invalid-feedback"></div>
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">Vergi Numarası <span class="text-danger">*</span></label>
          <div class="col-12 col-md-8">
            <input type="text" class="form-control" id="customerVergiNo" maxlength="11" placeholder="10 veya 11 haneli" required>
            <small class="form-hint">Zorunlu, 10-11 hane, Sayısal</small>
            <div class="invalid-feedback"></div>
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">Mersis No</label>
          <div class="col-12 col-md-8">
            <input type="text" class="form-control" id="customerMersisNo" maxlength="16" placeholder="16 haneli Mersis No">
            <small class="form-hint">Maks 16 hane</small>
            <div class="invalid-feedback"></div>
          </div>
        </div>
        
        <!-- İletişim Bilgileri -->
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">Telefon</label>
          <div class="col-12 col-md-8">
            <input type="tel" class="form-control" id="customerTelefon" maxlength="20" placeholder="(5xx) xxx xx xx">
            <small class="form-hint">Maks 20 karakter</small>
            <div class="invalid-feedback"></div>
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">Faks</label>
          <div class="col-12 col-md-8">
            <input type="tel" class="form-control" id="customerFaks" maxlength="20">
            <small class="form-hint">Maks 20 karakter</small>
            <div class="invalid-feedback"></div>
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">Web Sitesi</label>
          <div class="col-12 col-md-8">
            <input type="url" class="form-control" id="customerWeb" maxlength="150" placeholder="https://www.example.com">
            <small class="form-hint">Maks 150 karakter</small>
            <div class="invalid-feedback"></div>
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">İl</label>
          <div class="col-12 col-md-8">
            <input type="text" class="form-control" id="customerIl" maxlength="50" placeholder="Örn: İstanbul">
            <small class="form-hint">Maks 50 karakter</small>
            <div class="invalid-feedback"></div>
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">İlçe</label>
          <div class="col-12 col-md-8">
            <input type="text" class="form-control" id="customerIlce" maxlength="50" placeholder="Örn: Kadıköy">
            <small class="form-hint">Maks 50 karakter</small>
            <div class="invalid-feedback"></div>
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">Adres</label>
          <div class="col-12 col-md-8">
            <textarea class="form-control" id="customerAdres" rows="2" maxlength="300"></textarea>
            <small class="form-hint">Maks 300 karakter</small>
            <div class="invalid-feedback"></div>
          </div>
        </div>
        <div class="row mb-3">
          <label class="col-12 col-md-4 col-form-label">Açıklama</label>
          <div class="col-12 col-md-8">
            <textarea class="form-control" id="customerAciklama" rows="2" maxlength="500"></textarea>
            <small class="form-hint">Maks 500 karakter</small>
            <div class="invalid-feedback"></div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
        <button type="button" class="btn btn-primary" id="btnSaveCustomer" data-can-any="customers.create,customers.update">Kaydet</button>
      </div>
    </div>
  </div>
</div>
