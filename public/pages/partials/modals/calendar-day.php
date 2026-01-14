<?php
/**
 * Takvim Gün Detay Modalı
 * Dashboard'da bir güne tıklandığında o günün etkinliklerini gösterir
 */
?>
<div class="modal fade" id="calendarDayModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-success text-white py-2">
        <h6 class="modal-title" id="calendarDayModalTitle">
          <i class="bi bi-calendar3 me-2"></i>Günün Etkinlikleri
        </h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-0">
        <div id="calendarDayEventList" class="list-group list-group-flush">
          <!-- Etkinlikler buraya yüklenecek -->
        </div>
        <div id="calendarDayNoEvents" class="text-center py-4 text-muted d-none">
          <i class="bi bi-calendar-x fs-2 d-block mb-2"></i>
          <p class="mb-0">Bu tarihte etkinlik bulunmuyor</p>
        </div>
      </div>
      <div class="modal-footer py-2">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Kapat</button>
      </div>
    </div>
  </div>
</div>
