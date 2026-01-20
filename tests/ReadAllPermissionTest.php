<?php

/**
 * Read All Permission ve Multi-Role Test
 * 
 * Bu test dosyasi:
 * 1. customers.read_all ve users.read_all permission kontrollerini test eder
 * 2. Multi-role kullanici listesinin dogru calistigini test eder
 * 3. Eski rol kontrollerinin kaldirildigini dogrular
 * 
 * Calistirma: php tests/ReadAllPermissionTest.php
 */

// Bootstrap
require_once __DIR__ . '/../app/Core/bootstrap.php';

use App\Services\Authorization\AuthorizationService;
use App\Repositories\UserRepository;
use App\Repositories\CustomerRepository;

/**
 * Basit Test Framework
 */
class TestRunner
{
    private int $Passed = 0;
    private int $Failed = 0;
    private array $Hatalar = [];
    
    public function assert(bool $Condition, string $Mesaj): void
    {
        if ($Condition) {
            $this->Passed++;
            echo "\033[32m✓\033[0m {$Mesaj}\n";
        } else {
            $this->Failed++;
            $this->Hatalar[] = $Mesaj;
            echo "\033[31m✗\033[0m {$Mesaj}\n";
        }
    }
    
    public function assertEquals($Expected, $Actual, string $Mesaj): void
    {
        $this->assert($Expected === $Actual, $Mesaj . " (Expected: " . json_encode($Expected) . ", Actual: " . json_encode($Actual) . ")");
    }
    
    public function assertTrue(bool $Value, string $Mesaj): void
    {
        $this->assert($Value === true, $Mesaj);
    }
    
    public function assertFalse(bool $Value, string $Mesaj): void
    {
        $this->assert($Value === false, $Mesaj);
    }
    
    public function assertNotEmpty($Value, string $Mesaj): void
    {
        $this->assert(!empty($Value), $Mesaj);
    }
    
    public function assertArrayHasKey(string $Key, array $Array, string $Mesaj): void
    {
        $this->assert(array_key_exists($Key, $Array), $Mesaj);
    }
    
    public function summary(): void
    {
        echo "\n" . str_repeat('=', 60) . "\n";
        echo "Test Sonuclari: \033[32m{$this->Passed} Passed\033[0m, \033[31m{$this->Failed} Failed\033[0m\n";
        
        if (!empty($this->Hatalar)) {
            echo "\nBasarisiz Testler:\n";
            foreach ($this->Hatalar as $Hata) {
                echo "  - {$Hata}\n";
            }
        }
        
        echo str_repeat('=', 60) . "\n";
    }
    
    public function isSuccess(): bool
    {
        return $this->Failed === 0;
    }
}

// =============================================
// TESTLER
// =============================================

echo "\n\033[34m=== Read All Permission & Multi-Role Tests ===\033[0m\n\n";

$Test = new TestRunner();
$AuthService = AuthorizationService::getInstance();

// ---------------------------------------------
// 1. TÜMÜNÜ GÖRME PERMISSION METODLARI TESTI
// ---------------------------------------------
echo "\033[33m[1. tumunuGorebilirMi() Method Tests]\033[0m\n";

// users.read_all ve customers.read_all permission'ina sahip bir kullanici bul
$Db = \App\Core\Database::getInstance()->getConnection();

