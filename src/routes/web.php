<?php

use App\Middleware\Page;
use App\Services\Authorization\AuthorizationService;

$PagesPath = PUBLIC_PATH . 'pages' . DIRECTORY_SEPARATOR;

$Router->add('GET', '/login', function () {
    require PUBLIC_PATH . 'login.php';
});

$Router->add('GET', '/', function () use ($PagesPath) {
    if (!Page::can('dashboard.read')) return;
    require $PagesPath . 'dashboard.php';
});

$Router->add('GET', '/dashboard', function () use ($PagesPath) {
    if (!Page::can('dashboard.read')) return;
    require $PagesPath . 'dashboard.php';
});

$Router->add('GET', '/customer/new', function ($Parametreler) use ($PagesPath) {
    if (!Page::can('customers.create')) return;
    $MusteriId = 0;
    require $PagesPath . 'customers' . DIRECTORY_SEPARATOR . 'form.php';
});

$Router->add('GET', '/customer/{id}/edit', function ($Parametreler) use ($PagesPath) {
    if (!Page::can('customers.update')) return;
    $MusteriId = (int)($Parametreler['id'] ?? 0);
    if (!Page::requireCustomer($MusteriId)) return;
    require $PagesPath . 'customers' . DIRECTORY_SEPARATOR . 'form.php';
});

$Router->add('GET', '/customer/{id}', function ($Parametreler) use ($PagesPath) {
    if (!Page::can('customers.read')) return;
    $MusteriId = (int)($Parametreler['id'] ?? 0);
    if (!Page::requireCustomer($MusteriId)) return;
    require $PagesPath . 'customer-detail.php';
});

$Router->add('GET', '/customer/{id}/offers/new', function ($Parametreler) use ($PagesPath) {
    if (!Page::can('offers.create')) return;
    $MusteriId = (int)($Parametreler['id'] ?? 0);
    if (!Page::requireCustomer($MusteriId)) return;
    $TeklifId = 0;
    require $PagesPath . 'offers' . DIRECTORY_SEPARATOR . 'form.php';
});

$Router->add('GET', '/customer/{id}/offers/{offerId}/edit', function ($Parametreler) use ($PagesPath) {
    if (!Page::can('offers.update')) return;
    $MusteriId = (int)($Parametreler['id'] ?? 0);
    if (!Page::requireCustomer($MusteriId)) return;
    $TeklifId = (int)($Parametreler['offerId'] ?? 0);
    if (!Page::requireRecord('OfferRepository', $TeklifId, 'Teklif')) return;
    require $PagesPath . 'offers' . DIRECTORY_SEPARATOR . 'form.php';
});

$Router->add('GET', '/customer/{id}/contracts/new', function ($Parametreler) use ($PagesPath) {
    if (!Page::can('contracts.create')) return;
    $MusteriId = (int)($Parametreler['id'] ?? 0);
    if (!Page::requireCustomer($MusteriId)) return;
    $SozlesmeId = 0;
    require $PagesPath . 'contracts' . DIRECTORY_SEPARATOR . 'form.php';
});

$Router->add('GET', '/customer/{id}/contracts/{contractId}/edit', function ($Parametreler) use ($PagesPath) {
    if (!Page::can('contracts.update')) return;
    $MusteriId = (int)($Parametreler['id'] ?? 0);
    if (!Page::requireCustomer($MusteriId)) return;
    $SozlesmeId = (int)($Parametreler['contractId'] ?? 0);
    if (!Page::requireRecord('ContractRepository', $SozlesmeId, 'Sözleşme')) return;
    require $PagesPath . 'contracts' . DIRECTORY_SEPARATOR . 'form.php';
});

$Router->add('GET', '/customer/{id}/contacts/new', function ($Parametreler) use ($PagesPath) {
    if (!Page::can('contacts.create')) return;
    $MusteriId = (int)($Parametreler['id'] ?? 0);
    if (!Page::requireCustomer($MusteriId)) return;
    $KisiId = 0;
    require $PagesPath . 'contacts' . DIRECTORY_SEPARATOR . 'form.php';
});

