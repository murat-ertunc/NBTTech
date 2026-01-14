<?php

use App\Controllers\AuthController;
use App\Controllers\CustomerController;
use App\Controllers\DashboardController;
use App\Controllers\InvoiceController;
use App\Controllers\PaymentController;
use App\Core\Response;
use App\Middleware\Auth;
use App\Middleware\Role;

// Sağlık kontrolü endpoint
$Router->add('GET', '/health', function () {
	Response::json([
		'status' => 'ok',
		'app' => config('app.name'),
		'time' => date('c'),
	]);
});

// Kimlik doğrulama endpointleri
$Router->add('POST', '/api/login', fn() => AuthController::login());
$Router->add('POST', '/api/register', fn() => AuthController::register());
$Router->add('POST', '/api/logout', fn() => AuthController::logout());
$Router->add('POST', '/api/refresh', fn() => AuthController::refresh());

// Müşteri işlemleri endpointleri
$Router->add('GET', '/api/customers', function () {
	if (!Auth::yetkilendirmeGerekli()) return;
	if (!Role::rolGerekli(['superadmin', 'user'])) return;
	CustomerController::index();
});
$Router->add('POST', '/api/customers', function () {
	if (!Auth::yetkilendirmeGerekli()) return;
	if (!Role::rolGerekli(['superadmin', 'user'])) return;
	CustomerController::store();
});
$Router->add('PUT', '/api/customers/{id}', function ($Parametreler) {
	if (!Auth::yetkilendirmeGerekli()) return;
	if (!Role::rolGerekli(['superadmin', 'user'])) return;
	CustomerController::update($Parametreler);
});
$Router->add('DELETE', '/api/customers/{id}', function ($Parametreler) {
	if (!Auth::yetkilendirmeGerekli()) return;
	if (!Role::rolGerekli(['superadmin', 'user'])) return;
	CustomerController::delete($Parametreler);
});

// Fatura işlemleri endpointleri
$Router->add('GET', '/api/invoices', function () {
	if (!Auth::yetkilendirmeGerekli()) return;
	if (!Role::rolGerekli(['superadmin', 'user'])) return;
	InvoiceController::index();
});
$Router->add('GET', '/api/invoices/{id}', function ($Parametreler) {
	if (!Auth::yetkilendirmeGerekli()) return;
	if (!Role::rolGerekli(['superadmin', 'user'])) return;
	InvoiceController::show($Parametreler);
});
$Router->add('POST', '/api/invoices', function () {
	if (!Auth::yetkilendirmeGerekli()) return;
	if (!Role::rolGerekli(['superadmin', 'user'])) return;
	InvoiceController::store();
});
$Router->add('PUT', '/api/invoices/{id}', function ($Parametreler) {
	if (!Auth::yetkilendirmeGerekli()) return;
	if (!Role::rolGerekli(['superadmin', 'user'])) return;
	InvoiceController::update($Parametreler);
});
$Router->add('DELETE', '/api/invoices/{id}', function ($Parametreler) {
	if (!Auth::yetkilendirmeGerekli()) return;
	if (!Role::rolGerekli(['superadmin', 'user'])) return;
	InvoiceController::delete($Parametreler);
});

// Ödeme işlemleri endpointleri
$Router->add('GET', '/api/payments', function () {
	if (!Auth::yetkilendirmeGerekli()) return;
	if (!Role::rolGerekli(['superadmin', 'user'])) return;
	PaymentController::index();
});
$Router->add('POST', '/api/payments', function () {
	if (!Auth::yetkilendirmeGerekli()) return;
	if (!Role::rolGerekli(['superadmin', 'user'])) return;
	PaymentController::store();
});
$Router->add('PUT', '/api/payments/{id}', function ($Parametreler) {
	if (!Auth::yetkilendirmeGerekli()) return;
	if (!Role::rolGerekli(['superadmin', 'user'])) return;
	PaymentController::update($Parametreler);
});
$Router->add('DELETE', '/api/payments/{id}', function ($Parametreler) {
	if (!Auth::yetkilendirmeGerekli()) return;
	if (!Role::rolGerekli(['superadmin', 'user'])) return;
	PaymentController::delete($Parametreler);
});


