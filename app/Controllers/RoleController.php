<?php

namespace App\Controllers;

use App\Core\Context;
use App\Core\Response;
use App\Middleware\Permission;
use App\Repositories\RoleRepository;
use App\Repositories\PermissionRepository;
use App\Services\Authorization\AuthorizationService;

/**
 * Rol Controller
 * 
 * Rol CRUD ve permission atama islemlerini yonetir.
 * Tum islemler yetki kontrolu altindadir.
 * 
 * @package App\Controllers
 */
class RoleController
{
    // =========================================================================
    // ROL CRUD
    // =========================================================================
    
    /**
     * Tum rolleri listeler
     * GET /api/roles
     */
    public static function index(): void
    {
        if (!Permission::izinGerekli('roles.read')) {
            return;
        }
        
        try {
            $Repo = new RoleRepository();
            $Roller = $Repo->tumRoller();
            
            Response::json(['data' => $Roller]);
        } catch (\Throwable $E) {
            Response::error('Roller listelenirken hata: ' . $E->getMessage(), 500);
        }
    }
    
    /**
     * Rol detayi getirir
     * GET /api/roles/{id}
     */
    public static function show(array $Parametreler): void
    {
        if (!Permission::izinGerekli('roles.read')) {
            return;
        }
        
        $Id = isset($Parametreler['id']) ? (int) $Parametreler['id'] : 0;
        
        if ($Id <= 0) {
            Response::error('Gecersiz rol ID.', 400);
            return;
        }
        
        $Repo = new RoleRepository();
        $Rol = $Repo->rolDetay($Id);
        
        if (!$Rol) {
            Response::error('Rol bulunamadi.', 404);
            return;
        }
        
        Response::json(['data' => $Rol]);
    }
    
    /**
     * Yeni rol olusturur
     * POST /api/roles
     */
    public static function store(): void
    {
        if (!Permission::izinGerekli('roles.create')) {
            return;
        }
        
        $Girdi = json_decode(file_get_contents('php://input'), true);
        
        if (!$Girdi) {
            Response::error('Gecersiz istek verisi.', 400);
            return;
        }
        
        // Validasyon
        $Hatalar = [];
        
        if (empty($Girdi['RolKodu'])) {
            $Hatalar[] = 'Rol kodu zorunludur.';
        } elseif (!preg_match('/^[a-z][a-z0-9_]{2,29}$/', $Girdi['RolKodu'])) {
            $Hatalar[] = 'Rol kodu 3-30 karakter, kucuk harf ile baslamali, sadece kucuk harf, rakam ve alt cizgi icermelidir.';
        }
        
        if (empty($Girdi['RolAdi'])) {
            $Hatalar[] = 'Rol adi zorunludur.';
        } elseif (mb_strlen($Girdi['RolAdi']) > 50) {
            $Hatalar[] = 'Rol adi en fazla 50 karakter olabilir.';
        }
        
        if (isset($Girdi['Seviye']) && (!is_numeric($Girdi['Seviye']) || $Girdi['Seviye'] < 0 || $Girdi['Seviye'] > 99)) {
            $Hatalar[] = 'Seviye 0-99 arasinda olmalidir.';
        }
        
        if (!empty($Hatalar)) {
            Response::error($Hatalar, 422);
            return;
        }
        
        try {
            $KullaniciId = Context::kullaniciId();
            
            $Repo = new RoleRepository();
            $YeniId = $Repo->rolEkle([
                'RolKodu'  => $Girdi['RolKodu'],
                'RolAdi'   => $Girdi['RolAdi'],
                'Aciklama' => $Girdi['Aciklama'] ?? null,
                'Seviye'   => (int) ($Girdi['Seviye'] ?? 0)
            ], $KullaniciId);
            
            Response::json([
                'success' => true,
                'message' => 'Rol basariyla olusturuldu.',
                'data'    => ['Id' => $YeniId]
            ], 201);
            
        } catch (\InvalidArgumentException $E) {
            Response::error($E->getMessage(), 422);
        } catch (\Exception $E) {
            Response::error('Rol olusturulurken hata olustu.', 500);
        }
    }
    
