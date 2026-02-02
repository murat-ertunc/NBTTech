<?php

namespace App\Middleware;

use App\Core\Token;
use App\Services\Authorization\AuthorizationService;














class Page
{
    




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
            
            
            $GLOBALS['AuthUserId'] = (int)$Payload['userId'];
            return true;
            
        } catch (\Throwable $E) {
            self::redirectToLogin();
            return false;
        }
    }
    
    





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
    
    


    private static function redirectToLogin(): void
    {
        $ReturnUrl = $_SERVER['REQUEST_URI'] ?? '/';
        $RequestUri = $_SERVER['REQUEST_URI'] ?? '/';
        $ScriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $BaseDir = rtrim(str_replace('\\', '/', dirname($ScriptName)), '/');
        if ($BaseDir === '.' || $BaseDir === '/') {
            $BaseDir = '';
        }
        $LoginPath = $BaseDir . '/login';
        if (strpos($RequestUri, '/index.php') !== false || strpos($ScriptName, 'index.php') !== false) {
            $LoginPath = $BaseDir . '/login.php';
        }
        header('Location: ' . $LoginPath . '?redirect=' . urlencode($ReturnUrl));
        exit;
    }
    
    




    private static function showForbidden(string $GerekliYetki): void
    {
        http_response_code(403);
        
        $PageTitle = 'Erişim Reddedildi';
        $AppName = 'NbtProject';
        
        try {
            $AppName = config('app.name', 'NbtProject');
        } catch (\Throwable $E) {
            
        }
        
        
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

    





    public static function forbid(string $GerekliYetki): void
    {
        self::showForbidden($GerekliYetki);
    }
    
    





    private static function logForbidden(int $UserId, string $GerekliYetki): void
    {
        $Uri = $_SERVER['REQUEST_URI'] ?? '/';
        error_log("[PAGE-RBAC] Yetkisiz sayfa erisimi: userId={$UserId} uri={$Uri} gerekliYetki={$GerekliYetki}");
    }
    
    






    public static function requireValidId(int $Id, string $EntityName = 'Kayıt'): bool
    {
        if ($Id <= 0) {
            self::showNotFound($EntityName, 'Geçersiz ID');
            return false;
        }
        return true;
    }
    
    





    public static function requireCustomer(int $MusteriId): bool
    {
        if ($MusteriId <= 0) {
            self::showNotFound('Müşteri', 'Geçersiz müşteri ID');
            return false;
        }
        
        $Repo = new \App\Repositories\CustomerRepository();
        $Kayit = $Repo->bul($MusteriId);
        
        if (!$Kayit) {
            self::showNotFound('Müşteri', 'Müşteri bulunamadı veya silinmiş');
            return false;
        }
        
        return true;
    }
    
    







    public static function requireRecord(string $RepoClass, int $Id, string $EntityName = 'Kayıt'): bool
    {
        if ($Id <= 0) {
            self::showNotFound($EntityName, 'Geçersiz ID');
            return false;
        }
        
        
        if (strpos($RepoClass, '\\') === false) {
            $RepoClass = 'App\\Repositories\\' . $RepoClass;
        }
        
        if (!class_exists($RepoClass)) {
            error_log("[PAGE] Repository bulunamadi: {$RepoClass}");
            self::showNotFound($EntityName, 'Sistem hatası');
            return false;
        }
        
        $Repo = new $RepoClass();
        $Kayit = $Repo->bul($Id);
        
        if (!$Kayit) {
            self::showNotFound($EntityName, $EntityName . ' bulunamadı veya silinmiş');
            return false;
        }
        
        return true;
    }
    
    








    private static function showNotFound(string $EntityName, string $Mesaj): void
    {
        
        $FullMessage = $EntityName . ': ' . $Mesaj;
        
        
        $Referrer = $_SERVER['HTTP_REFERER'] ?? null;
        
        
        $CurrentUri = $_SERVER['REQUEST_URI'] ?? '/';
        if (empty($Referrer) || strpos($Referrer, $CurrentUri) !== false) {
            $RedirectUrl = '/dashboard';
        } else {
            
            $ParsedUrl = parse_url($Referrer);
            $BasePath = $ParsedUrl['path'] ?? '/dashboard';
            
            
            parse_str($ParsedUrl['query'] ?? '', $QueryParams);
            $RedirectUrl = $BasePath . (!empty($QueryParams) ? '?' . http_build_query($QueryParams) : '');
        }
        
        
        $Separator = (strpos($RedirectUrl, '?') !== false) ? '&' : '?';
        $RedirectUrl .= $Separator . '_error=' . urlencode($FullMessage);
        
        header('Location: ' . $RedirectUrl);
        exit;
    }
}
