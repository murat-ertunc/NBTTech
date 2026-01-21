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

$Router->add('GET', '/customer/{id}', function ($Parametreler) {
	if (!Page::can('customers.read')) return;
	$MusteriId = (int)($Parametreler['id'] ?? 0);
	require __DIR__ . '/../public/pages/customer-detail.php';
});

// ===== MÜŞTERI TEKLIFLERI (Sayfa Bazlı Form) =====
$Router->add('GET', '/customer/{id}/offers/new', function ($Parametreler) {
	if (!Page::can('offers.create')) return;
	$MusteriId = (int)($Parametreler['id'] ?? 0);
	$TeklifId = 0;
	require __DIR__ . '/../public/pages/offers/form.php';
});

$Router->add('GET', '/customer/{id}/offers/{offerId}/edit', function ($Parametreler) {
	if (!Page::can('offers.update')) return;
	$MusteriId = (int)($Parametreler['id'] ?? 0);
	$TeklifId = (int)($Parametreler['offerId'] ?? 0);
	require __DIR__ . '/../public/pages/offers/form.php';
});

// ===== MÜŞTERI SÖZLEŞMELERI (Sayfa Bazlı Form) =====
$Router->add('GET', '/customer/{id}/contracts/new', function ($Parametreler) {
	if (!Page::can('contracts.create')) return;
	$MusteriId = (int)($Parametreler['id'] ?? 0);
	$SozlesmeId = 0;
	require __DIR__ . '/../public/pages/contracts/form.php';
});

$Router->add('GET', '/customer/{id}/contracts/{contractId}/edit', function ($Parametreler) {
	if (!Page::can('contracts.update')) return;
	$MusteriId = (int)($Parametreler['id'] ?? 0);
	$SozlesmeId = (int)($Parametreler['contractId'] ?? 0);
	require __DIR__ . '/../public/pages/contracts/form.php';
});

// ===== MÜŞTERI KIŞILERI (Sayfa Bazlı Form) =====
$Router->add('GET', '/customer/{id}/contacts/new', function ($Parametreler) {
	if (!Page::can('contacts.create')) return;
	$MusteriId = (int)($Parametreler['id'] ?? 0);
	$KisiId = 0;
	require __DIR__ . '/../public/pages/contacts/form.php';
});

$Router->add('GET', '/customer/{id}/contacts/{contactId}/edit', function ($Parametreler) {
	if (!Page::can('contacts.update')) return;
	$MusteriId = (int)($Parametreler['id'] ?? 0);
	$KisiId = (int)($Parametreler['contactId'] ?? 0);
	require __DIR__ . '/../public/pages/contacts/form.php';
});

// ===== MÜŞTERI GÖRÜŞMELERİ (Sayfa Bazlı Form) =====
$Router->add('GET', '/customer/{id}/meetings/new', function ($Parametreler) {
	if (!Page::can('meetings.create')) return;
	$MusteriId = (int)($Parametreler['id'] ?? 0);
	$GorusmeId = 0;
	require __DIR__ . '/../public/pages/meetings/form.php';
});

$Router->add('GET', '/customer/{id}/meetings/{meetingId}/edit', function ($Parametreler) {
	if (!Page::can('meetings.update')) return;
	$MusteriId = (int)($Parametreler['id'] ?? 0);
	$GorusmeId = (int)($Parametreler['meetingId'] ?? 0);
	require __DIR__ . '/../public/pages/meetings/form.php';
});

// ===== MÜŞTERI PROJELERİ (Sayfa Bazlı Form) =====
$Router->add('GET', '/customer/{id}/projects/new', function ($Parametreler) {
	if (!Page::can('projects.create')) return;
	$MusteriId = (int)($Parametreler['id'] ?? 0);
	$ProjeId = 0;
	require __DIR__ . '/../public/pages/projects/form.php';
});

$Router->add('GET', '/customer/{id}/projects/{projectId}/edit', function ($Parametreler) {
	if (!Page::can('projects.update')) return;
	$MusteriId = (int)($Parametreler['id'] ?? 0);
	$ProjeId = (int)($Parametreler['projectId'] ?? 0);
	require __DIR__ . '/../public/pages/projects/form.php';
});

// ===== MÜŞTERI TAKVİM (Sayfa Bazlı Form) =====
$Router->add('GET', '/customer/{id}/calendar/new', function ($Parametreler) {
	if (!Page::can('calendar.create')) return;
	$MusteriId = (int)($Parametreler['id'] ?? 0);
	$TakvimId = 0;
	require __DIR__ . '/../public/pages/calendar/form.php';
});

