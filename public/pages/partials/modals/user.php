<?php
/**
 * Kullanıcı Modal - Ekle/Düzenle
 */
?>
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
