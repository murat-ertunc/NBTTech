<?php



?>
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
