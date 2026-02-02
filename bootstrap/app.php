<?php

defined('DS') or define('DS', DIRECTORY_SEPARATOR);

defined('ROOT_PATH') or define('ROOT_PATH', dirname(__DIR__) . DS);

defined('SRC_PATH') or define('SRC_PATH', ROOT_PATH . 'src' . DS);

defined('APP_PATH') or define('APP_PATH', SRC_PATH . 'app' . DS);

defined('CONFIG_PATH') or define('CONFIG_PATH', SRC_PATH . 'config' . DS);

defined('ROUTES_PATH') or define('ROUTES_PATH', SRC_PATH . 'routes' . DS);

defined('STORAGE_PATH') or define('STORAGE_PATH', SRC_PATH . 'storage' . DS);

defined('PUBLIC_PATH') or define('PUBLIC_PATH', ROOT_PATH . 'public' . DS);

defined('ENV_PATH') or define('ENV_PATH', ROOT_PATH . '.env');

defined('BASE_PATH') or define('BASE_PATH', APP_PATH);

require_once APP_PATH . 'Core' . DS . 'bootstrap.php';