$Router->add('GET', '/customer/{id}/contacts/{contactId}/edit', function ($Parametreler) use ($PagesPath) {
    if (!Page::can('contacts.update')) return;
    $MusteriId = (int)($Parametreler['id'] ?? 0);
    if (!Page::requireCustomer($MusteriId)) return;
    $KisiId = (int)($Parametreler['contactId'] ?? 0);
    if (!Page::requireRecord('ContactRepository', $KisiId, 'Kişi')) return;
    require $PagesPath . 'contacts' . DIRECTORY_SEPARATOR . 'form.php';
});

$Router->add('GET', '/customer/{id}/meetings/new', function ($Parametreler) use ($PagesPath) {
    if (!Page::can('meetings.create')) return;
    $MusteriId = (int)($Parametreler['id'] ?? 0);
    if (!Page::requireCustomer($MusteriId)) return;
    $GorusmeId = 0;
    require $PagesPath . 'meetings' . DIRECTORY_SEPARATOR . 'form.php';
});

$Router->add('GET', '/customer/{id}/meetings/{meetingId}/edit', function ($Parametreler) use ($PagesPath) {
    if (!Page::can('meetings.update')) return;
    $MusteriId = (int)($Parametreler['id'] ?? 0);
    if (!Page::requireCustomer($MusteriId)) return;
    $GorusmeId = (int)($Parametreler['meetingId'] ?? 0);
    if (!Page::requireRecord('MeetingRepository', $GorusmeId, 'Görüşme')) return;
    require $PagesPath . 'meetings' . DIRECTORY_SEPARATOR . 'form.php';
});

$Router->add('GET', '/customer/{id}/projects/new', function ($Parametreler) use ($PagesPath) {
    if (!Page::can('projects.create')) return;
    $MusteriId = (int)($Parametreler['id'] ?? 0);
    if (!Page::requireCustomer($MusteriId)) return;
    $ProjeId = 0;
    require $PagesPath . 'projects' . DIRECTORY_SEPARATOR . 'form.php';
});

$Router->add('GET', '/customer/{id}/projects/{projectId}/edit', function ($Parametreler) use ($PagesPath) {
    if (!Page::can('projects.update')) return;
    $MusteriId = (int)($Parametreler['id'] ?? 0);
    if (!Page::requireCustomer($MusteriId)) return;
    $ProjeId = (int)($Parametreler['projectId'] ?? 0);
    if (!Page::requireRecord('ProjectRepository', $ProjeId, 'Proje')) return;
    require $PagesPath . 'projects' . DIRECTORY_SEPARATOR . 'form.php';
});

$Router->add('GET', '/customer/{id}/calendar/new', function ($Parametreler) use ($PagesPath) {
    if (!Page::can('calendar.create')) return;
    $MusteriId = (int)($Parametreler['id'] ?? 0);
    if (!Page::requireCustomer($MusteriId)) return;
    $TakvimId = 0;
    require $PagesPath . 'calendar' . DIRECTORY_SEPARATOR . 'form.php';
});

$Router->add('GET', '/customer/{id}/calendar/{calendarId}/edit', function ($Parametreler) use ($PagesPath) {
    if (!Page::can('calendar.update')) return;
    $MusteriId = (int)($Parametreler['id'] ?? 0);
    if (!Page::requireCustomer($MusteriId)) return;
    $TakvimId = (int)($Parametreler['calendarId'] ?? 0);
    if (!Page::requireRecord('CalendarRepository', $TakvimId, 'Takvim')) return;
    require $PagesPath . 'calendar' . DIRECTORY_SEPARATOR . 'form.php';
});

$Router->add('GET', '/customer/{id}/stamp-taxes/new', function ($Parametreler) use ($PagesPath) {
    if (!Page::can('stamp_taxes.create')) return;
    $MusteriId = (int)($Parametreler['id'] ?? 0);
    if (!Page::requireCustomer($MusteriId)) return;
    $DamgaId = 0;
    require $PagesPath . 'stamp-taxes' . DIRECTORY_SEPARATOR . 'form.php';
});

