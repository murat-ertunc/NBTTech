<?php
require __DIR__ . '/../app/Core/bootstrap.php';

use App\Core\Router;

$Router = new Router();

require __DIR__ . '/../routes/api.php';
require __DIR__ . '/../routes/web.php';

$Metod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$Yol = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

$Router->dispatch($Metod, $Yol);
