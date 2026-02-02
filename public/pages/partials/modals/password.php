<?php

?>
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
