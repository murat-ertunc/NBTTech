<?php

namespace App\Middleware;

use App\Core\Token;
use App\Services\Authorization\AuthorizationService;

/**
 * Page Middleware
 * 
 * Server-rendered sayfalar icin authentication ve permission kontrolu yapar.
 * Login sayfasina yonlendirme veya 403 sayfasi gosterir.
 * 
 * Kullanim (routes/web.php):
 *   Page::auth() - Sadece auth kontrolu
 *   Page::can('logs.read') - Auth + tek permission
 *   Page::canAny(['logs.read', 'logs.create']) - Auth + herhangi biri
 * 
 * @package App\Middleware
 */
class Page
{
    /**
     * Oturum kontrol et - giriş yapmamışsa login'e yönlendir
     * 
     * @return bool
     */
    public static function auth(): bool
    {
        $TokenStr = $_COOKIE['nbt_token'] ?? null;
        
        if (empty($TokenStr)) {
            self::redirectToLogin();
            return false;
        }
        
        try {
            $Payload = Token::verify($TokenStr);
            if (!is_array($Payload) || !isset($Payload['userId'])) {
                self::redirectToLogin();
                return false;
            }
            
            // UserId'yi global olarak erişilebilir yap
            $GLOBALS['AuthUserId'] = (int)$Payload['userId'];
            return true;
            
        } catch (\Throwable $E) {
            self::redirectToLogin();
            return false;
        }
    }
    
    /**
     * Oturum + tek permission kontrol et
     * 
     * @param string $PermissionKodu
     * @return bool
     */
    public static function can(string $PermissionKodu): bool
    {
        if (!self::auth()) {
            return false;
        }
        
        $UserId = $GLOBALS['AuthUserId'] ?? null;
        if (!$UserId) {
            self::showForbidden($PermissionKodu);
            return false;
        }
        
        $AuthService = AuthorizationService::getInstance();
        if (!$AuthService->can($UserId, $PermissionKodu)) {
            self::logForbidden($UserId, $PermissionKodu);
            self::showForbidden($PermissionKodu);
            return false;
        }
        
        return true;
    }
    
    /**
     * Oturum + birden fazla permission'dan herhangi biri
     * 
     * @param array $PermissionKodlari
     * @return bool
     */
    public static function canAny(array $PermissionKodlari): bool
    {
        if (!self::auth()) {
            return false;
        }
        
        $UserId = $GLOBALS['AuthUserId'] ?? null;
        if (!$UserId) {
            self::showForbidden(implode(' | ', $PermissionKodlari));
            return false;
        }
        
        $AuthService = AuthorizationService::getInstance();
        if (!$AuthService->izinlerdenBiriVarMi($UserId, $PermissionKodlari)) {
            self::logForbidden($UserId, implode(',', $PermissionKodlari));
            self::showForbidden(implode(' | ', $PermissionKodlari));
            return false;
        }
        
        return true;
    }
    
    /**
     * Oturum + tüm permission'lar gerekli
     * 
     * @param array $PermissionKodlari
     * @return bool
     */
    public static function canAll(array $PermissionKodlari): bool
    {
        if (!self::auth()) {
            return false;
        }
        
        $UserId = $GLOBALS['AuthUserId'] ?? null;
        if (!$UserId) {
            self::showForbidden(implode(' & ', $PermissionKodlari));
            return false;
        }
        
        $AuthService = AuthorizationService::getInstance();
        if (!$AuthService->tumIzinlerVarMi($UserId, $PermissionKodlari)) {
            self::logForbidden($UserId, implode(',', $PermissionKodlari));
            self::showForbidden(implode(' & ', $PermissionKodlari));
            return false;
        }
        
        return true;
    }
    
    /**
     * Modüle erişim var mı kontrol et
     * 
     * @param string $ModulAdi
     * @return bool
     */
    public static function canModule(string $ModulAdi): bool
    {
        if (!self::auth()) {
            return false;
        }
        
        $UserId = $GLOBALS['AuthUserId'] ?? null;
        if (!$UserId) {
            self::showForbidden($ModulAdi . '.*');
            return false;
        }
        
        $AuthService = AuthorizationService::getInstance();
        if (!$AuthService->modulErisimVarMi($UserId, $ModulAdi)) {
            self::logForbidden($UserId, $ModulAdi . '.*');
            self::showForbidden($ModulAdi . '.*');
            return false;
        }
        
        return true;
    }
    
    /**
     * Login sayfasına yönlendir
     */
    private static function redirectToLogin(): void
    {
        $ReturnUrl = $_SERVER['REQUEST_URI'] ?? '/';
        header('Location: /login?redirect=' . urlencode($ReturnUrl));
        exit;
    }
    
    /**
     * 403 Forbidden sayfası göster
     * 
     * @param string $GerekliYetki
     */
    private static function showForbidden(string $GerekliYetki): void
    {
        http_response_code(403);
        
        $PageTitle = 'Erişim Reddedildi';
        $AppName = 'NbtProject';
        
        try {
            $AppName = config('app.name', 'NbtProject');
        } catch (\Throwable $E) {
            // Config yüklenemezse varsayılan kullan
        }
        
        // Basit 403 sayfası
        ?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($PageTitle . ' - ' . $AppName) ?></title>
    <link rel="stylesheet" href="/assets/bootstrap.min.css">
    <link rel="stylesheet" href="/assets/vendor/bootstrap-icons/bootstrap-icons.min.css">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-6 col-lg-5">
                <div class="card shadow-lg border-0">
                    <div class="card-body text-center p-5">
                        <div class="mb-4">
                            <i class="bi bi-shield-lock text-danger" style="font-size: 5rem;"></i>
                        </div>
                        <h1 class="h3 mb-3 text-danger">Erişim Reddedildi</h1>
                        <p class="text-muted mb-4">
                            Bu sayfaya erişim yetkiniz bulunmamaktadır.
                        </p>
                        <div class="alert alert-light border text-start">
                            <small class="text-muted d-block mb-1">Gerekli yetki:</small>
                            <code class="text-danger"><?= htmlspecialchars($GerekliYetki) ?></code>
                        </div>
                        <div class="d-grid gap-2 mt-4">
                            <a href="/dashboard" class="btn btn-primary">
                                <i class="bi bi-house me-2"></i>Ana Sayfaya Dön
                            </a>
                            <a href="javascript:history.back()" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-2"></i>Geri Git
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
        <?php
        exit;
    }
    
    /**
     * Yetkisiz erişim logla
     * 
     * @param int $UserId
     * @param string $GerekliYetki
     */
    private static function logForbidden(int $UserId, string $GerekliYetki): void
    {
        $Uri = $_SERVER['REQUEST_URI'] ?? '/';
        error_log("[PAGE-RBAC] Yetkisiz sayfa erisimi: userId={$UserId} uri={$Uri} gerekliYetki={$GerekliYetki}");
    }
}
