<?php

use App\Controllers\AuthController;
use App\Controllers\CustomerController;
use App\Controllers\DashboardController;
use App\Controllers\InvoiceController;
use App\Controllers\PaymentController;
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

// Fatura Islemleri
$Router->add('GET', '/api/invoices', function () {
	if (!Auth::yetkilendirmeGerekli()) return;
	if (!Role::rolGerekli(['superadmin', 'admin', 'user'])) return;
	InvoiceController::index();
});
$Router->add('POST', '/api/invoices', function () {
	if (!Auth::yetkilendirmeGerekli()) return;
	if (!Role::rolGerekli(['superadmin', 'admin', 'user'])) return;
	InvoiceController::store();
});
$Router->add('PUT', '/api/invoices/{id}', function ($Parametreler) {
	if (!Auth::yetkilendirmeGerekli()) return;
	if (!Role::rolGerekli(['superadmin', 'admin', 'user'])) return;
	InvoiceController::update($Parametreler);
});
$Router->add('DELETE', '/api/invoices/{id}', function ($Parametreler) {
	if (!Auth::yetkilendirmeGerekli()) return;
	if (!Role::rolGerekli(['superadmin', 'admin', 'user'])) return;
	InvoiceController::delete($Parametreler);
});

// Odeme Islemleri
$Router->add('GET', '/api/payments', function () {
	if (!Auth::yetkilendirmeGerekli()) return;
	if (!Role::rolGerekli(['superadmin', 'admin', 'user'])) return;
	PaymentController::index();
});
$Router->add('POST', '/api/payments', function () {
	if (!Auth::yetkilendirmeGerekli()) return;
	if (!Role::rolGerekli(['superadmin', 'admin', 'user'])) return;
	PaymentController::store();
});
$Router->add('PUT', '/api/payments/{id}', function ($Parametreler) {
	if (!Auth::yetkilendirmeGerekli()) return;
	if (!Role::rolGerekli(['superadmin', 'admin', 'user'])) return;
	PaymentController::update($Parametreler);
});
$Router->add('DELETE', '/api/payments/{id}', function ($Parametreler) {
	if (!Auth::yetkilendirmeGerekli()) return;
	if (!Role::rolGerekli(['superadmin', 'admin', 'user'])) return;
	PaymentController::delete($Parametreler);
});


// Proje Islemleri
$Router->add('GET', '/api/projects', function () {
	if (!Auth::yetkilendirmeGerekli()) return;
	if (!Role::rolGerekli(['superadmin', 'admin', 'user'])) return;
	App\Controllers\ProjectController::index();
});
$Router->add('POST', '/api/projects', function () {
	if (!Auth::yetkilendirmeGerekli()) return;
	if (!Role::rolGerekli(['superadmin', 'admin', 'user'])) return;
	App\Controllers\ProjectController::store();
});
$Router->add('PUT', '/api/projects/{id}', function ($Parametreler) {
	if (!Auth::yetkilendirmeGerekli()) return;
	if (!Role::rolGerekli(['superadmin', 'admin', 'user'])) return;
	App\Controllers\ProjectController::update($Parametreler);
});
$Router->add('DELETE', '/api/projects/{id}', function ($Parametreler) {
	if (!Auth::yetkilendirmeGerekli()) return;
	if (!Role::rolGerekli(['superadmin', 'admin', 'user'])) return;
	App\Controllers\ProjectController::delete($Parametreler);
});

// Teklif Islemleri
$Router->add('GET', '/api/offers', function () {
	if (!Auth::yetkilendirmeGerekli()) return;
	if (!Role::rolGerekli(['superadmin', 'admin', 'user'])) return;
	App\Controllers\OfferController::index();
});
$Router->add('POST', '/api/offers', function () {
	if (!Auth::yetkilendirmeGerekli()) return;
	if (!Role::rolGerekli(['superadmin', 'admin', 'user'])) return;
	App\Controllers\OfferController::store();
});
$Router->add('PUT', '/api/offers/{id}', function ($Parametreler) {
	if (!Auth::yetkilendirmeGerekli()) return;
	if (!Role::rolGerekli(['superadmin', 'admin', 'user'])) return;
	App\Controllers\OfferController::update($Parametreler);
});
$Router->add('DELETE', '/api/offers/{id}', function ($Parametreler) {
	if (!Auth::yetkilendirmeGerekli()) return;
	if (!Role::rolGerekli(['superadmin', 'admin', 'user'])) return;
	App\Controllers\OfferController::delete($Parametreler);
});

