<?php

/**
 * Web Routes - Server-Rendered Sayfa Mimarisi
 * 
 * Her sayfa ayri bir PHP dosyasindan server-side render edilir.
 * SPA routing KALDIRILDI - her route gercek sayfa yuklemesi yapar.
 * 
 * RBAC: Tum sayfa route'lari Page middleware ile korunur.
 * Page::can('module.action') - Hem auth hem permission kontrolu yapar.
 * 
 * URL Yapisi:
 *   /               → Dashboard (dashboard.read)
 *   /dashboard      → Dashboard (dashboard.read)
 *   /customer/{id}  → Musteri detay (customers.read)
 *   /invoices       → Fatura listesi (invoices.read)
 *   /payments       → Odeme listesi (payments.read)
 *   /projects       → Proje listesi (projects.read)
 *   /offers         → Teklif listesi (offers.read)
 *   /contracts      → Sozlesme listesi (contracts.read)
 *   /guarantees     → Teminat listesi (guarantees.read)
 *   /users          → Kullanici listesi (users.read)
 *   /roles          → Rol listesi (roles.read)
 *   /logs           → Log listesi (logs.read)
 *   /parameters     → Parametreler (parameters.read)
 *   /alarms         → Alarmlar (alarms.read)
 */

use App\Middleware\Page;

// ===== AUTH (Korumasiz) =====
$Router->add('GET', '/login', function () {
	require __DIR__ . '/../public/login.php';
});

// ===== DASHBOARD =====
$Router->add('GET', '/', function () {
	if (!Page::can('dashboard.read')) return;
	require __DIR__ . '/../public/pages/dashboard.php';
});

$Router->add('GET', '/dashboard', function () {
	if (!Page::can('dashboard.read')) return;
	require __DIR__ . '/../public/pages/dashboard.php';
});

// ===== MUSTERILER =====
// /customers kaldırıldı - müşteri listesi artık sol sidebar'da global olarak gösteriliyor

$Router->add('GET', '/customer/{id}', function ($Parametreler) {
	if (!Page::can('customers.read')) return;
	$MusteriId = (int)($Parametreler['id'] ?? 0);
	require __DIR__ . '/../public/pages/customer-detail.php';
});

// ===== FATURALAR =====
$Router->add('GET', '/invoices', function () {
	if (!Page::can('invoices.read')) return;
	require __DIR__ . '/../public/pages/invoices.php';
});

// ===== ODEMELER =====
$Router->add('GET', '/payments', function () {
	if (!Page::can('payments.read')) return;
	require __DIR__ . '/../public/pages/payments.php';
});

// ===== PROJELER =====
$Router->add('GET', '/projects', function () {
	if (!Page::can('projects.read')) return;
	require __DIR__ . '/../public/pages/projects.php';
});

// ===== TEKLIFLER =====
$Router->add('GET', '/offers', function () {
	if (!Page::can('offers.read')) return;
	require __DIR__ . '/../public/pages/offers.php';
});

// ===== SOZLESMELER =====
$Router->add('GET', '/contracts', function () {
	if (!Page::can('contracts.read')) return;
	require __DIR__ . '/../public/pages/contracts.php';
});

// ===== TEMINATLAR =====
$Router->add('GET', '/guarantees', function () {
	if (!Page::can('guarantees.read')) return;
	require __DIR__ . '/../public/pages/guarantees.php';
});

// ===== KULLANICILAR =====
$Router->add('GET', '/users', function () {
	if (!Page::can('users.read')) return;
	require __DIR__ . '/../public/pages/users.php';
});

// ===== ROLLER =====
$Router->add('GET', '/roles', function () {
	if (!Page::can('roles.read')) return;
	require __DIR__ . '/../public/pages/roles.php';
});

// ===== LOGLAR =====
$Router->add('GET', '/logs', function () {
	if (!Page::can('logs.read')) return;
	require __DIR__ . '/../public/pages/logs.php';
});

// ===== HESABIM =====
$Router->add('GET', '/my-account', function () {
	// Kendi hesabına herkes erişebilir (sadece auth gerekli)
	if (!Page::auth()) return;
	require __DIR__ . '/../public/pages/my-account.php';
});

// ===== ALARMLAR =====
$Router->add('GET', '/alarms', function () {
	if (!Page::can('alarms.read')) return;
	require __DIR__ . '/../public/pages/alarms.php';
});

// ===== PARAMETRELER =====
$Router->add('GET', '/parameters', function () {
	if (!Page::can('parameters.read')) return;
	require __DIR__ . '/../public/pages/parameters.php';
});