$Router->add('GET', '/customer/{id}/calendar/{calendarId}/edit', function ($Parametreler) {
	if (!Page::can('calendar.update')) return;
	$MusteriId = (int)($Parametreler['id'] ?? 0);
	$TakvimId = (int)($Parametreler['calendarId'] ?? 0);
	require __DIR__ . '/../public/pages/calendar/form.php';
});

// ===== MÜŞTERI DAMGA VERGİSİ (Sayfa Bazlı Form) =====
$Router->add('GET', '/customer/{id}/stamp-taxes/new', function ($Parametreler) {
	if (!Page::can('stamp_taxes.create')) return;
	$MusteriId = (int)($Parametreler['id'] ?? 0);
	$DamgaId = 0;
	require __DIR__ . '/../public/pages/stamp-taxes/form.php';
});

$Router->add('GET', '/customer/{id}/stamp-taxes/{stampTaxId}/edit', function ($Parametreler) {
	if (!Page::can('stamp_taxes.update')) return;
	$MusteriId = (int)($Parametreler['id'] ?? 0);
	$DamgaId = (int)($Parametreler['stampTaxId'] ?? 0);
	require __DIR__ . '/../public/pages/stamp-taxes/form.php';
});

// ===== MÜŞTERI TEMİNATLARI (Sayfa Bazlı Form) =====
$Router->add('GET', '/customer/{id}/guarantees/new', function ($Parametreler) {
	if (!Page::can('guarantees.create')) return;
	$MusteriId = (int)($Parametreler['id'] ?? 0);
	$TeminatId = 0;
	require __DIR__ . '/../public/pages/guarantees/form.php';
});

$Router->add('GET', '/customer/{id}/guarantees/{guaranteeId}/edit', function ($Parametreler) {
	if (!Page::can('guarantees.update')) return;
	$MusteriId = (int)($Parametreler['id'] ?? 0);
	$TeminatId = (int)($Parametreler['guaranteeId'] ?? 0);
	require __DIR__ . '/../public/pages/guarantees/form.php';
});

// ===== MÜŞTERI FATURALARI (Sayfa Bazlı Form) =====
$Router->add('GET', '/customer/{id}/invoices/new', function ($Parametreler) {
	if (!Page::can('invoices.create')) return;
	$MusteriId = (int)($Parametreler['id'] ?? 0);
	$FaturaId = 0;
	require __DIR__ . '/../public/pages/invoices/form.php';
});

$Router->add('GET', '/customer/{id}/invoices/{invoiceId}/edit', function ($Parametreler) {
	if (!Page::can('invoices.update')) return;
	$MusteriId = (int)($Parametreler['id'] ?? 0);
	$FaturaId = (int)($Parametreler['invoiceId'] ?? 0);
	require __DIR__ . '/../public/pages/invoices/form.php';
});

// ===== MÜŞTERI ÖDEMELERİ (Sayfa Bazlı Form) =====
$Router->add('GET', '/customer/{id}/payments/new', function ($Parametreler) {
	if (!Page::can('payments.create')) return;
	$MusteriId = (int)($Parametreler['id'] ?? 0);
	$OdemeId = 0;
	require __DIR__ . '/../public/pages/payments/form.php';
});

$Router->add('GET', '/customer/{id}/payments/{paymentId}/edit', function ($Parametreler) {
	if (!Page::can('payments.update')) return;
	$MusteriId = (int)($Parametreler['id'] ?? 0);
	$OdemeId = (int)($Parametreler['paymentId'] ?? 0);
	require __DIR__ . '/../public/pages/payments/form.php';
});

// ===== MÜŞTERI DOSYALARI (Sayfa Bazlı Form) =====
$Router->add('GET', '/customer/{id}/files/new', function ($Parametreler) {
	if (!Page::can('files.create')) return;
	$MusteriId = (int)($Parametreler['id'] ?? 0);
	$DosyaId = 0;
	require __DIR__ . '/../public/pages/files/form.php';
});

$Router->add('GET', '/customer/{id}/files/{fileId}/edit', function ($Parametreler) {
	if (!Page::can('files.update')) return;
	$MusteriId = (int)($Parametreler['id'] ?? 0);
	$DosyaId = (int)($Parametreler['fileId'] ?? 0);
	require __DIR__ . '/../public/pages/files/form.php';
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