// Sozlesme Islemleri
$Router->add('GET', '/api/contracts', function () {
	if (!Auth::yetkilendirmeGerekli()) return;
	if (!Role::rolGerekli(['superadmin', 'admin', 'user'])) return;
	App\Controllers\ContractController::index();
});
$Router->add('POST', '/api/contracts', function () {
	if (!Auth::yetkilendirmeGerekli()) return;
	if (!Role::rolGerekli(['superadmin', 'admin', 'user'])) return;
	App\Controllers\ContractController::store();
});
$Router->add('PUT', '/api/contracts/{id}', function ($Parametreler) {
	if (!Auth::yetkilendirmeGerekli()) return;
	if (!Role::rolGerekli(['superadmin', 'admin', 'user'])) return;
	App\Controllers\ContractController::update($Parametreler);
});
$Router->add('DELETE', '/api/contracts/{id}', function ($Parametreler) {
	if (!Auth::yetkilendirmeGerekli()) return;
	if (!Role::rolGerekli(['superadmin', 'admin', 'user'])) return;
	App\Controllers\ContractController::delete($Parametreler);
});

// Teminat Islemleri
$Router->add('GET', '/api/guarantees', function () {
	if (!Auth::yetkilendirmeGerekli()) return;
	if (!Role::rolGerekli(['superadmin', 'admin', 'user'])) return;
	App\Controllers\GuaranteeController::index();
});
$Router->add('POST', '/api/guarantees', function () {
	if (!Auth::yetkilendirmeGerekli()) return;
	if (!Role::rolGerekli(['superadmin', 'admin', 'user'])) return;
	App\Controllers\GuaranteeController::store();
});
$Router->add('PUT', '/api/guarantees/{id}', function ($Parametreler) {
	if (!Auth::yetkilendirmeGerekli()) return;
	if (!Role::rolGerekli(['superadmin', 'admin', 'user'])) return;
	App\Controllers\GuaranteeController::update($Parametreler);
});
$Router->add('DELETE', '/api/guarantees/{id}', function ($Parametreler) {
	if (!Auth::yetkilendirmeGerekli()) return;
	if (!Role::rolGerekli(['superadmin', 'admin', 'user'])) return;
	App\Controllers\GuaranteeController::delete($Parametreler);
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

$Router->add('PUT', '/api/users/{id}', function ($Parametreler) {
	if (!Auth::yetkilendirmeGerekli()) return;
	if (!Role::rolGerekli(['superadmin'])) return;
	App\Controllers\UserController::update($Parametreler);
});

$Router->add('DELETE', '/api/users/{id}', function ($Parametreler) {
	if (!Auth::yetkilendirmeGerekli()) return;
	if (!Role::rolGerekli(['superadmin'])) return;
	App\Controllers\UserController::delete($Parametreler);
});

// Kullanıcı ekleme (sadece superadmin)
$Router->add('POST', '/api/users', function () {
	if (!Auth::yetkilendirmeGerekli()) return;
	if (!Role::rolGerekli(['superadmin'])) return;
	App\Controllers\UserController::store();
});

// Şifre değiştirme (her kullanıcı kendi şifresini değiştirebilir)
$Router->add('POST', '/api/users/change-password', function () {
	if (!Auth::yetkilendirmeGerekli()) return;
	App\Controllers\UserController::changePassword();
});

// Dashboard
$Router->add('GET', '/api/dashboard', function () {
    if (!Auth::yetkilendirmeGerekli()) return;
    DashboardController::index();
});

// Log Kayitlari (sadece superadmin ve admin)
$Router->add('GET', '/api/logs', function () {
    if (!Auth::yetkilendirmeGerekli()) return;
    if (!Role::rolGerekli(['superadmin', 'admin'])) return;
    App\Controllers\LogController::index();
});

// Alarm API
$Router->add('GET', '/api/alarms', function () {
    if (!Auth::yetkilendirmeGerekli()) return;
    if (!Role::rolGerekli(['superadmin', 'admin', 'user'])) return;
    (new App\Controllers\AlarmController())->index();
});

// Calendar API
$Router->add('GET', '/api/calendar', function () {
    if (!Auth::yetkilendirmeGerekli()) return;
    if (!Role::rolGerekli(['superadmin', 'admin', 'user'])) return;
    (new App\Controllers\CalendarController())->index();
});

$Router->add('GET', '/api/calendar/day/{date}', function ($Parametreler) {
    if (!Auth::yetkilendirmeGerekli()) return;
    if (!Role::rolGerekli(['superadmin', 'admin', 'user'])) return;
    (new App\Controllers\CalendarController())->day($Parametreler['date']);
});

// Görüşme İşlemleri
$Router->add('GET', '/api/meetings', function () {
    if (!Auth::yetkilendirmeGerekli()) return;
    if (!Role::rolGerekli(['superadmin', 'admin', 'user'])) return;
    App\Controllers\MeetingController::index();
});
$Router->add('POST', '/api/meetings', function () {
    if (!Auth::yetkilendirmeGerekli()) return;
    if (!Role::rolGerekli(['superadmin', 'admin', 'user'])) return;
    App\Controllers\MeetingController::store();
});
$Router->add('PUT', '/api/meetings/{id}', function ($Parametreler) {
    if (!Auth::yetkilendirmeGerekli()) return;
    if (!Role::rolGerekli(['superadmin', 'admin', 'user'])) return;
    App\Controllers\MeetingController::update($Parametreler);
});
$Router->add('DELETE', '/api/meetings/{id}', function ($Parametreler) {
    if (!Auth::yetkilendirmeGerekli()) return;
    if (!Role::rolGerekli(['superadmin', 'admin', 'user'])) return;
    App\Controllers\MeetingController::delete($Parametreler);
});

// Kişi (İletişim) İşlemleri
$Router->add('GET', '/api/contacts', function () {
    if (!Auth::yetkilendirmeGerekli()) return;
    if (!Role::rolGerekli(['superadmin', 'admin', 'user'])) return;
    App\Controllers\ContactController::index();
});
$Router->add('POST', '/api/contacts', function () {
    if (!Auth::yetkilendirmeGerekli()) return;
    if (!Role::rolGerekli(['superadmin', 'admin', 'user'])) return;
    App\Controllers\ContactController::store();
});
$Router->add('PUT', '/api/contacts/{id}', function ($Parametreler) {
    if (!Auth::yetkilendirmeGerekli()) return;
    if (!Role::rolGerekli(['superadmin', 'admin', 'user'])) return;
    App\Controllers\ContactController::update($Parametreler);
});
$Router->add('DELETE', '/api/contacts/{id}', function ($Parametreler) {
    if (!Auth::yetkilendirmeGerekli()) return;
    if (!Role::rolGerekli(['superadmin', 'admin', 'user'])) return;
    App\Controllers\ContactController::delete($Parametreler);
});

// Damga Vergisi İşlemleri
$Router->add('GET', '/api/stamp-taxes', function () {
    if (!Auth::yetkilendirmeGerekli()) return;
    if (!Role::rolGerekli(['superadmin', 'admin', 'user'])) return;
    App\Controllers\StampTaxController::index();
});
$Router->add('POST', '/api/stamp-taxes', function () {
    if (!Auth::yetkilendirmeGerekli()) return;
    if (!Role::rolGerekli(['superadmin', 'admin', 'user'])) return;
    App\Controllers\StampTaxController::store();
});
$Router->add('PUT', '/api/stamp-taxes/{id}', function ($Parametreler) {
    if (!Auth::yetkilendirmeGerekli()) return;
    if (!Role::rolGerekli(['superadmin', 'admin', 'user'])) return;
    App\Controllers\StampTaxController::update($Parametreler);
});
$Router->add('DELETE', '/api/stamp-taxes/{id}', function ($Parametreler) {
    if (!Auth::yetkilendirmeGerekli()) return;
    if (!Role::rolGerekli(['superadmin', 'admin', 'user'])) return;
    App\Controllers\StampTaxController::delete($Parametreler);
});

// Dosya İşlemleri
$Router->add('GET', '/api/files', function () {
    if (!Auth::yetkilendirmeGerekli()) return;
    if (!Role::rolGerekli(['superadmin', 'admin', 'user'])) return;
    App\Controllers\FileController::index();
});
$Router->add('POST', '/api/files', function () {
    if (!Auth::yetkilendirmeGerekli()) return;
    if (!Role::rolGerekli(['superadmin', 'admin', 'user'])) return;
    App\Controllers\FileController::store();
});
$Router->add('PUT', '/api/files/{id}', function ($Parametreler) {
    if (!Auth::yetkilendirmeGerekli()) return;
    if (!Role::rolGerekli(['superadmin', 'admin', 'user'])) return;
    App\Controllers\FileController::update($Parametreler);
});
$Router->add('DELETE', '/api/files/{id}', function ($Parametreler) {
    if (!Auth::yetkilendirmeGerekli()) return;
    if (!Role::rolGerekli(['superadmin', 'admin', 'user'])) return;
    App\Controllers\FileController::delete($Parametreler);
});
$Router->add('GET', '/api/files/{id}/download', function ($Parametreler) {
    if (!Auth::yetkilendirmeGerekli()) return;
    if (!Role::rolGerekli(['superadmin', 'admin', 'user'])) return;
    App\Controllers\FileController::download($Parametreler);
});
