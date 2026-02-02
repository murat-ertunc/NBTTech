<?php





$MusteriId = $MusteriId ?? 0;
$ProjeId = $ProjeId ?? 0;
$IsEdit = $ProjeId > 0;

$pageTitle = $IsEdit ? 'Proje Düzenle' : 'Yeni Proje';
$activeNav = 'customers';
$currentPage = 'project-form';


$FormMusteriId = $MusteriId;
$FormTabKey = 'projeler';
$FormTitle = $pageTitle;
$FormIcon = 'bi-kanban';
$FormBreadcrumb = $pageTitle;
$FormSaveButtonId = 'btnSaveProjectPage';
$FormPermission = 'projects.create,projects.update';
$FormButtonColor = 'primary';

require __DIR__ . '/../partials/header.php';
?>

<div class="container-fluid py-4">
  <div class="row justify-content-center">
    <div class="col-12">
      <?php require __DIR__ . '/../partials/form-header.php'; ?>
      
      <div class="card-body">
        <form id="projectPageForm">
          <div class="alert alert-danger d-none" id="projectFormError"></div>
          
          <input type="hidden" id="projectId" value="<?= (int)$ProjeId ?>">
          <input type="hidden" id="projectMusteriId" value="<?= (int)$MusteriId ?>">
          
          <div class="row mb-3">
            <label class="col-4 col-form-label">Proje Adı <span class="text-danger">*</span></label>
            <div class="col-8">
              <input type="text" class="form-control" id="projectName" required>
            </div>
          </div>
          
          <div class="row mb-3">
            <label class="col-4 col-form-label">Durum</label>
            <div class="col-8">
              <select class="form-select" id="projectStatus">
                <!-- Dinamik olarak doldurulacak -->
              </select>
            </div>
          </div>
        </form>
      </div>
      
      <?php require __DIR__ . '/../partials/form-footer.php'; ?>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  NbtPageForm.init('project', <?= (int)$MusteriId ?>, <?= (int)$ProjeId ?>, 'projeler');
});
</script>

<?php require __DIR__ . '/../partials/footer.php'; ?>
