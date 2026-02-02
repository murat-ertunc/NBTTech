<?php








use App\Core\Response;
use App\Middleware\Auth;
use App\Middleware\Permission;
use App\Controllers\InstallController;












function guard(string $PermissionKodu, callable $Handler): callable
{
    return function ($Params = []) use ($PermissionKodu, $Handler) {
        if (!Auth::yetkilendirmeGerekli()) return;
        if ($PermissionKodu && !Permission::izinGerekli($PermissionKodu)) return;
        $Handler($Params);
    };
}
















function resource(string $Kaynak, string $Controller, array $Ayarlar = []): void
{
    global $Router;
    
    $Only = $Ayarlar['only'] ?? null;
    $Except = $Ayarlar['except'] ?? [];
    
    $Aksiyonlar = [
        'index'  => ['GET', "/api/{$Kaynak}", 'read'],
        'show'   => ['GET', "/api/{$Kaynak}/{id}", 'read'],
        'store'  => ['POST', "/api/{$Kaynak}", 'create'],
        'update' => ['POST', "/api/{$Kaynak}/{id}/update", 'update'],
        'delete' => ['POST', "/api/{$Kaynak}/{id}/delete", 'delete'],
    ];
    
    foreach ($Aksiyonlar as $Metod => [$HttpMetod, $Yol, $Aksiyon]) {
        
        if ($Only !== null && !in_array($Metod, $Only)) continue;
        
        if (in_array($Metod, $Except)) continue;
        
        $PermKodu = "{$Kaynak}.{$Aksiyon}";
        $Router->add($HttpMetod, $Yol, guard($PermKodu, fn($P) => $Controller::$Metod($P)));
    }
    
    
    if (!empty($Ayarlar['extra'])) {
        foreach ($Ayarlar['extra'] as $Extra) {
            [$HttpMetod, $Yol, $Perm, $ControllerMetod] = $Extra;
            
            $HttpMetod = in_array($HttpMetod, ['PUT', 'DELETE', 'PATCH']) ? 'POST' : $HttpMetod;
            $Router->add($HttpMetod, $Yol, guard($Perm, fn($P) => $Controller::$ControllerMetod($P)));
        }
    }
}




function resourceReadOnly(string $Kaynak, string $Controller): void
{
    resource($Kaynak, $Controller, ['only' => ['index', 'show']]);
}






function resourceInstance(string $Kaynak, string $Controller, array $Ayarlar = []): void
{
    global $Router;
    
    $Only = $Ayarlar['only'] ?? null;
    $Except = $Ayarlar['except'] ?? [];
    
    $Aksiyonlar = [
        'index'  => ['GET', "/api/{$Kaynak}", 'read'],
        'show'   => ['GET', "/api/{$Kaynak}/{id}", 'read'],
        'store'  => ['POST', "/api/{$Kaynak}", 'create'],
        'update' => ['POST', "/api/{$Kaynak}/{id}/update", 'update'],
        'delete' => ['POST', "/api/{$Kaynak}/{id}/delete", 'delete'],
    ];
    
    foreach ($Aksiyonlar as $Metod => [$HttpMetod, $Yol, $Aksiyon]) {
        if ($Only !== null && !in_array($Metod, $Only)) continue;
        if (in_array($Metod, $Except)) continue;
        
        $PermKodu = "{$Kaynak}.{$Aksiyon}";
        $Router->add($HttpMetod, $Yol, guard($PermKodu, fn($P) => (new $Controller())->$Metod($P)));
    }
}





$Router->add('GET', '/__internal__/install', fn() => InstallController::run());
$Router->add('POST', '/__internal__/install', fn() => InstallController::run());
$Router->add('GET', '/__internal__/check-columns', fn() => InstallController::checkColumns());

$Router->add('GET', '/health', fn() => Response::json([
    'status' => 'ok',
    'app' => config('app.name'),
    'time' => date('c'),
]));

$Router->add('POST', '/api/login', fn() => App\Controllers\AuthController::login());
$Router->add('POST', '/api/logout', fn() => App\Controllers\AuthController::logout());
$Router->add('POST', '/api/refresh', fn() => App\Controllers\AuthController::refresh());





$Router->add('GET', '/api/auth/permissions', guard('', fn() => App\Controllers\RoleController::myPermissions()));
$Router->add('GET', '/api/roles/assignable', guard('', fn() => App\Controllers\RoleController::assignableRoles()));
$Router->add('POST', '/api/users/change-password', guard('', fn() => App\Controllers\UserController::changePassword()));
$Router->add('GET', '/api/parameters/currencies', guard('', fn() => App\Controllers\ParameterController::currencies()));
$Router->add('GET', '/api/parameters/default-currency', guard('', fn() => App\Controllers\ParameterController::defaultCurrency()));
$Router->add('GET', '/api/parameters/statuses', guard('', fn() => App\Controllers\ParameterController::statuses()));
$Router->add('GET', '/api/parameters/settings', guard('', fn() => App\Controllers\ParameterController::settings()));





resource('roles', App\Controllers\RoleController::class, [
    'extra' => [
        ['GET', '/api/roles/{id}/permissions', 'roles.read', 'getPermissions'],
        ['POST', '/api/roles/{id}/permissions', 'roles.update', 'assignPermissions'],
    ]
]);
$Router->add('GET', '/api/permissions', guard('roles.read', fn() => App\Controllers\RoleController::allPermissions()));





