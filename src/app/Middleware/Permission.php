<?php

namespace App\Middleware;

use App\Core\Context;
use App\Core\Response;
use App\Services\Authorization\AuthorizationService;

/**
 * Permission Middleware
 * 
 * Route tabanli permission kontrolu yapar.
 * AuthorizationService ile entegre calisir.
 * 
 * Kullanim:
 *   Permission::izinGerekli('users.create')
 *   Permission::izinlerdenBiriGerekli(['users.create', 'users.update'])
 *   Permission::modulErisimGerekli('users')
 * 
 * @package App\Middleware
 */
class Permission
{
    /**
     * Belirtilen permission gerekli
     * 
     * @param string $PermissionKodu Ornek: "users.create"
     * @return bool
     */
    public static function izinGerekli(string $PermissionKodu): bool
    {
        $UserId = Context::kullaniciId();
        
        if (!$UserId) {
            Response::error('Yetkisiz erisim. Oturum bulunamadi.', 401);
            return false;
        }
        
        $AuthService = AuthorizationService::getInstance();
        
        if (!$AuthService->can($UserId, $PermissionKodu)) {
            self::logForbidden($UserId, [$PermissionKodu], 'izinGerekli');
            Response::forbidden();
            return false;
        }
        
        return true;
    }
    
    /**
     * Belirtilen permissionlardan en az biri gerekli
     * 
     * @param array $PermissionKodlari
     * @return bool
     */
    public static function izinlerdenBiriGerekli(array $PermissionKodlari): bool
    {
        $UserId = Context::kullaniciId();
        
        if (!$UserId) {
            Response::error('Yetkisiz erisim. Oturum bulunamadi.', 401);
            return false;
        }
        
        $AuthService = AuthorizationService::getInstance();
        
        if (!$AuthService->izinlerdenBiriVarMi($UserId, $PermissionKodlari)) {
            self::logForbidden($UserId, $PermissionKodlari, 'izinlerdenBiriGerekli');
            Response::forbidden();
            return false;
        }
        
        return true;
    }
    
    /**
     * Tum belirtilen permissionlar gerekli
     * 
     * @param array $PermissionKodlari
     * @return bool
     */
    public static function tumIzinlerGerekli(array $PermissionKodlari): bool
    {
        $UserId = Context::kullaniciId();
        
        if (!$UserId) {
            Response::error('Yetkisiz erisim. Oturum bulunamadi.', 401);
            return false;
        }
        
        $AuthService = AuthorizationService::getInstance();
        
        if (!$AuthService->tumIzinlerVarMi($UserId, $PermissionKodlari)) {
            self::logForbidden($UserId, $PermissionKodlari, 'tumIzinlerGerekli');
            Response::forbidden();
            return false;
        }
        
        return true;
    }
    
    /**
     * Belirtilen module erisim gerekli (herhangi bir aksiyon)
     * 
     * @param string $ModulAdi Ornek: "users", "invoices"
     * @return bool
     */
    public static function modulErisimGerekli(string $ModulAdi): bool
    {
        $UserId = Context::kullaniciId();
        
        if (!$UserId) {
            Response::error('Yetkisiz erisim. Oturum bulunamadi.', 401);
            return false;
        }
        
        $AuthService = AuthorizationService::getInstance();
        
        if (!$AuthService->modulErisimVarMi($UserId, $ModulAdi)) {
            self::logForbidden($UserId, [$ModulAdi . '.*'], 'modulErisimGerekli');
            Response::forbidden();
            return false;
        }
        
        return true;
    }
    
    
    /**
     * Kullanicinin kendi kaydini veya yeterli yetkiye sahip oldugunu kontrol eder
     * Ornek: Kullanici kendi profilini duzenleyebilir veya users.update yetkisi varsa baskasini
     * 
     * @param int $HedefUserId Islem yapilacak kullanici ID
     * @param string $PermissionKodu Baskasinin kaydini duzenleme yetkisi
     * @return bool
     */
    public static function kendiKaydiVeyaIzin(int $HedefUserId, string $PermissionKodu): bool
    {
        $UserId = Context::kullaniciId();
        
        if (!$UserId) {
            Response::error('Yetkisiz erisim. Oturum bulunamadi.', 401);
            return false;
        }
        
        // Kendi kaydi mi?
        if ($UserId === $HedefUserId) {
            return true;
        }
        
        $AuthService = AuthorizationService::getInstance();
        
        // Yetki kontrolu
        if (!$AuthService->can($UserId, $PermissionKodu)) {
            self::logForbidden($UserId, [$PermissionKodu], 'kendiKaydiVeyaIzin');
            Response::forbidden();
            return false;
        }
        
        return true;
    }

    private static function logForbidden(?int $UserId, array $PermissionKodlari, string $Kaynak): void
    {
        $UserInfo = $UserId ? (string) $UserId : 'null';
        $PermissionStr = implode(', ', $PermissionKodlari);
        $Cevriler = array_map([self::class, 'izinCevir'], $PermissionKodlari);
        $CeviriStr = implode(', ', $Cevriler);

        error_log("[RBAC] Yetkisiz islem: userId={$UserInfo} kaynak={$Kaynak} permissions={$PermissionStr} ceviri={$CeviriStr}");
    }

    private static function izinCevir(string $PermissionKodu): string
    {
        $Harita = config('permissions_tr.permissionlar', []);
        if (isset($Harita[$PermissionKodu])) {
            return $Harita[$PermissionKodu];
        }

        $Parcalar = explode('.', $PermissionKodu, 2);
        if (count($Parcalar) !== 2) {
            return $PermissionKodu;
        }

        $Moduller = config('permissions_tr.moduller', []);
        $Aksiyonlar = config('permissions_tr.aksiyonlar', []);
        $Modul = $Moduller[$Parcalar[0]] ?? $Parcalar[0];
        $Aksiyon = $Aksiyonlar[$Parcalar[1]] ?? $Parcalar[1];
        return $Modul . ': ' . $Aksiyon;
    }
}
