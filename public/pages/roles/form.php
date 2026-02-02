<?php





$RolId = $RolId ?? 0;
$IsEdit = $RolId > 0;

$pageTitle = $IsEdit ? 'Rol Düzenle' : 'Yeni Rol';
$activeNav = 'sistem';
$currentPage = 'role-form';

require __DIR__ . '/../partials/header.php';
?>

<div class="container-fluid py-4">
  <div class="row justify-content-center">
    <div class="col-12">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="/roles">Roller</a></li>
            <li class="breadcrumb-item active"><?= $IsEdit ? 'Rol Düzenle' : 'Yeni Rol' ?></li>
          </ol>
        </nav>
        <a href="/roles" class="btn btn-outline-secondary">
          <i class="bi bi-arrow-left me-1"></i>Listeye Dön
        </a>
      </div>

      <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
          <h5 class="card-title mb-0"><i class="bi bi-shield-lock me-2"></i><?= $IsEdit ? 'Rol Düzenle' : 'Yeni Rol' ?></h5>
        </div>
        <div class="card-body">
          <form id="rolePageForm">
            <div class="alert alert-danger d-none" id="roleFormError"></div>

            <input type="hidden" id="roleId" value="<?= (int)$RolId ?>">

            <div class="row mb-3">
              <label class="col-4 col-form-label">Rol Adı <span class="text-danger">*</span></label>
              <div class="col-8">
                <input type="text" class="form-control" id="roleAdi" maxlength="50" required>
                <div class="invalid-feedback" id="roleAdiError"></div>
                <div class="form-text">Türkçe karakterler kullanabilirsiniz.</div>
              </div>
            </div>

            <div class="row mb-3">
              <label class="col-4 col-form-label">Rol Kodu <span class="text-danger">*</span></label>
              <div class="col-8">
                <input type="text" class="form-control" id="roleKodu" maxlength="30" required pattern="[a-z][a-z0-9_]{2,29}">
                <div class="invalid-feedback" id="roleKoduError"></div>
                <div class="form-text">Küçük harf, rakam ve alt çizgi (örn: editor, veri_girisi)</div>
              </div>
            </div>

            <div class="row mb-3">
              <label class="col-4 col-form-label">Durum</label>
              <div class="col-8">
                <select class="form-select" id="roleAktif">
                  <option value="1">Aktif</option>
                  <option value="0">Pasif</option>
                </select>
                <div class="invalid-feedback" id="roleAktifError"></div>
              </div>
            </div>
          </form>
        </div>
        <div class="card-footer d-flex justify-content-between">
          <a href="/roles" class="btn btn-secondary">
            <i class="bi bi-x-lg me-1"></i>İptal
          </a>
          <button type="button" class="btn btn-primary" id="btnSaveRolePage" data-can-any="roles.create,roles.update">
            <i class="bi bi-check-lg me-1"></i>Kaydet
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const RolId = <?= (int)$RolId ?>;
  NbtRolePageForm.init(RolId);
});
</script>

<?php require __DIR__ . '/../partials/footer.php'; ?>