resource('users', App\Controllers\UserController::class, [
    'extra' => [
        ['GET', '/api/users/{id}/roles', 'users.read', 'getRoles'],
        ['POST', '/api/users/{id}/roles', 'users.update', 'assignRoles'],
        ['POST', '/api/users/{id}/block', 'users.update', 'block'],
    ]
]);





resource('customers', App\Controllers\CustomerController::class, [
    'extra' => [
        ['GET', '/api/customers/{id}/cari-ozet', 'customers.read', 'cariOzet'],
    ]
]);

resource('invoices', App\Controllers\InvoiceController::class);
resource('payments', App\Controllers\PaymentController::class, [
    'extra' => [
        ['GET', '/api/payments/{id}/download', 'payments.read', 'download'],
    ]
]);
resource('projects', App\Controllers\ProjectController::class);

resource('offers', App\Controllers\OfferController::class, [
    'extra' => [
        ['GET', '/api/offers/{id}/download', 'offers.read', 'download'],
    ]
]);

resource('contracts', App\Controllers\ContractController::class, [
    'extra' => [
        ['GET', '/api/contracts/{id}/download', 'contracts.read', 'download'],
    ]
]);

resource('meetings', App\Controllers\MeetingController::class);
resource('contacts', App\Controllers\ContactController::class);

resource('guarantees', App\Controllers\GuaranteeController::class, [
    'extra' => [
        ['GET', '/api/guarantees/{id}/download', 'guarantees.read', 'download'],
    ]
]);


$Router->add('GET', '/api/stamp-taxes', guard('stamp_taxes.read', fn($P) => App\Controllers\StampTaxController::index($P)));
$Router->add('GET', '/api/stamp-taxes/{id}', guard('stamp_taxes.read', fn($P) => App\Controllers\StampTaxController::show($P)));
$Router->add('POST', '/api/stamp-taxes', guard('stamp_taxes.create', fn($P) => App\Controllers\StampTaxController::store($P)));
$Router->add('POST', '/api/stamp-taxes/{id}/update', guard('stamp_taxes.update', fn($P) => App\Controllers\StampTaxController::update($P)));
$Router->add('POST', '/api/stamp-taxes/{id}/delete', guard('stamp_taxes.delete', fn($P) => App\Controllers\StampTaxController::delete($P)));
$Router->add('GET', '/api/stamp-taxes/{id}/download', guard('stamp_taxes.read', fn($P) => App\Controllers\StampTaxController::download($P)));

resource('files', App\Controllers\FileController::class, [
    'extra' => [
        ['GET', '/api/files/{id}/download', 'files.read', 'download'],
    ]
]);





resourceInstance('calendar', App\Controllers\CalendarController::class, ['only' => ['index']]);
$Router->add('GET', '/api/calendar/day/{date}', guard('calendar.read', fn($P) => (new App\Controllers\CalendarController())->day($P['date'])));


$Router->add('GET', '/api/takvim', guard('calendar.read', fn($P) => App\Controllers\TakvimController::index($P)));
$Router->add('GET', '/api/takvim/{id}', guard('calendar.read', fn($P) => App\Controllers\TakvimController::show($P)));
$Router->add('POST', '/api/takvim', guard('calendar.create', fn($P) => App\Controllers\TakvimController::store($P)));
$Router->add('POST', '/api/takvim/{id}/update', guard('calendar.update', fn($P) => App\Controllers\TakvimController::update($P)));
$Router->add('POST', '/api/takvim/{id}/delete', guard('calendar.delete', fn($P) => App\Controllers\TakvimController::delete($P)));






$Router->add('GET', '/api/dashboard', guard('dashboard.read', fn() => App\Controllers\DashboardController::index()));


$Router->add('GET', '/api/logs', guard('logs.read', fn() => App\Controllers\LogController::index()));


$Router->add('GET', '/api/alarms', guard('alarms.read', fn() => (new App\Controllers\AlarmController())->index()));





resource('parameters', App\Controllers\ParameterController::class, [
    'extra' => [
        ['POST', '/api/parameters/bulk', 'parameters.update', 'bulkUpdate'],
        ['GET', '/api/parameters/reminder-settings', 'parameters.read', 'reminderSettings'],
        ['POST', '/api/parameters/reminder-settings', 'parameters.update', 'updateReminderSettings'],
    ]
]);






$Router->add('GET', '/api/cities', guard('parameters.read', fn($P) => App\Controllers\CityController::index()));
$Router->add('GET', '/api/cities/{id}', guard('parameters.read', fn($P) => App\Controllers\CityController::show($P)));
$Router->add('POST', '/api/cities', guard('parameters.create', fn($P) => App\Controllers\CityController::store()));
$Router->add('POST', '/api/cities/{id}/update', guard('parameters.update', fn($P) => App\Controllers\CityController::update($P)));
$Router->add('POST', '/api/cities/{id}/delete', guard('parameters.delete', fn($P) => App\Controllers\CityController::delete($P)));


$Router->add('GET', '/api/districts', guard('parameters.read', fn($P) => App\Controllers\DistrictController::index()));
$Router->add('GET', '/api/districts/{id}', guard('parameters.read', fn($P) => App\Controllers\DistrictController::show($P)));
$Router->add('POST', '/api/districts', guard('parameters.create', fn($P) => App\Controllers\DistrictController::store()));
$Router->add('POST', '/api/districts/{id}/update', guard('parameters.update', fn($P) => App\Controllers\DistrictController::update($P)));
$Router->add('POST', '/api/districts/{id}/delete', guard('parameters.delete', fn($P) => App\Controllers\DistrictController::delete($P)));
