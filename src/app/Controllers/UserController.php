<?php

namespace App\Controllers;

use App\Core\Context;
use App\Core\Response;
use App\Core\Transaction;
use App\Repositories\CustomerRepository;
use App\Repositories\RoleRepository;
use App\Repositories\UserRepository;
use App\Services\Authorization\AuthorizationService;
use App\Services\Logger\ActionLogger;

/**
 * Kullanici Controller
 * 
 * Kullanici CRUD islemleri ve rol atamalari.
 * Yeni RBAC sistemi ile entegre.
 * 
 * @package App\Controllers
 */
class UserController
{
    /**
     * Kullanici listesi
     * GET /api/users
     */
    public static function index(): void
    {
        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Oturum gecersiz veya suresi dolmus.', 401);
            return;
        }
        
        $Repo = new UserRepository();
        
        // Permission bazli scope kontrolu
        $AuthService = AuthorizationService::getInstance();
        $TumunuGorebilir = $AuthService->tumunuGorebilirMi($KullaniciId, 'users');
        
        // Pagination parametreleri
        $Sayfa = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $Limit = isset($_GET['limit']) ? max(1, min(100, (int)$_GET['limit'])) : (int)env('PAGINATION_DEFAULT', 10);
        
        // Filtre parametreleri
        $Filtreler = [];
        if (!empty($_GET['adsoyad'])) {
            $Filtreler['adsoyad'] = $_GET['adsoyad'];
        }
        if (!empty($_GET['kullaniciadi'])) {
            $Filtreler['kullaniciadi'] = $_GET['kullaniciadi'];
        }
        if (!empty($_GET['roller_str'])) {
            $Filtreler['roller_str'] = $_GET['roller_str'];
        }
        if (isset($_GET['aktif']) && $_GET['aktif'] !== '') {
            $Filtreler['aktif'] = $_GET['aktif'];
        }
        
        // users.read_all yetkisi yoksa sadece kendi olusturdugu kullanicilari gorsun
        if (!$TumunuGorebilir) {
            $Filtreler['ekleyen_user_id'] = $KullaniciId;
        }
        
