<?php

$RequestUri = $_SERVER['REQUEST_URI'] ?? '/';
$RequestPath = parse_url($RequestUri, PHP_URL_PATH);
if ($RequestPath === false || $RequestPath === null) {
    $RequestPath = '/';
}

$CleanPath = $RequestPath;

if (substr($CleanPath, 0, 8) === '/public/') {
    $CleanPath = substr($CleanPath, 7);
}
if (substr($CleanPath, 0, 7) === 'public/') {
    $CleanPath = '/' . substr($CleanPath, 7);
}

if (substr($CleanPath, 0, 11) === '/index.php/') {
    $CleanPath = substr($CleanPath, 10);
} elseif ($CleanPath === '/index.php') {
    $CleanPath = '/';
}

$CleanPath = ltrim($CleanPath, '/');

if (strpos($CleanPath, 'api/') === 0 ||
    strpos($CleanPath, '__internal__/') === 0 ||
    $CleanPath === 'health' ||
    $CleanPath === 'login') {

    goto bootstrap;
}

$PageRoute = null;
$PageParams = array();

if (strpos($CleanPath, '..') !== false) {

    $PageRoute = null;
} else {

    if (preg_match('#^customer/([0-9]+)/([a-z_-]+)/new$#', $CleanPath, $M)) {
        $MusteriId = (int)$M[1];
        $ModulAdi = $M[2];

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

        $PageRoute = null;
    }

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

        $PageRoute = null;
    }

    elseif ($CleanPath === '__internal__/migrations') {
        $InternalFile = __DIR__ . '/__internal__/migrations.php';
        if (is_file($InternalFile)) {
            require $InternalFile;
            exit;
        }
        $PageRoute = null;
    }

    elseif (preg_match('#^customer/([0-9]+)$#', $CleanPath, $M)) {
        $PageRoute = 'customer-detail';
        $PageParams['id'] = $M[1];
        $_GET['id'] = $M[1];
        $MusteriId = (int)$M[1];
    }

    elseif (preg_match('#^project/([0-9]+)$#', $CleanPath, $M)) {
        $PageRoute = 'projects';
        $PageParams['id'] = $M[1];
        $_GET['id'] = $M[1];
    }

    elseif (preg_match('#^invoice/([0-9]+)$#', $CleanPath, $M)) {
        $PageRoute = 'invoices';
        $PageParams['id'] = $M[1];
        $_GET['id'] = $M[1];
    }

    elseif (preg_match('#^payment/([0-9]+)$#', $CleanPath, $M)) {
        $PageRoute = 'payments';
        $PageParams['id'] = $M[1];
        $_GET['id'] = $M[1];
    }

    elseif (preg_match('#^offer/([0-9]+)$#', $CleanPath, $M)) {
        $PageRoute = 'offers';
        $PageParams['id'] = $M[1];
        $_GET['id'] = $M[1];
    }

    elseif (preg_match('#^contract/([0-9]+)$#', $CleanPath, $M)) {
        $PageRoute = 'contracts';
        $PageParams['id'] = $M[1];
        $_GET['id'] = $M[1];
    }

    elseif (preg_match('#^guarantee/([0-9]+)$#', $CleanPath, $M)) {
        $PageRoute = 'guarantees';
        $PageParams['id'] = $M[1];
        $_GET['id'] = $M[1];
    }

    elseif (preg_match('#^user/([0-9]+)$#', $CleanPath, $M)) {
        $PageRoute = 'users';
        $PageParams['id'] = $M[1];
        $_GET['id'] = $M[1];
    }

    elseif (preg_match('#^pages/([a-zA-Z0-9_-]+)\.php$#', $CleanPath, $M)) {
        $PageRoute = $M[1];
    }

    elseif (preg_match('#^pages/([a-zA-Z0-9_-]+)$#', $CleanPath, $M)) {
        $PageRoute = $M[1];
    }

    elseif (preg_match('#^([a-zA-Z0-9_-]+)$#', $CleanPath, $M)) {
        $PageRoute = $M[1];
    }
}

if ($PageRoute !== null && $PageRoute !== '' && strpos($PageRoute, '..') === false) {

    if (!preg_match('#^[a-zA-Z0-9_-]+$#', $PageRoute)) {
        $PageRoute = null;
    }
}

if ($PageRoute !== null) {
    $PageFile = __DIR__ . DIRECTORY_SEPARATOR . 'pages' . DIRECTORY_SEPARATOR . $PageRoute . '.php';

    if (is_file($PageFile) && is_readable($PageFile)) {

        $GLOBALS['route_params'] = $PageParams;
        require $PageFile;
        exit;
    }
}

bootstrap:

require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'bootstrap' . DIRECTORY_SEPARATOR . 'app.php';

use App\Core\Router;

$Router = new Router();

require ROUTES_PATH . 'api.php';
require ROUTES_PATH . 'web.php';

$Metod = $_SERVER['REQUEST_METHOD'] ?? 'GET';

$PathFromUri = parse_url($RequestUri, PHP_URL_PATH);
if ($PathFromUri === false || $PathFromUri === null) {
    $PathFromUri = '/';
}
$PathInfo = $_SERVER['PATH_INFO'] ?? ($_SERVER['ORIG_PATH_INFO'] ?? null);
$RedirectUrl = $_SERVER['REDIRECT_URL'] ?? null;
$UnencodedUrl = $_SERVER['UNENCODED_URL'] ?? null;
$OriginalUrl = $_SERVER['HTTP_X_ORIGINAL_URL'] ?? null;

$Adaylar = [$OriginalUrl, $UnencodedUrl, $PathInfo, $RedirectUrl, $PathFromUri];
$Yol = null;
foreach ($Adaylar as $Aday) {
    if ($Aday === null || $Aday === '') {
        continue;
    }
    $AdayPath = parse_url($Aday, PHP_URL_PATH);
    if ($AdayPath === false || $AdayPath === null || $AdayPath === '') {
        continue;
    }
    if ($AdayPath === '/index.php' || $AdayPath === '/index.php/') {
        continue;
    }
    if ($AdayPath === '/') {
        if ($Yol === null) {
            $Yol = '/';
        }
        continue;
    }
    $Yol = $AdayPath;
    break;
}

if ($Yol === null) {
    $Yol = '/';
}

if ($Yol === '' || $Yol === false) {
    $Yol = '/';
}
if ($Yol[0] !== '/') {
    $Yol = '/' . $Yol;
}

$ScriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$BaseDir = rtrim(str_replace('\\', '/', dirname($ScriptName)), '/');
if ($BaseDir !== '' && $BaseDir !== '.' && strpos($Yol, $BaseDir) === 0) {
    $Yol = substr($Yol, strlen($BaseDir));
}

if ($ScriptName !== '' && strpos($Yol, $ScriptName) === 0) {
    $Yol = substr($Yol, strlen($ScriptName));
}
if (strpos($Yol, '/index.php') === 0) {
    $Yol = substr($Yol, strlen('/index.php'));
}

if ($Yol === '' || $Yol === false) {
    $Yol = '/';
}

if ($Yol !== '/' && substr($Yol, -1) === '/') {
    $Yol = rtrim($Yol, '/');
}

$Router->dispatch($Metod, $Yol);
