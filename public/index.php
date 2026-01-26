<?php
/**
 * Front Controller - Ana Giriş Noktası
 * 
 * Tüm web istekleri bu dosya üzerinden yönlendirilir.
 * Hosting PHP handler kısıtı nedeniyle pages/*.php dosyaları da buradan serve edilir.
 */

// Bootstrap yükle (merkezi init)
require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'bootstrap' . DIRECTORY_SEPARATOR . 'app.php';

// =============================================================================
// PAGE ROUTER: /pages/*.php veya /{page} isteklerini handle et
// Hosting alt dizinlerde PHP çalıştırmadığı için bu router gerekli
// =============================================================================

$RequestUri = $_SERVER['REQUEST_URI'] ?? '/';
$RequestPath = parse_url($RequestUri, PHP_URL_PATH) ?: '/';

// /public/ prefix'ini temizle (root proxy'den geliyorsa)
if (strpos($RequestPath, '/public/') === 0) {
    $RequestPath = substr($RequestPath, 7); // "/public" = 7 karakter
}

// /index.php prefix'ini temizle
if (strpos($RequestPath, '/index.php') === 0) {
    $RequestPath = substr($RequestPath, 10) ?: '/';
}

// Baştaki slash'ı kaldır
$CleanPath = ltrim($RequestPath, '/');

// Page route tespiti: /pages/{name}.php veya /pages/{name} veya /{name}
$PageRoute = null;

// Pattern 1: /pages/xxx.php veya /pages/xxx
if (preg_match('#^pages/([a-zA-Z0-9_-]+)(\.php)?$#', $CleanPath, $M)) {
    $PageRoute = $M[1];
}
// Pattern 2: /xxx (tek segment, pages/ olmadan)
elseif (preg_match('#^([a-zA-Z0-9_-]+)$#', $CleanPath, $M)) {
    $PageRoute = $M[1];
}

// Güvenlik: ".." içeren veya boş route'ları reddet
if ($PageRoute !== null && $PageRoute !== '' && strpos($PageRoute, '..') === false) {
    $PageFile = __DIR__ . '/pages/' . $PageRoute . '.php';
    
    // Dosya varsa include et ve çık
    if (is_file($PageFile) && is_readable($PageFile)) {
        require $PageFile;
        exit;
    }
}

// =============================================================================
// API/ROUTER: Page değilse normal router akışına devam et
// =============================================================================

use App\Core\Router;

$Router = new Router();

// Route tanımlarını yükle
require ROUTES_PATH . 'api.php';
require ROUTES_PATH . 'web.php';

$Metod = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// PATH_INFO/REDIRECT_URL destekli yol tespiti (bazı sunucularda REQUEST_URI sadece /index.php olur)
$PathFromUri = parse_url($RequestUri, PHP_URL_PATH) ?: '/';
$PathInfo = $_SERVER['PATH_INFO'] ?? ($_SERVER['ORIG_PATH_INFO'] ?? null);
$RedirectUrl = $_SERVER['REDIRECT_URL'] ?? null;

$Yol = $PathInfo ?: ($RedirectUrl ?: $PathFromUri);

// Güvenli normalize
if ($Yol === '' || $Yol === false) {
	$Yol = '/';
}
if ($Yol[0] !== '/') {
	$Yol = '/' . $Yol;
}

// FTP/Paylasimli hosting uyumlulugu + alt dizin kurulumlari:
// 1) Base path'i (script dizini) temizle
$ScriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$BaseDir = rtrim(str_replace('\\', '/', dirname($ScriptName)), '/');
if ($BaseDir !== '' && $BaseDir !== '.' && strpos($Yol, $BaseDir) === 0) {
	$Yol = substr($Yol, strlen($BaseDir));
}

// 2) /index.php veya /index.php/route prefix'ini temizle

if ($ScriptName !== '' && strpos($Yol, $ScriptName) === 0) {
	$Yol = substr($Yol, strlen($ScriptName));
}
if (strpos($Yol, '/index.php') === 0) {
	$Yol = substr($Yol, strlen('/index.php'));
}

// 3) Bos kalirsa root kabul et
if ($Yol === '' || $Yol === false) {
	$Yol = '/';
}

// 4) Sondaki slash'i temizle (root haric)
if ($Yol !== '/' && substr($Yol, -1) === '/') {
	$Yol = rtrim($Yol, '/');
}

$Router->dispatch($Metod, $Yol);