$UserWithUsersReadAll = $Db->query("
    SELECT TOP 1 ur.UserId
    FROM tnm_user_rol ur
    INNER JOIN tnm_rol_permission rp ON rp.RolId = ur.RolId AND rp.Sil = 0
    INNER JOIN tnm_permission p ON p.Id = rp.PermissionId AND p.Sil = 0 AND p.Aktif = 1
    WHERE ur.Sil = 0 AND p.PermissionKodu = 'users.read_all'
")
->fetch(\PDO::FETCH_ASSOC);

$UserWithCustomersReadAll = $Db->query("
    SELECT TOP 1 ur.UserId
    FROM tnm_user_rol ur
    INNER JOIN tnm_rol_permission rp ON rp.RolId = ur.RolId AND rp.Sil = 0
    INNER JOIN tnm_permission p ON p.Id = rp.PermissionId AND p.Sil = 0 AND p.Aktif = 1
    WHERE ur.Sil = 0 AND p.PermissionKodu = 'customers.read_all'
")
->fetch(\PDO::FETCH_ASSOC);

if ($UserWithUsersReadAll) {
    $UserId = (int)$UserWithUsersReadAll['UserId'];
    $Test->assertTrue(
        $AuthService->tumunuGorebilirMi($UserId, 'users'),
        "UserId={$UserId} users.read_all yetkisine sahip olmali"
    );
} else {
    echo "  \033[33m!\033[0m users.read_all yetkisine sahip kullanici bulunamadi - seed kontrol edin\n";
}

if ($UserWithCustomersReadAll) {
    $UserId = (int)$UserWithCustomersReadAll['UserId'];
    $Test->assertTrue(
        $AuthService->tumunuGorebilirMi($UserId, 'customers'),
        "UserId={$UserId} customers.read_all yetkisine sahip olmali"
    );
} else {
    echo "  \033[33m!\033[0m customers.read_all yetkisine sahip kullanici bulunamadi - seed kontrol edin\n";
}

// tumunuDuzenleyebilirMi metodu kontrolu (permission bazli)
if ($UserWithCustomersReadAll) {
    $UserId = (int)$UserWithCustomersReadAll['UserId'];
    $Test->assertTrue(
        $AuthService->tumunuDuzenleyebilirMi($UserId, 'customers'),
        "UserId={$UserId} tumunuDuzenleyebilirMi() true donmeli"
    );
}

echo "\n";

// ---------------------------------------------
// 2. MULTI-ROLE USER REPOSITORY TESTI
// ---------------------------------------------
echo "\033[33m[2. Multi-Role User Repository Tests]\033[0m\n";

$UserRepo = new UserRepository();

// Tum kullanicilari getir
$Kullanicilar = $UserRepo->tumKullanicilar();

if (!empty($Kullanicilar)) {
    $IlkKullanici = $Kullanicilar[0];
    
    // Roller dizisi olmali
    $Test->assertArrayHasKey('Roller', $IlkKullanici, 'Kullanici verisinde Roller alani olmali');
    
    // RollerStr stringi olmali
    $Test->assertArrayHasKey('RollerStr', $IlkKullanici, 'Kullanici verisinde RollerStr alani olmali');
    
    // Roller dizi olmali
    $Test->assertTrue(
        is_array($IlkKullanici['Roller']),
        'Roller alani dizi olmali'
    );
    
    echo "  Info: Ilk kullanici rolleri: " . ($IlkKullanici['RollerStr'] ?: '(bos)') . "\n";
} else {
    echo "  \033[33m!\033[0m Kullanici bulunamadi, multi-role testi atlanıyor\n";
}

// Paginated liste testi
$PaginatedSonuc = $UserRepo->tumKullanicilarPaginated(1, 10);
$Test->assertArrayHasKey('data', $PaginatedSonuc, 'Paginated sonucta data olmali');
$Test->assertArrayHasKey('pagination', $PaginatedSonuc, 'Paginated sonucta pagination olmali');

if (!empty($PaginatedSonuc['data'])) {
    $IlkPaginatedKullanici = $PaginatedSonuc['data'][0];
    $Test->assertArrayHasKey('Roller', $IlkPaginatedKullanici, 'Paginated kullanicida Roller olmali');
    $Test->assertArrayHasKey('RollerStr', $IlkPaginatedKullanici, 'Paginated kullanicida RollerStr olmali');
}

echo "\n";

// ---------------------------------------------
// 3. ESKI ROL KONTROLU KALINTI TESTI (GREP)
// ---------------------------------------------
echo "\033[33m[3. Eski Rol Kontrolu Kalinti Testi]\033[0m\n";

// CustomerController'da $Rol === 'superadmin' olmamalı
$CustomerControllerPath = __DIR__ . '/../app/Controllers/CustomerController.php';
$CustomerControllerContent = file_get_contents($CustomerControllerPath);

$EskiRolKontrolSayisi = substr_count($CustomerControllerContent, "\$Rol === 'superadmin'");
$Test->assertEquals(
    0, 
    $EskiRolKontrolSayisi,
    "CustomerController'da eski rol kontrolu (\$Rol === 'superadmin') kalmamali"
);

// Context::rol() kullanimi olmamali (bu metod artik gerekli degil)
$ContextRolSayisi = substr_count($CustomerControllerContent, 'Context::rol()');
$Test->assertEquals(
    0,
    $ContextRolSayisi,
    "CustomerController'da Context::rol() kullanimi kalmamali"
);

// AuthorizationService kullanimi olmali
$AuthServiceKullanimi = strpos($CustomerControllerContent, 'AuthorizationService') !== false;
$Test->assertTrue(
    $AuthServiceKullanimi,
    "CustomerController AuthorizationService kullaniyor olmali"
);

echo "\n";

// ---------------------------------------------
// 4. PERMISSION MIDDLEWARE SUPERADMIN BYPASS YOK
// ---------------------------------------------
echo "\033[33m[4. Permission Middleware Superadmin Bypass Yok Testi]\033[0m\n";

$PermissionMiddlewarePath = __DIR__ . '/../app/Middleware/Permission.php';
$PermissionMiddlewareContent = file_get_contents($PermissionMiddlewarePath);

$Test->assertTrue(
    strpos($PermissionMiddlewareContent, 'superadminMi') === false,
    'Permission middleware superadmin bypass icermemeli'
);

echo "\n";

// ---------------------------------------------
// 5. SCOPE FILTRE TESTI (UserRepository)
// ---------------------------------------------
echo "\033[33m[5. Scope Filtre Tests]\033[0m\n";

// ekleyen_user_id filtresi calisiyormu
$ScopedSonuc = $UserRepo->tumKullanicilarPaginated(1, 10, ['ekleyen_user_id' => 1]);
$Test->assertArrayHasKey('data', $ScopedSonuc, 'Scoped paginated sonucta data olmali');

// Pagination bozulmamali
$Test->assertArrayHasKey('page', $ScopedSonuc['pagination'], 'Pagination page olmali');
$Test->assertArrayHasKey('total', $ScopedSonuc['pagination'], 'Pagination total olmali');

echo "  Info: Scoped sonuc toplam kayit: " . $ScopedSonuc['pagination']['total'] . "\n";

echo "\n";

// ---------------------------------------------
// 6. ROLE MIDDLEWARE DEPRECATED TESTI
// ---------------------------------------------
echo "\033[33m[6. Role Middleware Deprecated Tests]\033[0m\n";

$RoleMiddlewarePath = __DIR__ . '/../app/Middleware/Role.php';
$RoleMiddlewareContent = file_get_contents($RoleMiddlewarePath);

// @deprecated isaretli olmali
$DeprecatedBulundu = strpos($RoleMiddlewareContent, '@deprecated') !== false;
$Test->assertTrue(
    $DeprecatedBulundu,
    'Role middleware @deprecated isaretli olmali'
);

// trigger_error ile uyari verilmeli
$TriggerErrorBulundu = strpos($RoleMiddlewareContent, 'trigger_error') !== false;
$Test->assertTrue(
    $TriggerErrorBulundu,
    'Role::rolGerekli() trigger_error ile uyari vermeli'
);

echo "\n";

// ---------------------------------------------
// 7. PARAMETERCONTROLLER ROL KALINTI TESTI
// ---------------------------------------------
echo "\033[33m[7. ParameterController Rol Kalinti Tests]\033[0m\n";

$ParameterControllerPath = __DIR__ . '/../app/Controllers/ParameterController.php';
$ParameterControllerContent = file_get_contents($ParameterControllerPath);

// Context::rol() kullanimi olmamali
$ContextRolSayisi = substr_count($ParameterControllerContent, 'Context::rol()');
$Test->assertEquals(
    0,
    $ContextRolSayisi,
    "ParameterController'da Context::rol() kullanimi kalmamali"
);

// $Rol === 'superadmin' olmamali
$EskiRolKontrolSayisi = substr_count($ParameterControllerContent, "\$Rol === 'superadmin'");
$Test->assertEquals(
    0, 
    $EskiRolKontrolSayisi,
    "ParameterController'da eski rol kontrolu kalmamali"
);

// AuthorizationService kullanimi olmali
$AuthServiceKullanimi = strpos($ParameterControllerContent, 'AuthorizationService') !== false;
$Test->assertTrue(
    $AuthServiceKullanimi,
    "ParameterController AuthorizationService kullaniyor olmali"
);

echo "\n";

// ---------------------------------------------
// 8. LOGCONTROLLER ROL KALINTI TESTI
// ---------------------------------------------
echo "\033[33m[8. LogController Rol Kalinti Tests]\033[0m\n";

$LogControllerPath = __DIR__ . '/../app/Controllers/LogController.php';
$LogControllerContent = file_get_contents($LogControllerPath);

// Context::rol() kullanimi olmamali
$ContextRolSayisi = substr_count($LogControllerContent, 'Context::rol()');
$Test->assertEquals(
    0,
    $ContextRolSayisi,
    "LogController'da Context::rol() kullanimi kalmamali"
);

// AuthorizationService kullanimi olmali
$AuthServiceKullanimi = strpos($LogControllerContent, 'AuthorizationService') !== false;
$Test->assertTrue(
    $AuthServiceKullanimi,
    "LogController AuthorizationService kullaniyor olmali"
);

// Rol::SUPERADMIN kullanimi olmamali
$RolEnumSayisi = substr_count($LogControllerContent, 'Rol::SUPERADMIN');
$Test->assertEquals(
    0,
    $RolEnumSayisi,
    "LogController'da Rol::SUPERADMIN kullanimi kalmamali"
);

echo "\n";

// ---------------------------------------------
// 9. USERS/CUSTOMERS SCOPING 4 SENARYO TESTI
// ---------------------------------------------
echo "\033[33m[9. Read_all Scoping 4 Senaryo Tests]\033[0m\n";

// Senaryo 1: users.read_all VAR - tum kullanicilari gormeli
$Test->assertTrue(
    $AuthService->tumunuGorebilirMi((int)($UserWithUsersReadAll['UserId'] ?? 0), 'users') || !$UserWithUsersReadAll,
    'Senaryo 1: users.read_all varsa tumunu gorebilmeli'
);

// Senaryo 2: customers.read_all VAR - tum musterileri gormeli  
$Test->assertTrue(
    $AuthService->tumunuGorebilirMi((int)($UserWithCustomersReadAll['UserId'] ?? 0), 'customers') || !$UserWithCustomersReadAll,
    'Senaryo 2: customers.read_all varsa tumunu gorebilmeli'
);

// Senaryo 3: Belirli bir kullanici icin izin kontrolu
// (Superadmin olmayan bir kullanici varsa test et)
$NormalKullaniciId = 2; // Eger varsa
$NormalKullaniciUsersReadAll = $AuthService->tumunuGorebilirMi($NormalKullaniciId, 'users');
$NormalKullaniciCustomersReadAll = $AuthService->tumunuGorebilirMi($NormalKullaniciId, 'customers');

echo "  Info: UserId={$NormalKullaniciId} users.read_all: " . ($NormalKullaniciUsersReadAll ? 'VAR' : 'YOK') . "\n";
echo "  Info: UserId={$NormalKullaniciId} customers.read_all: " . ($NormalKullaniciCustomersReadAll ? 'VAR' : 'YOK') . "\n";

// Senaryo 4: Controller scope kontrolu (kod analizi)
$UserControllerPath = __DIR__ . '/../app/Controllers/UserController.php';
$UserControllerContent = file_get_contents($UserControllerPath);

$TumunuGorebilirKullanimi = strpos($UserControllerContent, 'tumunuGorebilirMi') !== false;
$Test->assertTrue(
    $TumunuGorebilirKullanimi,
    'Senaryo 4a: UserController tumunuGorebilirMi() kullaniyor'
);

$CustomerControllerPath = __DIR__ . '/../app/Controllers/CustomerController.php';
$CustomerControllerContent = file_get_contents($CustomerControllerPath);

$CustomerTumunuGorebilir = strpos($CustomerControllerContent, 'tumunuGorebilirMi') !== false;
$Test->assertTrue(
    $CustomerTumunuGorebilir,
    'Senaryo 4b: CustomerController tumunuGorebilirMi() kullaniyor'
);

echo "\n";

// ---------------------------------------------
// 10. TUM CONTROLLER'LARDA ESKİ ROL KONTROLU YOK TESTI
// ---------------------------------------------
echo "\033[33m[10. Tum Controller'larda Eski Rol Kontrolu Yok Tests]\033[0m\n";

$ControllersDizini = __DIR__ . '/../app/Controllers';
$ControllerDosyalari = glob($ControllersDizini . '/*.php');

$ToplamContextRol = 0;
$ToplamEskiRolKontrol = 0;

foreach ($ControllerDosyalari as $Dosya) {
    $Icerik = file_get_contents($Dosya);
    $DosyaAdi = basename($Dosya);
    
    $ContextRolSayisi = substr_count($Icerik, 'Context::rol()');
    $EskiRolSayisi = substr_count($Icerik, "\$Rol === 'superadmin'");
    
    $ToplamContextRol += $ContextRolSayisi;
    $ToplamEskiRolKontrol += $EskiRolSayisi;
    
    if ($ContextRolSayisi > 0 || $EskiRolSayisi > 0) {
        echo "  ⚠️  {$DosyaAdi}: Context::rol()={$ContextRolSayisi}, \$Rol==='superadmin'={$EskiRolSayisi}\n";
    }
}

$Test->assertEquals(
    0,
    $ToplamContextRol,
    "Tum Controller'larda Context::rol() kullanimi kalmamali"
);

$Test->assertEquals(
    0,
    $ToplamEskiRolKontrol,
    "Tum Controller'larda \$Rol === 'superadmin' kontrolu kalmamali"
);

echo "\n";

// ---------------------------------------------
// 11. SQL SEED DOSYASI READ_ALL PERMISSION KONTROLU
// ---------------------------------------------
echo "\033[33m[11. SQL Seed Read_all Permission Kontrolu]\033[0m\n";

$SeedDosyasi = __DIR__ . '/../sql/058_read_all_permissions.sql';
$SeedIcerik = file_get_contents($SeedDosyasi);

$UsersReadAllSeed = strpos($SeedIcerik, 'users.read_all') !== false;
$CustomersReadAllSeed = strpos($SeedIcerik, 'customers.read_all') !== false;

$Test->assertTrue(
    $UsersReadAllSeed,
    'SQL seed dosyasinda users.read_all mevcut'
);

$Test->assertTrue(
    $CustomersReadAllSeed,
    'SQL seed dosyasinda customers.read_all mevcut'
);

echo "\n";

// ---------------------------------------------
// 12. UI PERMISSION GORUNURLUGU TESTI
// ---------------------------------------------
echo "\033[33m[12. UI Permission Gorunurlugu Testi]\033[0m\n";

$PermissionsConfig = require __DIR__ . '/../config/permissions_tr.php';
$PermissionCevirileri = $PermissionsConfig['permissionlar'] ?? [];

$Test->assertEquals(
    'Tüm Kullanıcıları Görüntüleme',
    $PermissionCevirileri['users.read_all'] ?? null,
    'permissions_tr.php: users.read_all label dogru'
);

$Test->assertEquals(
    'Tüm Müşterileri Görüntüleme',
    $PermissionCevirileri['customers.read_all'] ?? null,
    'permissions_tr.php: customers.read_all label dogru'
);

$RolesUiPath = __DIR__ . '/../public/pages/roles.php';
$RolesUiContent = file_get_contents($RolesUiPath);

$Test->assertTrue(
    strpos($RolesUiContent, 'PermissionAdiTr') !== false,
    'roles.php: UI PermissionAdiTr kullanmali'
);

echo "\n";

// ---------------------------------------------
// SONUC
// ---------------------------------------------
$Test->summary();

exit($Test->isSuccess() ? 0 : 1);