// Proje işlemleri endpointleri
$Router->add('GET', '/api/projects', function () {
	if (!Auth::yetkilendirmeGerekli()) return;
	if (!Role::rolGerekli(['superadmin', 'user'])) return;
	App\Controllers\ProjectController::index();
});
$Router->add('POST', '/api/projects', function () {
	if (!Auth::yetkilendirmeGerekli()) return;
	if (!Role::rolGerekli(['superadmin', 'user'])) return;
	App\Controllers\ProjectController::store();
});
$Router->add('PUT', '/api/projects/{id}', function ($Parametreler) {
	if (!Auth::yetkilendirmeGerekli()) return;
	if (!Role::rolGerekli(['superadmin', 'user'])) return;
	App\Controllers\ProjectController::update($Parametreler);
});
$Router->add('DELETE', '/api/projects/{id}', function ($Parametreler) {
	if (!Auth::yetkilendirmeGerekli()) return;
	if (!Role::rolGerekli(['superadmin', 'user'])) return;
	App\Controllers\ProjectController::delete($Parametreler);
});

// Teklif işlemleri endpointleri
$Router->add('GET', '/api/offers', function () {
	if (!Auth::yetkilendirmeGerekli()) return;
	if (!Role::rolGerekli(['superadmin', 'user'])) return;
	App\Controllers\OfferController::index();
});
$Router->add('POST', '/api/offers', function () {
	if (!Auth::yetkilendirmeGerekli()) return;
	if (!Role::rolGerekli(['superadmin', 'user'])) return;
	App\Controllers\OfferController::store();
});
$Router->add('PUT', '/api/offers/{id}', function ($Parametreler) {
	if (!Auth::yetkilendirmeGerekli()) return;
	if (!Role::rolGerekli(['superadmin', 'user'])) return;
	App\Controllers\OfferController::update($Parametreler);
});
$Router->add('DELETE', '/api/offers/{id}', function ($Parametreler) {
	if (!Auth::yetkilendirmeGerekli()) return;
	if (!Role::rolGerekli(['superadmin', 'user'])) return;
	App\Controllers\OfferController::delete($Parametreler);
});

// Sözleşme işlemleri endpointleri
$Router->add('GET', '/api/contracts', function () {
	if (!Auth::yetkilendirmeGerekli()) return;
	if (!Role::rolGerekli(['superadmin', 'user'])) return;
	App\Controllers\ContractController::index();
});
$Router->add('POST', '/api/contracts', function () {
	if (!Auth::yetkilendirmeGerekli()) return;
	if (!Role::rolGerekli(['superadmin', 'user'])) return;
	App\Controllers\ContractController::store();
});
$Router->add('PUT', '/api/contracts/{id}', function ($Parametreler) {
	if (!Auth::yetkilendirmeGerekli()) return;
	if (!Role::rolGerekli(['superadmin', 'user'])) return;
	App\Controllers\ContractController::update($Parametreler);
});
$Router->add('DELETE', '/api/contracts/{id}', function ($Parametreler) {
	if (!Auth::yetkilendirmeGerekli()) return;
	if (!Role::rolGerekli(['superadmin', 'user'])) return;
	App\Controllers\ContractController::delete($Parametreler);
});

// Teminat işlemleri endpointleri
$Router->add('GET', '/api/guarantees', function () {
	if (!Auth::yetkilendirmeGerekli()) return;
	if (!Role::rolGerekli(['superadmin', 'user'])) return;
	App\Controllers\GuaranteeController::index();
});
$Router->add('POST', '/api/guarantees', function () {
	if (!Auth::yetkilendirmeGerekli()) return;
	if (!Role::rolGerekli(['superadmin', 'user'])) return;
	App\Controllers\GuaranteeController::store();
});
$Router->add('PUT', '/api/guarantees/{id}', function ($Parametreler) {
	if (!Auth::yetkilendirmeGerekli()) return;
	if (!Role::rolGerekli(['superadmin', 'user'])) return;
	App\Controllers\GuaranteeController::update($Parametreler);
});
$Router->add('DELETE', '/api/guarantees/{id}', function ($Parametreler) {
	if (!Auth::yetkilendirmeGerekli()) return;
	if (!Role::rolGerekli(['superadmin', 'user'])) return;
	App\Controllers\GuaranteeController::delete($Parametreler);
});

// Kullanıcı yönetimi endpointleri (sadece superadmin)
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

// Kullanıcı ekleme endpoint (sadece superadmin)
$Router->add('POST', '/api/users', function () {
	if (!Auth::yetkilendirmeGerekli()) return;
	if (!Role::rolGerekli(['superadmin'])) return;
	App\Controllers\UserController::store();
});

// Şifre değiştirme endpoint (her kullanıcı kendi şifresini değiştirebilir)
$Router->add('POST', '/api/users/change-password', function () {
	if (!Auth::yetkilendirmeGerekli()) return;
	App\Controllers\UserController::changePassword();
});

