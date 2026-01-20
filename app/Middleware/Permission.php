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
            Response::error("Bu islem icin yetkiniz yok: {$PermissionKodu}", 403);
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
            $PermissionStr = implode(', ', $PermissionKodlari);
            Response::error("Bu islem icin su yetkilerden birine sahip olmalisiniz: {$PermissionStr}", 403);
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
            $PermissionStr = implode(', ', $PermissionKodlari);
            Response::error("Bu islem icin tum su yetkilere sahip olmalisiniz: {$PermissionStr}", 403);
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
            Response::error("Bu modul icin erisim yetkiniz yok: {$ModulAdi}", 403);
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
            Response::error("Baska kullanicilarin kayitlari icin yetkiniz yok.", 403);
            return false;
        }
        
        return true;
    }
}
