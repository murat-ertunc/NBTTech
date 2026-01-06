<?php

use App\Controllers\AuthController;
use App\Controllers\CustomerController;
use App\Core\Response;
use App\Middleware\Auth;
use App\Middleware\Role;

// Saglik kontrolu
$Router->add('GET', '/health', function () {
	Response::json([
		'status' => 'ok',
		'app' => config('app.name'),
		'time' => date('c'),
	]);
});

// Kimlik dogrulama
$Router->add('POST', '/api/login', fn() => AuthController::login());
$Router->add('POST', '/api/register', fn() => AuthController::register());
$Router->add('POST', '/api/logout', fn() => AuthController::logout());
$Router->add('POST', '/api/refresh', fn() => AuthController::refresh());

// Musteri islemleri
$Router->add('GET', '/api/customers', function () {
	if (!Auth::yetkilendirmeGerekli()) return;
	if (!Role::rolGerekli(['superadmin', 'admin', 'user'])) return;
	CustomerController::index();
});
$Router->add('POST', '/api/customers', function () {
	if (!Auth::yetkilendirmeGerekli()) return;
	if (!Role::rolGerekli(['superadmin', 'admin', 'user'])) return;
	CustomerController::store();
});
$Router->add('PUT', '/api/customers/{id}', function ($Parametreler) {
	if (!Auth::yetkilendirmeGerekli()) return;
	if (!Role::rolGerekli(['superadmin', 'admin', 'user'])) return;
	CustomerController::update($Parametreler);
});
$Router->add('DELETE', '/api/customers/{id}', function ($Parametreler) {
	if (!Auth::yetkilendirmeGerekli()) return;
	if (!Role::rolGerekli(['superadmin', 'admin', 'user'])) return;
	CustomerController::delete($Parametreler);
});

// Kullanici yonetimi (sadece superadmin)
$Router->add('GET', '/api/users', function () {
	if (!Auth::yetkilendirmeGerekli()) return;
	if (!Role::rolGerekli(['superadmin'])) return;
	App\Controllers\UserController::index();
});

$Router->add('PUT', '/api/users/{id}/block', function ($Parametreler) {
	if (!Auth::yetkilendirmeGerekli()) return;
	if (!Role::rolGerekli(['superadmin'])) return;
	App\Controllers\UserController::block($Parametreler);
});

$Router->add('DELETE', '/api/users/{id}', function ($Parametreler) {
	if (!Auth::yetkilendirmeGerekli()) return;
	if (!Role::rolGerekli(['superadmin'])) return;
	App\Controllers\UserController::delete($Parametreler);
});