    /**
     * Rol gunceller
     * PUT /api/roles/{id}
     */
    public static function update(array $Parametreler): void
    {
        if (!Permission::izinGerekli('roles.update')) {
            return;
        }
        
        $Id = isset($Parametreler['id']) ? (int) $Parametreler['id'] : 0;
        
        if ($Id <= 0) {
            Response::error('Gecersiz rol ID.', 400);
            return;
        }
        
        $Girdi = json_decode(file_get_contents('php://input'), true);
        
        if (!$Girdi) {
            Response::error('Gecersiz istek verisi.', 400);
            return;
        }
        
        // Validasyon
        $Hatalar = [];
        
        if (isset($Girdi['RolKodu']) && !preg_match('/^[a-z][a-z0-9_]{2,29}$/', $Girdi['RolKodu'])) {
            $Hatalar[] = 'Rol kodu 3-30 karakter, kucuk harf ile baslamali, sadece kucuk harf, rakam ve alt cizgi icermelidir.';
        }
        
        if (isset($Girdi['RolAdi']) && mb_strlen($Girdi['RolAdi']) > 50) {
            $Hatalar[] = 'Rol adi en fazla 50 karakter olabilir.';
        }
        
        if (isset($Girdi['Seviye']) && (!is_numeric($Girdi['Seviye']) || $Girdi['Seviye'] < 0 || $Girdi['Seviye'] > 99)) {
            $Hatalar[] = 'Seviye 0-99 arasinda olmalidir.';
        }
        
        if (!empty($Hatalar)) {
            Response::error($Hatalar, 422);
            return;
        }
        
        try {
            $KullaniciId = Context::kullaniciId();
            
            $Repo = new RoleRepository();
            $Repo->rolGuncelle($Id, $Girdi, $KullaniciId);
            
            Response::json([
                'success' => true,
                'message' => 'Rol basariyla guncellendi.'
            ]);
            
        } catch (\InvalidArgumentException $E) {
            Response::error($E->getMessage(), 422);
        } catch (\Exception $E) {
            Response::error('Rol guncellenirken hata olustu.', 500);
        }
    }
    
    /**
     * Rol siler
     * DELETE /api/roles/{id}
     */
    public static function delete(array $Parametreler): void
    {
        if (!Permission::izinGerekli('roles.delete')) {
            return;
        }
        
        $Id = isset($Parametreler['id']) ? (int) $Parametreler['id'] : 0;
        
        if ($Id <= 0) {
            Response::error('Gecersiz rol ID.', 400);
            return;
        }
        
        try {
            $KullaniciId = Context::kullaniciId();
            
            $Repo = new RoleRepository();
            $Repo->rolSil($Id, $KullaniciId);
            
            Response::json([
                'success' => true,
                'message' => 'Rol basariyla silindi.'
            ]);
            
        } catch (\InvalidArgumentException $E) {
            Response::error($E->getMessage(), 422);
        } catch (\Exception $E) {
            Response::error('Rol silinirken hata olustu.', 500);
        }
    }
    
    // =========================================================================
    // PERMISSION ATAMA
    // =========================================================================
    
    /**
     * Role permission listesi atar (toplu atama)
     * POST /api/roles/{id}/permissions
     * Body: { "permissions": [1, 2, 3] }
     */
    public static function assignPermissions(array $Parametreler): void
    {
        if (!Permission::izinGerekli('roles.update')) {
            return;
        }
        
        $RolId = isset($Parametreler['id']) ? (int) $Parametreler['id'] : 0;
        
        if ($RolId <= 0) {
            Response::error('Gecersiz rol ID.', 400);
            return;
        }
        
        $Girdi = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($Girdi['permissions']) || !is_array($Girdi['permissions'])) {
            Response::error('permissions alani array olmalidir.', 422);
            return;
        }
        
