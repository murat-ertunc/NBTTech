<?php

/**
 * Web Routes - Server-Rendered Sayfa Mimarisi
 * 
 * Her sayfa ayri bir PHP dosyasindan server-side render edilir.
 * SPA routing KALDIRILDI - her route gercek sayfa yuklemesi yapar.
 * 
 * URL Yapisi:
 *   /               → Dashboard
 *   /dashboard      → Dashboard
 *   /customers      → Musteri listesi
 *   /customer/{id}  → Musteri detay (tab URL'de degil, JS state'de)
 *   /invoices       → Fatura listesi
 *   /payments       → Odeme listesi
 *   /projects       → Proje listesi
 *   /offers         → Teklif listesi
 *   /contracts      → Sozlesme listesi
 *   /guarantees     → Teminat listesi
 *   /users          → Kullanici listesi
 *   /logs           → Log listesi
 */

// ===== DASHBOARD =====
$Router->add('GET', '/', function () {
	require __DIR__ . '/../public/pages/dashboard.php';
});

$Router->add('GET', '/dashboard', function () {
	require __DIR__ . '/../public/pages/dashboard.php';
});

// ===== MUSTERILER =====
$Router->add('GET', '/customers', function () {
	require __DIR__ . '/../public/pages/customers.php';
});

$Router->add('GET', '/customer/{id}', function ($Parametreler) {
	$MusteriId = (int)($Parametreler['id'] ?? 0);
	require __DIR__ . '/../public/pages/customer-detail.php';
});

// ===== FATURALAR =====
$Router->add('GET', '/invoices', function () {
	require __DIR__ . '/../public/pages/invoices.php';
});

// ===== ODEMELER =====
$Router->add('GET', '/payments', function () {
	require __DIR__ . '/../public/pages/payments.php';
});

// ===== PROJELER =====
$Router->add('GET', '/projects', function () {
	require __DIR__ . '/../public/pages/projects.php';
});

// ===== TEKLIFLER =====
$Router->add('GET', '/offers', function () {
	require __DIR__ . '/../public/pages/offers.php';
});

// ===== SOZLESMELER =====
$Router->add('GET', '/contracts', function () {
	require __DIR__ . '/../public/pages/contracts.php';
});

// ===== TEMINATLAR =====
$Router->add('GET', '/guarantees', function () {
	require __DIR__ . '/../public/pages/guarantees.php';
});

// ===== KULLANICILAR =====
$Router->add('GET', '/users', function () {
	require __DIR__ . '/../public/pages/users.php';
});

// ===== LOGLAR =====
$Router->add('GET', '/logs', function () {
	require __DIR__ . '/../public/pages/logs.php';
});

// ===== HESABIM =====
$Router->add('GET', '/my-account', function () {
	require __DIR__ . '/../public/pages/my-account.php';
});

// ===== ALARMLAR =====
$Router->add('GET', '/alarms', function () {
	require __DIR__ . '/../public/pages/alarms.php';
});

// ===== PARAMETRELER =====
$Router->add('GET', '/parameters', function () {
	require __DIR__ . '/../public/pages/parameters.php';
});

// ===== AUTH =====
$Router->add('GET', '/login', function () {
	require __DIR__ . '/../public/login.php';
});

$Router->add('GET', '/register', function () {
	require __DIR__ . '/../public/register.php';
});
