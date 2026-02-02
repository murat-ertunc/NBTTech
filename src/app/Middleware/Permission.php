<?php

namespace App\Middleware;

use App\Core\Context;
use App\Core\Response;
use App\Services\Authorization\AuthorizationService;














class Permission
{
    





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
    
    
    







    public static function kendiKaydiVeyaIzin(int $HedefUserId, string $PermissionKodu): bool
    {
        $UserId = Context::kullaniciId();
        
        if (!$UserId) {
            Response::error('Yetkisiz erisim. Oturum bulunamadi.', 401);
            return false;
        }
        
        
        if ($UserId === $HedefUserId) {
            return true;
        }
        
        $AuthService = AuthorizationService::getInstance();
        
        
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