$Router->add('GET', '/customer/{id}/stamp-taxes/{stampTaxId}/edit', function ($Parametreler) use ($PagesPath) {
    if (!Page::can('stamp_taxes.update')) return;
    $MusteriId = (int)($Parametreler['id'] ?? 0);
    if (!Page::requireCustomer($MusteriId)) return;
    $DamgaId = (int)($Parametreler['stampTaxId'] ?? 0);
    if (!Page::requireRecord('StampTaxRepository', $DamgaId, 'Damga Vergisi')) return;
    require $PagesPath . 'stamp-taxes' . DIRECTORY_SEPARATOR . 'form.php';
});

$Router->add('GET', '/customer/{id}/guarantees/new', function ($Parametreler) use ($PagesPath) {
    if (!Page::can('guarantees.create')) return;
    $MusteriId = (int)($Parametreler['id'] ?? 0);
    if (!Page::requireCustomer($MusteriId)) return;
    $TeminatId = 0;
    require $PagesPath . 'guarantees' . DIRECTORY_SEPARATOR . 'form.php';
});

$Router->add('GET', '/customer/{id}/guarantees/{guaranteeId}/edit', function ($Parametreler) use ($PagesPath) {
    if (!Page::can('guarantees.update')) return;
    $MusteriId = (int)($Parametreler['id'] ?? 0);
    if (!Page::requireCustomer($MusteriId)) return;
    $TeminatId = (int)($Parametreler['guaranteeId'] ?? 0);
    if (!Page::requireRecord('GuaranteeRepository', $TeminatId, 'Teminat')) return;
    require $PagesPath . 'guarantees' . DIRECTORY_SEPARATOR . 'form.php';
});

$Router->add('GET', '/customer/{id}/invoices/new', function ($Parametreler) use ($PagesPath) {
    if (!Page::can('invoices.create')) return;
    $MusteriId = (int)($Parametreler['id'] ?? 0);
    if (!Page::requireCustomer($MusteriId)) return;
    $FaturaId = 0;
    require $PagesPath . 'invoices' . DIRECTORY_SEPARATOR . 'form.php';
});

$Router->add('GET', '/customer/{id}/invoices/{invoiceId}/edit', function ($Parametreler) use ($PagesPath) {
    if (!Page::can('invoices.update')) return;
    $MusteriId = (int)($Parametreler['id'] ?? 0);
    if (!Page::requireCustomer($MusteriId)) return;
    $FaturaId = (int)($Parametreler['invoiceId'] ?? 0);
    if (!Page::requireRecord('InvoiceRepository', $FaturaId, 'Fatura')) return;
    require $PagesPath . 'invoices' . DIRECTORY_SEPARATOR . 'form.php';
});

$Router->add('GET', '/customer/{id}/payments/new', function ($Parametreler) use ($PagesPath) {
    if (!Page::can('payments.create')) return;
    $MusteriId = (int)($Parametreler['id'] ?? 0);
    if (!Page::requireCustomer($MusteriId)) return;
    $OdemeId = 0;
    require $PagesPath . 'payments' . DIRECTORY_SEPARATOR . 'form.php';
});

$Router->add('GET', '/customer/{id}/payments/{paymentId}/edit', function ($Parametreler) use ($PagesPath) {
    if (!Page::can('payments.update')) return;
    $MusteriId = (int)($Parametreler['id'] ?? 0);
    if (!Page::requireCustomer($MusteriId)) return;
    $OdemeId = (int)($Parametreler['paymentId'] ?? 0);
    if (!Page::requireRecord('PaymentRepository', $OdemeId, 'Ödeme')) return;
    require $PagesPath . 'payments' . DIRECTORY_SEPARATOR . 'form.php';
});

$Router->add('GET', '/customer/{id}/files/new', function ($Parametreler) use ($PagesPath) {
    if (!Page::can('files.create')) return;
    $MusteriId = (int)($Parametreler['id'] ?? 0);
    if (!Page::requireCustomer($MusteriId)) return;
    $DosyaId = 0;
    require $PagesPath . 'files' . DIRECTORY_SEPARATOR . 'form.php';
});