        try {
            $KullaniciId = Context::kullaniciId();
            $AuthService = AuthorizationService::getInstance();
            
            // Subset constraint kontrolu: sadece kendi sahip oldugu permissionlari atayabilir
            $KullaniciPermissionlari = $AuthService->kullaniciPermissionlariGetir($KullaniciId);
            $Repo = new RoleRepository();
            $TumPermissionlar = $AuthService->tumPermissionlariGetir();
            $PermissionIdToKod = [];
            foreach ($TumPermissionlar as $P) {
                $PermissionIdToKod[$P['Id']] = $P['PermissionKodu'];
            }
            
            $IzinsizPermissionlar = [];
            foreach ($Girdi['permissions'] as $PermId) {
                $Kod = $PermissionIdToKod[$PermId] ?? null;
                if ($Kod && !in_array($Kod, $KullaniciPermissionlari, true)) {
                    $IzinsizPermissionlar[] = $Kod;
                }
            }
            
            if (!empty($IzinsizPermissionlar)) {
                Response::error('Sadece kendi sahip oldugunuz yetkileri atayabilirsiniz. Izinsiz yetkiler: ' . implode(', ', $IzinsizPermissionlar), 403);
                return;
            }
            
            $Repo = new RoleRepository();
            $Repo->rolePermissionAta($RolId, $Girdi['permissions'], $KullaniciId);
            
            Response::json([
                'success' => true,
                'message' => 'Rol yetkileri basariyla guncellendi.'
            ]);
            
        } catch (\InvalidArgumentException $E) {
            Response::error($E->getMessage(), 422);
        } catch (\Exception $E) {
            Response::error('Yetkiler atanirken hata olustu.', 500);
        }
    }
    
    /**
     * Rolun permissionlarini getirir
     * GET /api/roles/{id}/permissions
     */
    public static function getPermissions(array $Parametreler): void
    {
        if (!Permission::izinGerekli('roles.read')) {
            return;
        }
        
        $RolId = isset($Parametreler['id']) ? (int) $Parametreler['id'] : 0;
        
        if ($RolId <= 0) {
            Response::error('Gecersiz rol ID.', 400);
            return;
        }
        
        $Repo = new RoleRepository();
        $Permissionlar = $Repo->rolPermissionlariGetir($RolId);
        
        Response::json(['data' => $Permissionlar]);
    }
    
    // =========================================================================
    // YARDIMCI ENDPOINTLER
    // =========================================================================
    
    /**
     * Tum permission listesini getirir (rol duzenleme icin)
     * GET /api/permissions
     */
    public static function allPermissions(): void
    {
        if (!Permission::izinGerekli('roles.read')) {
            return;
        }
        
        $AuthService = AuthorizationService::getInstance();
        $KullaniciId = Context::kullaniciId();
        
        // Kritik permission'larin varligini garanti altina al
        $PermissionRepo = new PermissionRepository();
        $KritikPermissionlar = [
            'users.read_all' => [
                'ModulAdi' => 'users',
                'Aksiyon' => 'read_all',
                'Aciklama' => 'Tum kullanicilari gorebilme yetkisi (sadece kendi olusturdugu degil)'
            ],
            'customers.read_all' => [
                'ModulAdi' => 'customers',
                'Aksiyon' => 'read_all',
                'Aciklama' => 'Tum musterileri gorebilme yetkisi (sadece kendi olusturdugu degil)'
            ]
        ];
        
        foreach ($KritikPermissionlar as $Kod => $Veri) {
            $Mevcut = $PermissionRepo->koduIleBul($Kod);
            if (!$Mevcut) {
                $PermissionRepo->permissionEkle([
                    'PermissionKodu' => $Kod,
                    'ModulAdi' => $Veri['ModulAdi'],
                    'Aksiyon' => $Veri['Aksiyon'],
                    'Aciklama' => $Veri['Aciklama']
                ], $KullaniciId);
            }
        }
        
        // Turkce ceviri dosyasini yukle
        $Ceviriler = require ROOT_PATH . '/config/permissions_tr.php';
        $ModulCevirileri = $Ceviriler['moduller'] ?? [];
        $PermissionCevirileri = $Ceviriler['permissionlar'] ?? [];
        $AksiyonCevirileri = $Ceviriler['aksiyonlar'] ?? [];
        
        // Modul bazinda gruplu
        $ModulBazinda = $AuthService->permissionlariModulBazindaGetir();
        
        // Turkce ceviri ekle
        $ModulBazindaTr = [];
        foreach ($ModulBazinda as $Modul => $Permler) {
            $ModulAdiTr = $ModulCevirileri[$Modul] ?? ucfirst($Modul);
            $PermlerTr = [];
            foreach ($Permler as $Perm) {
                $PermKodu = $Perm['PermissionKodu'] ?? '';
                $Aksiyon = $Perm['Aksiyon'] ?? '';
                $PermlerTr[] = array_merge($Perm, [
                    'AksiyonTr' => $AksiyonCevirileri[$Aksiyon] ?? ucfirst($Aksiyon),
                    'PermissionAdiTr' => $PermissionCevirileri[$PermKodu] ?? $PermKodu
                ]);
            }
            $ModulBazindaTr[$Modul] = [
                'modulAdiTr' => $ModulAdiTr,
                'permissionlar' => $PermlerTr
            ];
        }
        
        // Duz liste
        $TumListe = $AuthService->tumPermissionlariGetir();
        $TumListeTr = array_map(function ($P) use ($PermissionCevirileri) {
            $P['PermissionAdiTr'] = $PermissionCevirileri[$P['PermissionKodu']] ?? $P['PermissionKodu'];
            return $P;
        }, $TumListe);
        
        Response::json([
            'data' => [
                'modulBazinda' => $ModulBazindaTr,
                'tumPermissionlar' => $TumListeTr,
                'modulCevirileri' => $ModulCevirileri,
                'aksiyonCevirileri' => $AksiyonCevirileri
            ]
        ]);
    }
    
    /**
     * Kullanicinin atayabilecegi rolleri getirir
     * GET /api/roles/assignable
     */
    public static function assignableRoles(): void
    {
        $KullaniciId = Context::kullaniciId();
        
        if (!$KullaniciId) {
            Response::error('Oturum bulunamadi.', 401);
            return;
        }
        
        $AuthService = AuthorizationService::getInstance();
        $AtanabilirRoller = $AuthService->atanabilirRolleriGetir($KullaniciId);
        
        Response::json(['data' => $AtanabilirRoller]);
    }
    
    /**
     * Mevcut kullanicinin yetkilerini getirir (frontend icin)
     * GET /api/auth/permissions
     */
    public static function myPermissions(): void
    {
        $UserId = Context::kullaniciId();
        
        if (!$UserId) {
            Response::error('Oturum bulunamadi.', 401);
            return;
        }
        
        $AuthService = AuthorizationService::getInstance();
        $Yetkiler = $AuthService->frontendIcinYetkiler($UserId);
        
        Response::json(['data' => $Yetkiler]);
    }
}
