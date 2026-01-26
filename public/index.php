<?php
/**
 * Front Controller - Ana Giriş Noktası
 * 
 * Tüm web istekleri bu dosya üzerinden yönlendirilir.
 */

// Bootstrap yükle (merkezi init)
require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'bootstrap' . DIRECTORY_SEPARATOR . 'app.php';

use App\Core\Router;

$Router = new Router();

// Route tanımlarını yükle
require ROUTES_PATH . 'api.php';
require ROUTES_PATH . 'web.php';

$Metod = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// PATH_INFO/REDIRECT_URL destekli yol tespiti (bazı sunucularda REQUEST_URI sadece /index.php olur)
$RequestUri = $_SERVER['REQUEST_URI'] ?? '/';
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