        // Pagination veya filtre varsa paginated sonuc dondur
        if (isset($_GET['page']) || isset($_GET['limit']) || !empty($Filtreler)) {
            $Sonuc = $Repo->tumKullanicilarPaginated($Sayfa, $Limit, $Filtreler);
            Response::json($Sonuc);
        } else {
            if ($TumunuGorebilir) {
                $Satirlar = $Repo->tumKullanicilar();
            } else {
                $Satirlar = $Repo->kullaniciyaGoreKullanicilar($KullaniciId);
            }
            Response::json(['data' => $Satirlar]);
        }
    }
    
    /**
     * Kullanici detayi
     * GET /api/users/{id}
     */
    public static function show(array $Parametreler): void
    {
        $Id = isset($Parametreler['id']) ? (int) $Parametreler['id'] : 0;
        
        if ($Id <= 0) {
            Response::error('Gecersiz kullanici ID.', 400);
            return;
        }
        
        $Repo = new UserRepository();
        $Kullanici = $Repo->bul($Id);
        
        if (!$Kullanici) {
            Response::error('Kullanici bulunamadi.', 404);
            return;
        }
        
        // Kullanicinin rollerini getir
        $RoleRepo = new RoleRepository();
        $Kullanici['Roller'] = $RoleRepo->kullaniciRolleriGetir($Id);
        
        // Parola gizle
        unset($Kullanici['Parola']);
        
        Response::json(['data' => $Kullanici]);
    }

    /**
     * Kullanici bloklama/aktiflestime
     * PUT /api/users/{id}/block
     */
    public static function block(array $Parametreler): void
    {
        $KullaniciId = Context::kullaniciId();
        $Id = isset($Parametreler['id']) ? (int) $Parametreler['id'] : 0;
        
        if ($Id <= 0) {
            Response::error('Gecersiz kullanici.', 422);
            return;
        }
        
        $Girdi = json_decode(file_get_contents('php://input'), true) ?: [];
        if (!array_key_exists('Aktif', $Girdi)) {
            Response::error('Aktif alani zorunludur.', 422);
            return;
        }
        
        $Aktif = (int) $Girdi['Aktif'] === 1 ? 1 : 0;
        $Repo = new UserRepository();
        $Mevcut = $Repo->bul($Id);
        
        if (!$Mevcut) {
            Response::error('Kullanici bulunamadi.', 404);
            return;
        }
        
        Transaction::wrap(function () use ($Repo, $Id, $Aktif, $KullaniciId) {
            $Repo->yedekle($Id, 'bck_tnm_user', $KullaniciId);
            $Repo->guncelle($Id, ['Aktif' => $Aktif], $KullaniciId);
            $Islem = $Aktif === 1 ? 'unblock' : 'block';
            ActionLogger::logla($Islem, 'tnm_user', ['Id' => $Id, 'Aktif' => $Aktif], 'ok');
        });

        Response::json(['status' => 'ok']);
    }

    /**
     * Kullanici silme
     * DELETE /api/users/{id}
     */
    public static function delete(array $Parametreler): void
    {
        $SilenKullaniciId = Context::kullaniciId();
        $Id = isset($Parametreler['id']) ? (int) $Parametreler['id'] : 0;
        
        if ($Id <= 0) {
            Response::error('Gecersiz kullanici.', 422);
            return;
        }
        
        $Repo = new UserRepository();
        $Mevcut = $Repo->bul($Id);
        
        if (!$Mevcut) {
            Response::error('Kullanici bulunamadi.', 404);
            return;
        }
        
        Transaction::wrap(function () use ($Id, $SilenKullaniciId, $Repo) {
            // Bagli musterileri sil
            $MusteriRepo = new CustomerRepository();
            $SilinenMusteriSayisi = $MusteriRepo->kullanicininMusterileriniSil($Id, $SilenKullaniciId);
            if ($SilinenMusteriSayisi > 0) {
                ActionLogger::delete('tbl_musteri', ['EkleyenUserId' => $Id, 'SilinenAdet' => $SilinenMusteriSayisi]);
            }
            
            // Kullanici rollerini sil
            $RoleRepo = new RoleRepository();
            $RoleRepo->kullaniciRolleriniTemizle($Id, $SilenKullaniciId);
            
            // Kullaniciyi sil
            $Repo->yedekle($Id, 'bck_tnm_user', $SilenKullaniciId);
            $Repo->softSil($Id, $SilenKullaniciId);
            ActionLogger::delete('tnm_user', ['Id' => $Id, 'BagliSilinenMusteri' => $SilinenMusteriSayisi]);
            
            // Cache temizle
            AuthorizationService::getInstance()->kullaniciCacheTemizle($Id);
        });

        Response::json(['status' => 'ok']);
    }

    /**
     * Yeni kullanici olustur
     * POST /api/users
     */
    public static function store(): void
    {
        $EkleyenKullaniciId = Context::kullaniciId();
        $Girdi = json_decode(file_get_contents('php://input'), true) ?: [];
        
        $AdSoyad = trim($Girdi['AdSoyad'] ?? '');
        $KullaniciAdi = trim($Girdi['KullaniciAdi'] ?? '');
        $Sifre = $Girdi['Sifre'] ?? '';
        $RolIdler = $Girdi['RolIdler'] ?? []; // Yeni RBAC: rol ID listesi
        
        // Validasyon
        if (!$AdSoyad || !$KullaniciAdi || !$Sifre) {
            Response::error('Tum alanlar zorunludur.', 422);
            return;
        }
        if (strlen($Sifre) < 6) {
            Response::error('Sifre en az 6 karakter olmalidir.', 422);
            return;
        }

        $Repo = new UserRepository();
        
        // Kullanici adi kontrolu
        $Mevcut = $Repo->kullaniciAdiylaAra($KullaniciAdi);
        if ($Mevcut) {
            Response::error('Bu kullanici adi zaten kullaniliyor.', 422);
            return;
        }
        
        // Rol atama subset kontrolu
        $AuthService = AuthorizationService::getInstance();
        $RoleRepo = new RoleRepository();
        
        if (!empty($RolIdler)) {
            foreach ($RolIdler as $RolId) {
                if (!$AuthService->rolAtayabilirMi($EkleyenKullaniciId, (int)$RolId)) {
                    Response::error('Bu rolu atama yetkiniz yok. Sadece kendi seviyenizden dusuk rolleri atayabilirsiniz.', 403);
                    return;
                }
            }
        }

        $SifreHash = password_hash($Sifre, PASSWORD_BCRYPT);
        
        Transaction::wrap(function () use ($Repo, $RoleRepo, $AdSoyad, $KullaniciAdi, $SifreHash, $RolIdler, $EkleyenKullaniciId) {
            // Kullanici olustur
            $YeniId = $Repo->ekle([
                'AdSoyad' => $AdSoyad,
                'KullaniciAdi' => $KullaniciAdi,
                'Parola' => $SifreHash,
                'Aktif' => 1
            ], $EkleyenKullaniciId);
            
            // Rolleri ata
            if (!empty($RolIdler)) {
                foreach ($RolIdler as $RolId) {
                    $RoleRepo->kullaniciyaRolAta($YeniId, (int)$RolId, $EkleyenKullaniciId);
                }
            }
            
            ActionLogger::insert('tnm_user', ['Id' => $YeniId], ['KullaniciAdi' => $KullaniciAdi, 'RolSayisi' => count($RolIdler)]);
        });

        Response::json(['status' => 'ok', 'message' => 'Kullanici olusturuldu.']);
    }

    /**
     * Kullanici guncelle
     * PUT /api/users/{id}
     */
    public static function update(array $Parametreler): void
    {
        $GuncelleyenKullaniciId = Context::kullaniciId();
        $Id = isset($Parametreler['id']) ? (int) $Parametreler['id'] : 0;
        
        if ($Id <= 0) {
            Response::error('Gecersiz kullanici.', 422);
            return;
        }

        $Girdi = json_decode(file_get_contents('php://input'), true) ?: [];
        $Repo = new UserRepository();

        $Mevcut = $Repo->bul($Id);
        if (!$Mevcut) {
            Response::error('Kullanici bulunamadi.', 404);
            return;
        }
        
        Transaction::wrap(function () use ($Repo, $Id, $Girdi, $GuncelleyenKullaniciId) {
            $Guncellenecek = [];
            
            if (isset($Girdi['AdSoyad']) && trim($Girdi['AdSoyad'])) {
                $Guncellenecek['AdSoyad'] = trim($Girdi['AdSoyad']);
            }
            if (isset($Girdi['Sifre']) && strlen($Girdi['Sifre']) >= 6) {
                $Guncellenecek['Parola'] = password_hash($Girdi['Sifre'], PASSWORD_BCRYPT);
            }

            if (!empty($Guncellenecek)) {
                $Repo->yedekle($Id, 'bck_tnm_user', $GuncelleyenKullaniciId);
                $Repo->guncelle($Id, $Guncellenecek, $GuncelleyenKullaniciId);
                ActionLogger::update('tnm_user', ['Id' => $Id], array_keys($Guncellenecek));
            }
            
            // Rol guncelleme ayri endpoint'te yapilacak
        });

        Response::json(['status' => 'success']);
    }
    
    /**
     * Kullaniciya rol atama
     * POST /api/users/{id}/roles
     * 
    * KRITIK KISIT: Atanan rolun permission seti, auth user'in permission setinin
    * alt kumesi olmak zorundadir.
     */
    public static function assignRoles(array $Parametreler): void
    {
        $AtayanKullaniciId = Context::kullaniciId();
        $HedefKullaniciId = isset($Parametreler['id']) ? (int) $Parametreler['id'] : 0;
        
        if ($HedefKullaniciId <= 0) {
            Response::error('Gecersiz kullanici ID.', 400);
            return;
        }
        
        $Girdi = json_decode(file_get_contents('php://input'), true) ?: [];
        $RolIdler = $Girdi['RolIdler'] ?? [];
        
        if (!is_array($RolIdler)) {
            Response::error('RolIdler array olmalidir.', 422);
            return;
        }
        
        $Repo = new UserRepository();
        $RoleRepo = new RoleRepository();
        $AuthService = AuthorizationService::getInstance();
        
        // Hedef kullanici mevcut mu?
        $HedefKullanici = $Repo->bul($HedefKullaniciId);
        if (!$HedefKullanici) {
            Response::error('Kullanici bulunamadi.', 404);
            return;
        }

        // Hedef kullanicinin mevcut rolleri, duzenleyen kullanicinin yetkilerinin alt kumesi olmali
        if (!$AuthService->kullaniciRolleriniDuzenleyebilirMi($AtayanKullaniciId, $HedefKullaniciId)) {
            Response::forbidden('Bu kullanicinin rollerini duzenleme yetkiniz yok.');
            return;
        }
        
        
        
        // SUBSET CONSTRAINT: Her rol icin kontrol
        $AtanamayanRoller = [];
        foreach ($RolIdler as $RolId) {
            if (!$AuthService->rolAtayabilirMi($AtayanKullaniciId, (int)$RolId)) {
                $Rol = $RoleRepo->bul((int)$RolId);
                $AtanamayanRoller[] = $Rol ? $Rol['RolAdi'] : "ID:{$RolId}";
            }
        }
        
        if (!empty($AtanamayanRoller)) {
            Response::error('Su rolleri atama yetkiniz yok: ' . implode(', ', $AtanamayanRoller) . '. Sadece sahip oldugunuz permission setinin alt kumesi olan rolleri atayabilirsiniz.', 403);
            return;
        }
        
        Transaction::wrap(function () use ($RoleRepo, $HedefKullaniciId, $RolIdler, $AtayanKullaniciId, $AuthService) {
            // Mevcut rolleri temizle
            $RoleRepo->kullaniciRolleriniTemizle($HedefKullaniciId, $AtayanKullaniciId);
            
            // Yeni rolleri ata
            foreach ($RolIdler as $RolId) {
                $RoleRepo->kullaniciyaRolAta($HedefKullaniciId, (int)$RolId, $AtayanKullaniciId);
            }
            
            // Cache invalidation
            $AuthService->kullaniciCacheTemizle($HedefKullaniciId);
            
            ActionLogger::logla('role_assign', 'tnm_user_rol', [
                'UserId' => $HedefKullaniciId,
                'RolIdler' => $RolIdler
            ], 'ok');
        });
        
        Response::json([
            'status' => 'ok',
            'message' => 'Roller basariyla atandi.'
        ]);
    }
    
    /**
     * Kullanicinin rollerini getir
     * GET /api/users/{id}/roles
     */
    public static function getRoles(array $Parametreler): void
    {
        $HedefKullaniciId = isset($Parametreler['id']) ? (int) $Parametreler['id'] : 0;
        
        if ($HedefKullaniciId <= 0) {
            Response::error('Gecersiz kullanici ID.', 400);
            return;
        }
        
        $RoleRepo = new RoleRepository();
        $Roller = $RoleRepo->kullaniciRolleriGetir($HedefKullaniciId);
        
        Response::json(['data' => $Roller]);
    }

    /**
     * Sifre degistirme (kullanici kendi sifresini degistirir)
     * POST /api/users/change-password
     */
    public static function changePassword(): void
    {
        $KullaniciId = Context::kullaniciId();
        if (!$KullaniciId) {
            Response::error('Oturum gecersiz.', 401);
            return;
        }

        $Girdi = json_decode(file_get_contents('php://input'), true) ?: [];
        $MevcutSifre = $Girdi['CurrentPassword'] ?? '';
        $YeniSifre = $Girdi['NewPassword'] ?? '';

        if (!$MevcutSifre || !$YeniSifre) {
            Response::error('Mevcut ve yeni sifre zorunludur.', 422);
            return;
        }
        if (strlen($YeniSifre) < 6) {
            Response::error('Yeni sifre en az 6 karakter olmalidir.', 422);
            return;
        }

        $Repo = new UserRepository();
        $Kullanici = $Repo->bul($KullaniciId);
        
        if (!$Kullanici) {
            Response::error('Kullanici bulunamadi.', 404);
            return;
        }

        if (!password_verify($MevcutSifre, $Kullanici['Parola'])) {
            Response::error('Mevcut sifre yanlis.', 422);
            return;
        }

        $YeniHash = password_hash($YeniSifre, PASSWORD_BCRYPT);

        Transaction::wrap(function () use ($Repo, $KullaniciId, $YeniHash) {
            $Repo->yedekle($KullaniciId, 'bck_tnm_user', $KullaniciId);
            $Repo->guncelle($KullaniciId, ['Parola' => $YeniHash], $KullaniciId);
            ActionLogger::logla('password_change', 'tnm_user', ['Id' => $KullaniciId], 'ok');
        });

        Response::json(['status' => 'ok', 'message' => 'Sifre degistirildi.']);
    }
}
