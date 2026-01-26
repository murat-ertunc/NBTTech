<?php
/**
 * FTP Hosting Entry Point - Root Proxy
 *
 * Document root değiştirilemeyen hostinglerde tüm istekleri /public altına devreder.
 */

define('PROJECT_ROOT', __DIR__);

require PROJECT_ROOT . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'index.php';
