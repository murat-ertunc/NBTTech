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

// =============================================================================
// API ve INTERNAL ROUTES - Bootstrap'a direkt gönder
// Bu route'lar page router tarafından işlenmemeli
// =============================================================================
if (strpos($CleanPath, 'api/') === 0 || 
    strpos($CleanPath, '__internal__/') === 0 || 
    $CleanPath === 'health' ||
    $CleanPath === 'login') {
    // Bootstrap'a düşsün, page router atlansın
    goto bootstrap;
}

// =============================================================================
// PAGE ROUTER - IIS Pretty URL Desteği
// Güvenlik: '..' path traversal engeli, sadece güvenli karakterler
// PHP 7.4 uyumlu (str_starts_with yok)
// =============================================================================

$PageRoute = null;
$PageParams = array();

// Güvenlik: Path traversal kontrolü
if (strpos($CleanPath, '..') !== false) {
    // Tehlikeli path, bootstrap'a devret
    $PageRoute = null;
} else {
    // ==========================================================================
    // ROUTE TANIMLARI (öncelik sırasına göre)
    // Önemli: Daha spesifik route'lar önce gelmeli!
    // ==========================================================================
    
    // =========================================================================
    // CUSTOMER SUB-ROUTES: /customer/{id}/{module}/new veya /customer/{id}/{module}/{entityId}/edit
    // Form sayfalarını doğrudan yükle (web.php Router'a bırakmadan)
    // =========================================================================
    
    // Pattern A: customer/{id}/{module}/new → İlgili form sayfasını yükle
    if (preg_match('#^customer/([0-9]+)/([a-z_-]+)/new$#', $CleanPath, $M)) {
        $MusteriId = (int)$M[1];
        $ModulAdi = $M[2];
        
        // Modül adı → dizin eşleştirmesi
        $ModulDizinMap = [
            'contacts' => 'contacts',
            'offers' => 'offers',
            'contracts' => 'contracts',
            'meetings' => 'meetings',
            'projects' => 'projects',
            'calendar' => 'calendar',
            'stamp-taxes' => 'stamp-taxes',
            'guarantees' => 'guarantees',
            'invoices' => 'invoices',
            'payments' => 'payments',
            'files' => 'files'
        ];
        
        if (isset($ModulDizinMap[$ModulAdi])) {
            $FormDosya = __DIR__ . '/pages/' . $ModulDizinMap[$ModulAdi] . '/form.php';
            if (is_file($FormDosya)) {
                // Form sayfasına gerekli değişkenleri set et
                // Edit olmadığı için entity ID = 0
                $EntityIdVars = [
                    'contacts' => 'KisiId',
                    'offers' => 'TeklifId', 
                    'contracts' => 'SozlesmeId',
                    'meetings' => 'GorusmeId',
                    'projects' => 'ProjeId',
                    'calendar' => 'TakvimId',
                    'stamp-taxes' => 'DamgaId',
                    'guarantees' => 'TeminatId',
                    'invoices' => 'FaturaId',
                    'payments' => 'OdemeId',
                    'files' => 'DosyaId'
                ];
                ${$EntityIdVars[$ModulAdi] ?? 'EntityId'} = 0;
                require $FormDosya;
                exit;
            }
        }
        // Eşleşme yoksa web.php'ye bırak
        $PageRoute = null;
    }
    // Pattern B: customer/{id}/{module}/{entityId}/edit → İlgili form sayfasını yükle
    elseif (preg_match('#^customer/([0-9]+)/([a-z_-]+)/([0-9]+)/edit$#', $CleanPath, $M)) {
        $MusteriId = (int)$M[1];
        $ModulAdi = $M[2];
        $EntityId = (int)$M[3];
        
        $ModulDizinMap = [
            'contacts' => 'contacts',
            'offers' => 'offers',
            'contracts' => 'contracts',
            'meetings' => 'meetings',
            'projects' => 'projects',
            'calendar' => 'calendar',
            'stamp-taxes' => 'stamp-taxes',
            'guarantees' => 'guarantees',
            'invoices' => 'invoices',
            'payments' => 'payments',
            'files' => 'files'
        ];
        
        if (isset($ModulDizinMap[$ModulAdi])) {
            $FormDosya = __DIR__ . '/pages/' . $ModulDizinMap[$ModulAdi] . '/form.php';
            if (is_file($FormDosya)) {
                // Entity ID değişkenlerini set et
                $EntityIdVars = [
                    'contacts' => 'KisiId',
                    'offers' => 'TeklifId',
                    'contracts' => 'SozlesmeId', 
                    'meetings' => 'GorusmeId',
                    'projects' => 'ProjeId',
                    'calendar' => 'TakvimId',
                    'stamp-taxes' => 'DamgaId',
                    'guarantees' => 'TeminatId',
                    'invoices' => 'FaturaId',
                    'payments' => 'OdemeId',
                    'files' => 'DosyaId'
                ];
                ${$EntityIdVars[$ModulAdi] ?? 'EntityId'} = $EntityId;
                require $FormDosya;
                exit;
            }
        }
        // Eşleşme yoksa web.php'ye bırak
        $PageRoute = null;
    }
    // Internal: __internal__/migrations
    elseif ($CleanPath === '__internal__/migrations') {
        $InternalFile = __DIR__ . '/__internal__/migrations.php';
        if (is_file($InternalFile)) {
            require $InternalFile;
            exit;
        }
        $PageRoute = null;
    }
    // Pattern 1: customer/{id} → customer-detail.php (ID ile)
    elseif (preg_match('#^customer/([0-9]+)$#', $CleanPath, $M)) {
        $PageRoute = 'customer-detail';
        $PageParams['id'] = $M[1];
        $_GET['id'] = $M[1]; // Query param olarak da ata
    }
    // Pattern 2: project/{id} → projects.php veya project-detail.php
    elseif (preg_match('#^project/([0-9]+)$#', $CleanPath, $M)) {
        $PageRoute = 'projects'; // veya project-detail varsa onu kullan
        $PageParams['id'] = $M[1];
        $_GET['id'] = $M[1];
    }
    // Pattern 3: invoice/{id} → invoices.php
    elseif (preg_match('#^invoice/([0-9]+)$#', $CleanPath, $M)) {
        $PageRoute = 'invoices';
        $PageParams['id'] = $M[1];
        $_GET['id'] = $M[1];
    }
    // Pattern 4: payment/{id} → payments.php
    elseif (preg_match('#^payment/([0-9]+)$#', $CleanPath, $M)) {
        $PageRoute = 'payments';
        $PageParams['id'] = $M[1];
        $_GET['id'] = $M[1];
    }
    // Pattern 5: offer/{id} → offers.php
    elseif (preg_match('#^offer/([0-9]+)$#', $CleanPath, $M)) {
        $PageRoute = 'offers';
        $PageParams['id'] = $M[1];
        $_GET['id'] = $M[1];
    }
    // Pattern 6: contract/{id} → contracts.php
    elseif (preg_match('#^contract/([0-9]+)$#', $CleanPath, $M)) {
        $PageRoute = 'contracts';
        $PageParams['id'] = $M[1];
        $_GET['id'] = $M[1];
    }
    // Pattern 7: guarantee/{id} → guarantees.php
    elseif (preg_match('#^guarantee/([0-9]+)$#', $CleanPath, $M)) {
        $PageRoute = 'guarantees';
        $PageParams['id'] = $M[1];
        $_GET['id'] = $M[1];
    }
    // Pattern 8: user/{id} → users.php
    elseif (preg_match('#^user/([0-9]+)$#', $CleanPath, $M)) {
        $PageRoute = 'users';
        $PageParams['id'] = $M[1];
        $_GET['id'] = $M[1];
    }
    // Pattern 9: pages/xxx.php → doğrudan eşleştir
    elseif (preg_match('#^pages/([a-zA-Z0-9_-]+)\.php$#', $CleanPath, $M)) {
        $PageRoute = $M[1];
    }
    // Pattern 10: pages/xxx (php olmadan)
    elseif (preg_match('#^pages/([a-zA-Z0-9_-]+)$#', $CleanPath, $M)) {
        $PageRoute = $M[1];
    }
    // Pattern 11: xxx (tek segment - dashboard, users, logs vb.)
    elseif (preg_match('#^([a-zA-Z0-9_-]+)$#', $CleanPath, $M)) {
        $PageRoute = $M[1];
    }
}

// Page dosyası varsa require et ve çık
if ($PageRoute !== null && $PageRoute !== '' && strpos($PageRoute, '..') === false) {
    // Güvenlik: Sadece alfanumerik, tire ve alt çizgi
    if (!preg_match('#^[a-zA-Z0-9_-]+$#', $PageRoute)) {
        $PageRoute = null;
    }
}

if ($PageRoute !== null) {
    $PageFile = __DIR__ . DIRECTORY_SEPARATOR . 'pages' . DIRECTORY_SEPARATOR . $PageRoute . '.php';
    
    if (is_file($PageFile) && is_readable($PageFile)) {
        // Route parametrelerini global yap (isteğe bağlı)
        $GLOBALS['route_params'] = $PageParams;
        require $PageFile;
        exit;
    }
}

// =============================================================================
// BOOTSTRAP: Page değilse normal uygulama akışı
// Label: API ve internal route'lar için goto hedefi
// =============================================================================
bootstrap:
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
