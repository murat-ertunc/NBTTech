<?php
/**
 * Front Controller - Ana Giriş Noktası
 * 
 * Tüm web istekleri bu dosya üzerinden yönlendirilir.
 * Hosting PHP handler kısıtı nedeniyle pages/*.php dosyaları da buradan serve edilir.
 */

// =============================================================================
// PAGE ROUTER - BOOTSTRAP'TAN ÖNCE!
// Hosting alt dizinlerde PHP çalıştırmadığı için bu router gerekli
// PHP 7.4 uyumlu (str_starts_with yok)
// =============================================================================

$RequestUri = $_SERVER['REQUEST_URI'] ?? '/';
$RequestPath = parse_url($RequestUri, PHP_URL_PATH);
if ($RequestPath === false || $RequestPath === null) {
    $RequestPath = '/';
}

// Path normalizasyonu
$CleanPath = $RequestPath;

// /public prefix'ini temizle
if (substr($CleanPath, 0, 8) === '/public/') {
    $CleanPath = substr($CleanPath, 7);
}
if (substr($CleanPath, 0, 7) === 'public/') {
    $CleanPath = '/' . substr($CleanPath, 7);
}

// /index.php prefix'ini temizle
if (substr($CleanPath, 0, 11) === '/index.php/') {
    $CleanPath = substr($CleanPath, 10);
} elseif ($CleanPath === '/index.php') {
    $CleanPath = '/';
}

// Baştaki slash'ı kaldır
$CleanPath = ltrim($CleanPath, '/');

// Page route tespiti
$PageRoute = null;

// Pattern 1: pages/xxx.php
if (preg_match('#^pages/([a-zA-Z0-9_-]+)\.php$#', $CleanPath, $M)) {
    $PageRoute = $M[1];
}
// Pattern 2: pages/xxx (php olmadan)
elseif (preg_match('#^pages/([a-zA-Z0-9_-]+)$#', $CleanPath, $M)) {
    $PageRoute = $M[1];
}
// Pattern 3: xxx (tek segment - dashboard, users, vb.)
elseif (preg_match('#^([a-zA-Z0-9_-]+)$#', $CleanPath, $M)) {
    $PageRoute = $M[1];
}

// Page dosyası varsa require et ve çık
if ($PageRoute !== null && $PageRoute !== '' && strpos($PageRoute, '..') === false) {
    $PageFile = __DIR__ . DIRECTORY_SEPARATOR . 'pages' . DIRECTORY_SEPARATOR . $PageRoute . '.php';
    
    if (is_file($PageFile) && is_readable($PageFile)) {
        require $PageFile;
        exit;
    }
}

// =============================================================================
// BOOTSTRAP: Page değilse normal uygulama akışı
// =============================================================================

// Bootstrap yükle (merkezi init)
require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'bootstrap' . DIRECTORY_SEPARATOR . 'app.php';

use App\Core\Router;

$Router = new Router();

// Route tanımlarını yükle
require ROUTES_PATH . 'api.php';
require ROUTES_PATH . 'web.php';

$Metod = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// PATH_INFO/REDIRECT_URL destekli yol tespiti
$PathFromUri = parse_url($RequestUri, PHP_URL_PATH);
if ($PathFromUri === false || $PathFromUri === null) {
    $PathFromUri = '/';
}
$PathInfo = $_SERVER['PATH_INFO'] ?? ($_SERVER['ORIG_PATH_INFO'] ?? null);
$RedirectUrl = $_SERVER['REDIRECT_URL'] ?? null;

$Yol = $PathInfo ? $PathInfo : ($RedirectUrl ? $RedirectUrl : $PathFromUri);

// Güvenli normalize
if ($Yol === '' || $Yol === false) {
	$Yol = '/';
}
if ($Yol[0] !== '/') {
	$Yol = '/' . $Yol;
}

// Base path'i temizle
$ScriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$BaseDir = rtrim(str_replace('\\', '/', dirname($ScriptName)), '/');
if ($BaseDir !== '' && $BaseDir !== '.' && strpos($Yol, $BaseDir) === 0) {
	$Yol = substr($Yol, strlen($BaseDir));
}

// /index.php prefix'ini temizle
if ($ScriptName !== '' && strpos($Yol, $ScriptName) === 0) {
	$Yol = substr($Yol, strlen($ScriptName));
}
if (strpos($Yol, '/index.php') === 0) {
	$Yol = substr($Yol, strlen('/index.php'));
}

// Boş kalırsa root kabul et
if ($Yol === '' || $Yol === false) {
	$Yol = '/';
}

// Sondaki slash'i temizle (root hariç)
if ($Yol !== '/' && substr($Yol, -1) === '/') {
	$Yol = rtrim($Yol, '/');
}

$Router->dispatch($Metod, $Yol);
