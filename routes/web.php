<?php

/**
 * Web Routes - Server-Rendered Sayfa Mimarisi
 * 
 * Her sayfa ayrı bir PHP dosyasından server-side render edilir.
 * SPA routing KALDIRILDI - her route gerçek sayfa yüklemesi yapar.
 * 
 * URL Yapısı:
 *   /               → Dashboard
 *   /dashboard      → Dashboard
 *   /customers      → Müşteri listesi
 *   /customer/{id}  → Müşteri detay (tab URL'de değil, JS state'de)
 *   /invoices       → Fatura listesi
 *   /payments       → Ödeme listesi
 *   /projects       → Proje listesi
 *   /offers         → Teklif listesi
 *   /contracts      → Sözleşme listesi
 *   /guarantees     → Teminat listesi
 *   /users          → Kullanıcı listesi
 *   /logs           → Log listesi
 */

// ===== DASHBOARD =====
$Router->add('GET', '/', function () {
	require __DIR__ . '/../public/pages/dashboard.php';
});

$Router->add('GET', '/dashboard', function () {
	require __DIR__ . '/../public/pages/dashboard.php';
});

// ===== MÜŞTERİLER =====
$Router->add('GET', '/customers', function () {
	require __DIR__ . '/../public/pages/customers.php';
});

$Router->add('GET', '/customer/{id}', function ($params) {
	$customerId = (int)($params['id'] ?? 0);
	require __DIR__ . '/../public/pages/customer-detail.php';
});

// ===== FATURALAR =====
$Router->add('GET', '/invoices', function () {
	require __DIR__ . '/../public/pages/invoices.php';
});

// ===== ÖDEMELER =====
$Router->add('GET', '/payments', function () {
	require __DIR__ . '/../public/pages/payments.php';
});

// ===== PROJELER =====
$Router->add('GET', '/projects', function () {
	require __DIR__ . '/../public/pages/projects.php';
});

// ===== TEKLİFLER =====
$Router->add('GET', '/offers', function () {
	require __DIR__ . '/../public/pages/offers.php';
});

// ===== SÖZLEŞMELER =====
$Router->add('GET', '/contracts', function () {
	require __DIR__ . '/../public/pages/contracts.php';
});

// ===== TEMİNATLAR =====
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

// ===== AUTH =====
$Router->add('GET', '/login', function () {
	require __DIR__ . '/../public/login.php';
});

$Router->add('GET', '/register', function () {
	require __DIR__ . '/../public/register.php';
});
