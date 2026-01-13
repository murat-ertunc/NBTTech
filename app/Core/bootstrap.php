<?php

use App\Core\Config;
use App\Core\Env;
use App\Services\Logger\LoggerFactory;

const BASE_PATH = __DIR__ . '/..';
const ROOT_PATH = __DIR__ . '/../../';

spl_autoload_register(function ($Sinif) {
    $Prefix = 'App\\';
    $TemelDizin = __DIR__ . '/../';
    if (strncmp($Prefix, $Sinif, strlen($Prefix)) !== 0) {
        return;
    }
    $GoreDizin = substr($Sinif, strlen($Prefix));
    $Dosya = $TemelDizin . str_replace('\\', '/', $GoreDizin) . '.php';
    if (file_exists($Dosya)) {
        require $Dosya;
    }
});

require_once __DIR__ . '/Env.php';
require_once __DIR__ . '/Config.php';

$EnvYolu = ROOT_PATH . '.env';
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