$Router->add('GET', '/customer/{id}/files/{fileId}/edit', function ($Parametreler) use ($PagesPath) {
    if (!Page::can('files.update')) return;
    $MusteriId = (int)($Parametreler['id'] ?? 0);
    if (!Page::requireCustomer($MusteriId)) return;
    $DosyaId = (int)($Parametreler['fileId'] ?? 0);
    if (!Page::requireRecord('FileRepository', $DosyaId, 'Dosya')) return;
    require $PagesPath . 'files' . DIRECTORY_SEPARATOR . 'form.php';
});

$Router->add('GET', '/invoices', function () use ($PagesPath) {
    if (!Page::can('invoices.read')) return;
    require $PagesPath . 'invoices.php';
});

$Router->add('GET', '/payments', function () use ($PagesPath) {
    if (!Page::can('payments.read')) return;
    require $PagesPath . 'payments.php';
});

$Router->add('GET', '/projects', function () use ($PagesPath) {
    if (!Page::can('projects.read')) return;
    require $PagesPath . 'projects.php';
});

$Router->add('GET', '/offers', function () use ($PagesPath) {
    if (!Page::can('offers.read')) return;
    require $PagesPath . 'offers.php';
});

$Router->add('GET', '/contracts', function () use ($PagesPath) {
    if (!Page::can('contracts.read')) return;
    require $PagesPath . 'contracts.php';
});

$Router->add('GET', '/guarantees', function () use ($PagesPath) {
    if (!Page::can('guarantees.read')) return;
    require $PagesPath . 'guarantees.php';
});

$Router->add('GET', '/users', function () use ($PagesPath) {
    if (!Page::can('users.read')) return;
    require $PagesPath . 'users.php';
});

$Router->add('GET', '/users/new', function () use ($PagesPath) {
    if (!Page::can('users.create')) return;
    $KullaniciId = 0;
    require $PagesPath . 'users' . DIRECTORY_SEPARATOR . 'form.php';
});

$Router->add('GET', '/users/{id}/edit', function ($Parametreler) use ($PagesPath) {
    if (!Page::can('users.update')) return;
    $KullaniciId = (int)($Parametreler['id'] ?? 0);
    if (!Page::requireRecord('UserRepository', $KullaniciId, 'Kullanıcı')) return;
    require $PagesPath . 'users' . DIRECTORY_SEPARATOR . 'form.php';
});

$Router->add('GET', '/roles', function () use ($PagesPath) {
    if (!Page::can('roles.read')) return;
    require $PagesPath . 'roles.php';
});

$Router->add('GET', '/roles/new', function () use ($PagesPath) {
    if (!Page::can('roles.create')) return;
    $RolId = 0;
    require $PagesPath . 'roles' . DIRECTORY_SEPARATOR . 'form.php';
});

$Router->add('GET', '/roles/{id}/edit', function ($Parametreler) use ($PagesPath) {
    if (!Page::can('roles.update')) return;
    $RolId = (int)($Parametreler['id'] ?? 0);
    if (!Page::requireRecord('RoleRepository', $RolId, 'Rol')) return;
    $UserId = $GLOBALS['AuthUserId'] ?? null;
    if ($UserId) {
        $AuthService = AuthorizationService::getInstance();
        if (!$AuthService->rolDuzenleyebilirMi($UserId, $RolId)) {
            Page::forbid('roles.update');
            return;
        }
    }
    require $PagesPath . 'roles' . DIRECTORY_SEPARATOR . 'form.php';
});

$Router->add('GET', '/logs', function () use ($PagesPath) {
    if (!Page::can('logs.read')) return;
    require $PagesPath . 'logs.php';
});

$Router->add('GET', '/my-account', function () use ($PagesPath) {

    if (!Page::auth()) return;
    require $PagesPath . 'my-account.php';
});

$Router->add('GET', '/alarms', function () use ($PagesPath) {
    if (!Page::can('alarms.read')) return;
    require $PagesPath . 'alarms.php';
});

$Router->add('GET', '/parameters', function () use ($PagesPath) {
    if (!Page::can('parameters.read')) return;
    require $PagesPath . 'parameters.php';
});
