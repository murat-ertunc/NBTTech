<?php
/**
 * Core Bootstrap - Autoloader ve Temel Fonksiyonlar
 * 
 * Bu dosya bootstrap/app.php tarafından yüklenir.
 * Path sabitleri zaten tanımlı olmalıdır.
 */

use App\Core\Config;
use App\Core\Env;
use App\Services\Logger\LoggerFactory;

// Path sabitleri tanımlı değilse (doğrudan çağrılmış), eski yapı için uyumluluk
if (!defined('ROOT_PATH')) {
    // Eski yapı desteği (deprecated) - bootstrap/app.php kullanın
    define('DS', DIRECTORY_SEPARATOR);
    define('ROOT_PATH', dirname(__DIR__, 3) . DS);
    define('SRC_PATH', dirname(__DIR__, 2) . DS);
    define('APP_PATH', dirname(__DIR__) . DS);
    define('CONFIG_PATH', SRC_PATH . 'config' . DS);
    define('ROUTES_PATH', SRC_PATH . 'routes' . DS);
    define('STORAGE_PATH', SRC_PATH . 'storage' . DS);
    define('PUBLIC_PATH', ROOT_PATH . 'public' . DS);
    define('ENV_PATH', ROOT_PATH . '.env');
    define('BASE_PATH', APP_PATH);
}

// PSR-4 Autoloader
spl_autoload_register(function ($Sinif) {
    $Prefix = 'App\\';
    $TemelDizin = APP_PATH;
    if (strncmp($Prefix, $Sinif, strlen($Prefix)) !== 0) {
        return;
    }
    $GoreDizin = substr($Sinif, strlen($Prefix));
    $Dosya = $TemelDizin . str_replace('\\', DS, $GoreDizin) . '.php';
    if (file_exists($Dosya)) {
        require $Dosya;
    }
});

require_once __DIR__ . DS . 'Env.php';
require_once __DIR__ . DS . 'Config.php';

// .env dosyasını yükle
$EnvYolu = ENV_PATH;
if (!file_exists($EnvYolu)) {
    $EnvYolu = ROOT_PATH . '.env.example';
}
Env::load($EnvYolu);

if (!function_exists('env')) {
    function env(string $Anahtar, $Varsayilan = null)
    {
        return Env::get($Anahtar, $Varsayilan);
    }
}

if (!function_exists('config')) {
    function config(string $Anahtar, $Varsayilan = null)
    {
        return Config::get($Anahtar, $Varsayilan);
    }
}

if (!function_exists('logger')) {
    function logger()
    {
        static $Logger = null;
        if ($Logger === null) {
            $Logger = LoggerFactory::make();
        }
        return $Logger;
    }
}

date_default_timezone_set('UTC');
