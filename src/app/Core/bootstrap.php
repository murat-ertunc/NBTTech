<?php







use App\Core\Config;
use App\Core\Env;
use App\Services\Logger\LoggerFactory;


if (!defined('ROOT_PATH')) {
    
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





set_exception_handler(function (\Throwable $Exception) {
    $RequestUri = $_SERVER['REQUEST_URI'] ?? '';
    $IsApiRequest = strpos($RequestUri, '/api/') !== false;
    
    
    $LogMessage = sprintf(
        "[UNCAUGHT EXCEPTION] %s: %s in %s:%d\nStack trace:\n%s",
        get_class($Exception),
        $Exception->getMessage(),
        $Exception->getFile(),
        $Exception->getLine(),
        $Exception->getTraceAsString()
    );
    error_log($LogMessage);
    
    
    if (function_exists('logger')) {
        try {
            logger()->error($LogMessage);
        } catch (\Throwable $LogError) {
            
        }
    }
    
    if ($IsApiRequest) {
        
        http_response_code(500);
        header('Content-Type: application/json');
        
        $Response = [
            'ok' => false,
            'error' => [
                'code' => 'INTERNAL_ERROR',
                'message' => 'Sunucu hatası oluştu.'
            ]
        ];
        
        
        if (env('APP_DEBUG', false) === true || env('APP_DEBUG', 'false') === 'true') {
            $Response['error']['debug'] = [
                'exception' => get_class($Exception),
                'message' => $Exception->getMessage(),
                'file' => $Exception->getFile(),
                'line' => $Exception->getLine()
            ];
        }
        
        echo json_encode($Response, JSON_UNESCAPED_UNICODE);
    } else {
        
        http_response_code(500);
        
        if (defined('PUBLIC_PATH') && file_exists(PUBLIC_PATH . '500.php')) {
            require PUBLIC_PATH . '500.php';
        } else {
            echo "<h1>500 - Internal Server Error</h1>";
            if (env('APP_DEBUG', false) === true || env('APP_DEBUG', 'false') === 'true') {
                echo "<pre>" . htmlspecialchars($Exception->getMessage()) . "</pre>";
            }
        }
    }
    
    exit(1);
});


set_error_handler(function ($Severity, $Message, $File, $Line) {
    
    if (!(error_reporting() & $Severity)) {
        return false;
    }
    throw new \ErrorException($Message, 0, $Severity, $File, $Line);
});