// Dashboard endpoint
$Router->add('GET', '/api/dashboard', function () {
    if (!Auth::yetkilendirmeGerekli()) return;
    DashboardController::index();
});

// Log kayıtları endpoint (sadece superadmin)
$Router->add('GET', '/api/logs', function () {
    if (!Auth::yetkilendirmeGerekli()) return;
    if (!Role::rolGerekli(['superadmin'])) return;
    App\Controllers\LogController::index();
});

// Alarm API endpointleri
$Router->add('GET', '/api/alarms', function () {
    if (!Auth::yetkilendirmeGerekli()) return;
    if (!Role::rolGerekli(['superadmin', 'user'])) return;
    (new App\Controllers\AlarmController())->index();
});

// Takvim API endpointleri
$Router->add('GET', '/api/calendar', function () {
    if (!Auth::yetkilendirmeGerekli()) return;
    if (!Role::rolGerekli(['superadmin', 'user'])) return;
    (new App\Controllers\CalendarController())->index();
});

$Router->add('GET', '/api/calendar/day/{date}', function ($Parametreler) {
    if (!Auth::yetkilendirmeGerekli()) return;
    if (!Role::rolGerekli(['superadmin', 'user'])) return;
    (new App\Controllers\CalendarController())->day($Parametreler['date']);
});

// Görüşme işlemleri endpointleri
$Router->add('GET', '/api/meetings', function () {
    if (!Auth::yetkilendirmeGerekli()) return;
    if (!Role::rolGerekli(['superadmin', 'user'])) return;
    App\Controllers\MeetingController::index();
});
$Router->add('POST', '/api/meetings', function () {
    if (!Auth::yetkilendirmeGerekli()) return;
    if (!Role::rolGerekli(['superadmin', 'user'])) return;
    App\Controllers\MeetingController::store();
});
$Router->add('PUT', '/api/meetings/{id}', function ($Parametreler) {
    if (!Auth::yetkilendirmeGerekli()) return;
    if (!Role::rolGerekli(['superadmin', 'user'])) return;
    App\Controllers\MeetingController::update($Parametreler);
});
$Router->add('DELETE', '/api/meetings/{id}', function ($Parametreler) {
    if (!Auth::yetkilendirmeGerekli()) return;
    if (!Role::rolGerekli(['superadmin', 'user'])) return;
    App\Controllers\MeetingController::delete($Parametreler);
});

// Takvim işlemleri endpointleri (Müşteri detay sayfası takvim tabı)
$Router->add('GET', '/api/takvim', function () {
    if (!Auth::yetkilendirmeGerekli()) return;
    if (!Role::rolGerekli(['superadmin', 'user'])) return;
    App\Controllers\TakvimController::index();
});
$Router->add('POST', '/api/takvim', function () {
    if (!Auth::yetkilendirmeGerekli()) return;
    if (!Role::rolGerekli(['superadmin', 'user'])) return;
    App\Controllers\TakvimController::store();
});
$Router->add('PUT', '/api/takvim/{id}', function ($Parametreler) {
    if (!Auth::yetkilendirmeGerekli()) return;
    if (!Role::rolGerekli(['superadmin', 'user'])) return;
    App\Controllers\TakvimController::update($Parametreler);
});
$Router->add('DELETE', '/api/takvim/{id}', function ($Parametreler) {
    if (!Auth::yetkilendirmeGerekli()) return;
    if (!Role::rolGerekli(['superadmin', 'user'])) return;
    App\Controllers\TakvimController::delete($Parametreler);
});

// Kişi (İletişim) işlemleri endpointleri
$Router->add('GET', '/api/contacts', function () {
    if (!Auth::yetkilendirmeGerekli()) return;
    if (!Role::rolGerekli(['superadmin', 'user'])) return;
    App\Controllers\ContactController::index();
});
$Router->add('POST', '/api/contacts', function () {
    if (!Auth::yetkilendirmeGerekli()) return;
    if (!Role::rolGerekli(['superadmin', 'user'])) return;
    App\Controllers\ContactController::store();
});
$Router->add('PUT', '/api/contacts/{id}', function ($Parametreler) {
    if (!Auth::yetkilendirmeGerekli()) return;
    if (!Role::rolGerekli(['superadmin', 'user'])) return;
    App\Controllers\ContactController::update($Parametreler);
});
$Router->add('DELETE', '/api/contacts/{id}', function ($Parametreler) {
    if (!Auth::yetkilendirmeGerekli()) return;
    if (!Role::rolGerekli(['superadmin', 'user'])) return;
    App\Controllers\ContactController::delete($Parametreler);
});

