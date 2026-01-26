<?php
/**
 * Bootstrap / Application Initialization
 * 
 * Bu dosya tüm entrypoint'ler tarafından kullanılır.
 * Path sabitlerini tanımlar ve src/app/Core/bootstrap.php'yi yükler.
 * 
 * Dizin Yapısı:
 *   /bootstrap/app.php      ← Bu dosya (merkezi init)
 *   /src/app/               ← Uygulama kodu
 *   /src/config/            ← Konfigürasyon
 *   /src/routes/            ← Route tanımları
 *   /src/storage/           ← Logs, uploads
 *   /public/                ← Web root (Document Root)
 *   /.env                   ← Environment (repo root, web dışı)
 */

// Path sabitleri - DIRECTORY_SEPARATOR ile platform bağımsız
defined('DS') or define('DS', DIRECTORY_SEPARATOR);

// Repo kök dizini (bootstrap/ üst dizini)
defined('ROOT_PATH') or define('ROOT_PATH', dirname(__DIR__) . DS);

// src dizini (web erişimine kapalı uygulama kodu)
defined('SRC_PATH') or define('SRC_PATH', ROOT_PATH . 'src' . DS);

// app dizini (controller, model, service, core)
defined('APP_PATH') or define('APP_PATH', SRC_PATH . 'app' . DS);

// config dizini
defined('CONFIG_PATH') or define('CONFIG_PATH', SRC_PATH . 'config' . DS);

// routes dizini
defined('ROUTES_PATH') or define('ROUTES_PATH', SRC_PATH . 'routes' . DS);

// storage dizini (logs, uploads - yazılabilir)
defined('STORAGE_PATH') or define('STORAGE_PATH', SRC_PATH . 'storage' . DS);

// public dizini (web root)
defined('PUBLIC_PATH') or define('PUBLIC_PATH', ROOT_PATH . 'public' . DS);

// .env dosya yolu (repo root'ta, public dışında)
defined('ENV_PATH') or define('ENV_PATH', ROOT_PATH . '.env');

// Eski sabitler için geriye uyumluluk (deprecated, kaldırılacak)
defined('BASE_PATH') or define('BASE_PATH', APP_PATH);

// Core bootstrap'ı yükle
require_once APP_PATH . 'Core' . DS . 'bootstrap.php';
