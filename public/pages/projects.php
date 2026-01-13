<?php
/**
 * Projeler Listesi Sayfası - Server-Rendered
 * URL: /projects
 */

$pageTitle = 'Projeler';
$activeNav = 'islemler';
$currentPage = 'projects';

require __DIR__ . '/partials/header.php';
?>

    <!-- ===== VIEW: PROJELER ===== -->
    <div id="view-projects">
      <div class="card" id="panelProjects">
        <div class="card-header bg-primary text-white py-2 d-flex justify-content-between align-items-center">
          <span class="fw-semibold"><i class="bi bi-kanban me-2"></i>Projeler</span>
          <button type="button" class="btn btn-sm btn-light" data-panel-fullscreen="panelProjects" title="Tam Ekran">
            <i class="bi bi-arrows-fullscreen"></i>
          </button>
        </div>
        <div id="projectsToolbar"></div>
        <div class="card-body p-0" id="projectsTableContainer">
          <div class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary"></div></div>
        </div>
      </div>
    </div>

<?php require __DIR__ . '/partials/modals/project.php'; ?>
<?php require __DIR__ . '/partials/modals/entity-detail.php'; ?>
<?php require __DIR__ . '/partials/footer.php'; ?>