// Damga vergisi işlemleri endpointleri
$Router->add('GET', '/api/stamp-taxes', function () {
    if (!Auth::yetkilendirmeGerekli()) return;
    if (!Role::rolGerekli(['superadmin', 'user'])) return;
    App\Controllers\StampTaxController::index();
});
$Router->add('POST', '/api/stamp-taxes', function () {
    if (!Auth::yetkilendirmeGerekli()) return;
    if (!Role::rolGerekli(['superadmin', 'user'])) return;
    App\Controllers\StampTaxController::store();
});
$Router->add('PUT', '/api/stamp-taxes/{id}', function ($Parametreler) {
    if (!Auth::yetkilendirmeGerekli()) return;
    if (!Role::rolGerekli(['superadmin', 'user'])) return;
    App\Controllers\StampTaxController::update($Parametreler);
});
$Router->add('DELETE', '/api/stamp-taxes/{id}', function ($Parametreler) {
    if (!Auth::yetkilendirmeGerekli()) return;
    if (!Role::rolGerekli(['superadmin', 'user'])) return;
    App\Controllers\StampTaxController::delete($Parametreler);
});

// Dosya işlemleri endpointleri
$Router->add('GET', '/api/files', function () {
    if (!Auth::yetkilendirmeGerekli()) return;
    if (!Role::rolGerekli(['superadmin', 'user'])) return;
    App\Controllers\FileController::index();
});
$Router->add('POST', '/api/files', function () {
    if (!Auth::yetkilendirmeGerekli()) return;
    if (!Role::rolGerekli(['superadmin', 'user'])) return;
    App\Controllers\FileController::store();
});
$Router->add('PUT', '/api/files/{id}', function ($Parametreler) {
    if (!Auth::yetkilendirmeGerekli()) return;
    if (!Role::rolGerekli(['superadmin', 'user'])) return;
    App\Controllers\FileController::update($Parametreler);
});
$Router->add('DELETE', '/api/files/{id}', function ($Parametreler) {
    if (!Auth::yetkilendirmeGerekli()) return;
    if (!Role::rolGerekli(['superadmin', 'user'])) return;
    App\Controllers\FileController::delete($Parametreler);
});
$Router->add('GET', '/api/files/{id}/download', function ($Parametreler) {
    if (!Auth::yetkilendirmeGerekli()) return;
    if (!Role::rolGerekli(['superadmin', 'user'])) return;
    App\Controllers\FileController::download($Parametreler);
});

// Parametre işlemleri endpointleri
$Router->add('GET', '/api/parameters', function () {
    if (!Auth::yetkilendirmeGerekli()) return;
    if (!Role::rolGerekli(['superadmin'])) return;
    App\Controllers\ParameterController::index();
});
$Router->add('GET', '/api/parameters/currencies', function () {
    if (!Auth::yetkilendirmeGerekli()) return;
    if (!Role::rolGerekli(['superadmin', 'user'])) return;
    App\Controllers\ParameterController::currencies();
});
$Router->add('GET', '/api/parameters/statuses', function () {
    if (!Auth::yetkilendirmeGerekli()) return;
    if (!Role::rolGerekli(['superadmin', 'user'])) return;
    App\Controllers\ParameterController::statuses();
});
$Router->add('GET', '/api/parameters/settings', function () {
    if (!Auth::yetkilendirmeGerekli()) return;
    if (!Role::rolGerekli(['superadmin', 'user'])) return;
    App\Controllers\ParameterController::settings();
});
$Router->add('POST', '/api/parameters', function () {
    if (!Auth::yetkilendirmeGerekli()) return;
    if (!Role::rolGerekli(['superadmin'])) return;
    App\Controllers\ParameterController::store();
});
$Router->add('PUT', '/api/parameters/{id}', function ($Parametreler) {
    if (!Auth::yetkilendirmeGerekli()) return;
    if (!Role::rolGerekli(['superadmin'])) return;
    App\Controllers\ParameterController::update($Parametreler);
});
$Router->add('POST', '/api/parameters/bulk', function () {
    if (!Auth::yetkilendirmeGerekli()) return;
    if (!Role::rolGerekli(['superadmin'])) return;
    App\Controllers\ParameterController::bulkUpdate();
});
$Router->add('DELETE', '/api/parameters/{id}', function ($Parametreler) {
    if (!Auth::yetkilendirmeGerekli()) return;
    if (!Role::rolGerekli(['superadmin'])) return;
    App\Controllers\ParameterController::delete($Parametreler);
});
