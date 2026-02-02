<?php

$pageTitle = 'Rol Yönetimi';
$activeNav = 'sistem';
$currentPage = 'roles';

require __DIR__ . '/partials/header.php';
?>

    <div class="container-fluid py-4" data-can="roles.read">

    <!-- Rol Listesi -->
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white py-2">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-list-ul me-2"></i>Rol Listesi</h5>
                <a href="/roles/new" class="btn btn-light" data-can="roles.create">
                    <i class="bi bi-plus-lg me-1"></i>
                    Yeni Rol
                </a>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="rolTablosu">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 200px;">Rol Adı</th>
                            <th style="width: 150px;">Rol Kodu</th>
                            <th style="width: 100px;">Kullanıcı</th>
                            <th style="width: 100px;">Yetki</th>
                            <th style="width: 80px;">Durum</th>
                            <th style="width: 120px;">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody id="rolListesi">
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Yükleniyor...</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Permission Atama Modal -->
<div class="modal fade" id="permissionModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-key me-2"></i>
                    Yetki Ataması: <span id="permissionRolAdi"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="permissionRolId">

                <div class="alert alert-info mb-3">
                    <i class="bi bi-info-circle me-1"></i>
                    Sadece sahip olduğunuz yetkileri atayabilirsiniz.
                </div>

                <div id="permissionContainer" style="max-height: 500px; overflow-y: auto;">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <div class="me-auto">
                    <button type="button" class="btn btn-outline-primary btn-sm" id="btnTumunuSec">Tümünü Seç</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="btnTumunuKaldir">Tümünü Kaldır</button>
                </div>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="button" class="btn btn-primary" id="btnPermissionKaydet" data-can="roles.update">
                    <i class="bi bi-save me-1"></i> Kaydet
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', async function() {
    // Permission sisteminin hazir olmasini bekle (pages.js yukleyecek)
    const ready = await NbtPermission.waitForReady(3000);

    if (!ready) {
        // Timeout olduysa manuel yukle
        await NbtPermission.load();
    }

    // Permission kontrolu
    if (!NbtPermission.can('roles.read')) {
        NbtToast.error('Bu sayfaya erişim yetkiniz yok.');
        window.location.href = '/dashboard';
        return;
    }

    // UI'a permission kurallarini uygula
    NbtPermission.applyToElements();

    const flashKey = 'roles_flash_success';
    const flashMsg = sessionStorage.getItem(flashKey);
    if (flashMsg) {
        sessionStorage.removeItem(flashKey);
        NbtToast.success(flashMsg);
    }

    const permissionModal = new bootstrap.Modal(document.getElementById('permissionModal'));

    // State
    let tumRoller = [];
    let tumPermissionlar = [];
    let modulBazindaPermissionlar = {};

    // Rolleri yukle
    function normalizeArray(maybeArray) {
        return Array.isArray(maybeArray) ? maybeArray : [];
    }

    async function rolleriYukle() {
        try {
            const resp = await NbtApi.get('/api/roles');
            // API yaniti {data: [...]} veya dogrudan [...] formatinda olabilir
            const data = resp?.data ?? resp;
            tumRoller = normalizeArray(data);
            sessionStorage.setItem('roles_cache', JSON.stringify(tumRoller));
            if (!Array.isArray(data) && resp?.error) {
                NbtToast.error(resp.error);
            }
            tabloGuncelle();
        } catch (err) {
            NbtToast.error(err?.message || 'Roller yuklenemedi');
            const cached = sessionStorage.getItem('roles_cache');
            tumRoller = cached ? normalizeArray(JSON.parse(cached)) : [];
            tabloGuncelle();
        }
    }

    // Tabloyu guncelle
    function tabloGuncelle() {
        const tbody = document.getElementById('rolListesi');

        if (!Array.isArray(tumRoller) || tumRoller.length === 0) {
            tbody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-muted">Kayıt bulunamadı</td></tr>`;
            return;
        }

        tbody.innerHTML = tumRoller.map(rol => {
            const duzenlenebilir = rol.Duzenlenebilir == 1;
            const editDisabled = rol.SistemRolu == 1 || !duzenlenebilir;
            const editTitle = editDisabled
                ? 'Bu rolü düzenleme yetkiniz yok'
                : 'Düzenle';
            const permTitle = editDisabled
                ? 'Bu rolün yetkilerini düzenleme yetkiniz yok'
                : 'Yetkiler';
            return `
            <tr>
                <td>
                    <div class="fw-medium">${NbtUtils.escapeHtml(rol.RolAdi)}</div>
                    ${rol.SistemRolu == 1 ? '<span class="badge bg-primary">Sistem</span>' : ''}
                </td>
                <td><code>${NbtUtils.escapeHtml(rol.RolKodu)}</code></td>
                <td>
                    <span class="badge bg-info">${rol.KullaniciSayisi || 0}</span>
                </td>
                <td>
                    <span class="badge bg-success">${rol.PermissionSayisi || 0}</span>
                </td>
                <td>
                    ${rol.Aktif == 1
                        ? '<span class="badge bg-success">Aktif</span>'
                        : '<span class="badge bg-secondary">Pasif</span>'}
                </td>
                <td>
                    <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-outline-primary" onclick="yetkiAta(${rol.Id})" title="${permTitle}"
                            ${editDisabled ? 'disabled' : ''}>
                            <i class="bi bi-key"></i>
                        </button>
                        <a class="btn btn-outline-secondary ${editDisabled ? 'disabled' : ''}"
                            href="${editDisabled ? 'javascript:void(0)' : `/roles/${rol.Id}/edit`}" title="${editTitle}"
                            data-can="roles.update" ${editDisabled ? 'tabindex="-1" aria-disabled="true"' : ''}>
                            <i class="bi bi-pencil-square"></i>
                        </a>
                        <button type="button" class="btn btn-outline-danger" onclick="rolSil(${rol.Id})" title="Sil"
                            data-can="roles.delete" ${rol.SistemRolu == 1 ? 'disabled' : ''}>
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
        }).join('');

        // Permission kontrollerini uygula
        NbtPermission.applyToElements();
    }

    // Rol silme - SweetAlert2 ile onay
    window.rolSil = async function(id) {
        const rol = tumRoller.find(r => r.Id == id);
        if (!rol) return;

        // SweetAlert2 onay dialogu
        const result = await Swal.fire({
            title: 'Rol Silme',
            html: `
                <div class="text-start">
                    <p class="mb-2"><strong>Rol Adı:</strong> ${NbtUtils.escapeHtml(rol.RolAdi)}</p>
                    <p class="mb-2"><strong>Rol Kodu:</strong> <code>${NbtUtils.escapeHtml(rol.RolKodu)}</code></p>
                    <p class="mb-2"><strong>Etkilenen Kullanıcı:</strong> <span class="badge bg-warning">${rol.KullaniciSayisi || 0} kişi</span></p>
                    <hr>
                    <p class="text-danger mb-0"><i class="bi bi-exclamation-triangle me-1"></i>Bu işlem geri alınamaz!</p>
                </div>
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="bi bi-trash me-1"></i>Evet, Sil',
            cancelButtonText: 'İptal',
            reverseButtons: true
        });

        if (!result.isConfirmed) return;

        try {
            const resp = await NbtApi.delete('/api/roles/' + id);
            if (resp && (resp.success !== false)) {
                NbtToast.success('Rol silindi');
                rolleriYukle();
            } else {
                NbtToast.error(resp.message || resp.error || 'Silinemedi');
            }
        } catch (err) {
            NbtToast.error(err?.message || 'Silme işlemi başarısız');
        }
    };

    // Yetki atama
    window.yetkiAta = async function(rolId) {
        const rol = tumRoller.find(r => r.Id == rolId);
        if (!rol) return;

        document.getElementById('permissionRolId').value = rolId;
        document.getElementById('permissionRolAdi').textContent = rol.RolAdi;

        // Permissionlari ve mevcut kullanicinin yetkilerini yukle
        try {
            const [permResp, rolPermResp, myPermResp] = await Promise.all([
                NbtApi.get('/api/permissions'),
                NbtApi.get('/api/roles/' + rolId + '/permissions'),
                NbtApi.get('/api/auth/permissions')
            ]);

            const permData = permResp.data || permResp || {};
            if (permData) {
                tumPermissionlar = permData.tumPermissionlar || [];
                modulBazindaPermissionlar = permData.modulBazinda || {};
            }

            const rolPermissionKodlari = (rolPermResp.data || []).map(p => p.PermissionKodu);

            // Mevcut kullanicinin yetkileri (subset constraint icin)
            // API {roller, permissionlar, moduller} formatinda dondurur
            const myPermData = myPermResp.data || myPermResp || {};
            const benimYetkilerim = Array.isArray(myPermData)
                ? myPermData
                : (Array.isArray(myPermData.permissionlar) ? myPermData.permissionlar : []);

            // UI olustur - Turkce ceviri destegi
            const container = document.getElementById('permissionContainer');
            let html = '';

            for (const [modul, modulData] of Object.entries(modulBazindaPermissionlar)) {
                const modulAdiTr = modulData.modulAdiTr || modul.charAt(0).toUpperCase() + modul.slice(1);
                const permler = modulData.permissionlar || modulData;

                html += `
                    <div class="card mb-2 permission-modul" data-modul="${modul}" data-modul-tr="${modulAdiTr}">
                        <div class="card-header bg-light py-2">
                            <div class="form-check">
                                <input class="form-check-input modul-checkbox" type="checkbox" id="modul_${modul}" data-modul="${modul}">
                                <label class="form-check-label fw-bold text-primary" for="modul_${modul}">
                                    <i class="bi bi-folder me-1"></i>${modulAdiTr}
                                </label>
                            </div>
                        </div>
                        <div class="card-body py-2">
                            <div class="row">
                                ${(Array.isArray(permler) ? permler : []).map(p => {
                                    const aksiyonTr = p.AksiyonTr || p.Aksiyon;
                                    const etiket = p.PermissionAdiTr || aksiyonTr || p.PermissionKodu;
                                    const benim = benimYetkilerim.includes(p.PermissionKodu);
                                    const disabledAttr = benim ? '' : 'disabled';
                                    const disabledClass = benim ? '' : 'text-muted';
                                    const disabledTitle = benim ? '' : 'title="Bu yetkiyi atayamazsınız (kendi yetkiniz değil)"';
                                    return `
                                    <div class="col-md-3 col-sm-6 permission-item ${disabledClass}" data-label="${(p.PermissionAdiTr || p.PermissionKodu).toLowerCase()}">
                                        <div class="form-check">
                                            <input class="form-check-input permission-checkbox" type="checkbox"
                                                id="perm_${p.Id}"
                                                data-id="${p.Id}"
                                                data-kod="${p.PermissionKodu}"
                                                data-modul="${modul}"
                                                ${rolPermissionKodlari.includes(p.PermissionKodu) ? 'checked' : ''}
                                                ${disabledAttr}>
                                            <label class="form-check-label ${disabledClass}" for="perm_${p.Id}" ${disabledTitle}>
                                                ${etiket}
                                                ${!benim ? '<i class="bi bi-lock-fill ms-1 text-secondary" style="font-size: 0.7rem;"></i>' : ''}
                                            </label>
                                        </div>
                                    </div>
                                `}).join('')}
                            </div>
                        </div>
                    </div>
                `;
            }

            container.innerHTML = html;

            // Modul checkbox'lari - sadece enabled olanlari toggle et
            document.querySelectorAll('.modul-checkbox').forEach(cb => {
                cb.addEventListener('change', function() {
                    const modul = this.dataset.modul;
                    const checked = this.checked;
                    // Sadece disabled olmayan checkbox'lari degistir
                    document.querySelectorAll(`.permission-checkbox[data-modul="${modul}"]:not(:disabled)`).forEach(pcb => {
                        pcb.checked = checked;
                    });
                });
            });

            // Modul checkbox durumlarini guncelle
            updateModulCheckboxes();

            document.querySelectorAll('.permission-checkbox').forEach(cb => {
                cb.addEventListener('change', updateModulCheckboxes);
            });

            permissionModal.show();

        } catch (err) {
            NbtToast.error(err?.message || 'Yetkiler yüklenemedi');
        }
    };

    function updateModulCheckboxes() {
        document.querySelectorAll('.modul-checkbox').forEach(mcb => {
            const modul = mcb.dataset.modul;
            // Sadece enabled checkbox'lari say
            const permCheckboxes = document.querySelectorAll(`.permission-checkbox[data-modul="${modul}"]:not(:disabled)`);
            const checkedCount = document.querySelectorAll(`.permission-checkbox[data-modul="${modul}"]:not(:disabled):checked`).length;

            if (checkedCount === 0) {
                mcb.checked = false;
                mcb.indeterminate = false;
            } else if (checkedCount === permCheckboxes.length) {
                mcb.checked = true;
                mcb.indeterminate = false;
            } else {
                mcb.checked = false;
                mcb.indeterminate = true;
            }
        });
    }

    // Tumunu sec/kaldir
    document.getElementById('btnTumunuSec').addEventListener('click', function() {
        document.querySelectorAll('.permission-checkbox').forEach(cb => cb.checked = true);
        document.querySelectorAll('.modul-checkbox').forEach(cb => { cb.checked = true; cb.indeterminate = false; });
    });

    document.getElementById('btnTumunuKaldir').addEventListener('click', function() {
        document.querySelectorAll('.permission-checkbox').forEach(cb => cb.checked = false);
        document.querySelectorAll('.modul-checkbox').forEach(cb => { cb.checked = false; cb.indeterminate = false; });
    });

    // Permission kaydet
    document.getElementById('btnPermissionKaydet').addEventListener('click', async function() {
        const rolId = document.getElementById('permissionRolId').value;
        const seciliPermissionIdler = [];

        document.querySelectorAll('.permission-checkbox:checked').forEach(cb => {
            seciliPermissionIdler.push(parseInt(cb.dataset.id));
        });

        try {
            const resp = await NbtApi.post('/api/roles/' + rolId + '/permissions', {
                permissions: seciliPermissionIdler
            });

            if (resp && (resp.success !== false)) {
                NbtToast.success('Yetkiler kaydedildi');
                permissionModal.hide();
                rolleriYukle();
                // Cache'i temizle
                NbtPermission.clearCache();
                await NbtPermission.load();
            } else {
                NbtToast.error(resp.message || resp.error || 'Kayıt başarısız');
            }
        } catch (err) {
            if (err?.fields) {
                const list = Object.values(err.fields).flat();
                if (list.length > 0) {
                    NbtToast.error(list[0]);
                    return;
                }
            }
            NbtToast.error(err?.message || 'Kayıt başarısız');
        }
    });

    // Sayfa yuklendiginde rolleri getir
    rolleriYukle();
});
</script>

<?php require __DIR__ . '/partials/footer.php'; ?>
