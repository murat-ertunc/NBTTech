<?php

/**
 * Permission Middleware Test
 * 
 * Middleware permission kontrollerinin testleri.
 * 
 * Calistirma: php tests/PermissionMiddlewareTest.php
 */

// Bootstrap
require_once __DIR__ . '/../app/Core/bootstrap.php';

use App\Core\Context;
use App\Services\Authorization\AuthorizationService;

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
    
    public function assertTrue(bool $Value, string $Mesaj): void
    {
        $this->assert($Value === true, $Mesaj);
    }
    
    public function assertFalse(bool $Value, string $Mesaj): void
    {
        $this->assert($Value === false, $Mesaj);
    }
    
    public function summary(): void
    {
        echo "\n" . str_repeat('=', 50) . "\n";
        echo "Test Sonuclari: \033[32m{$this->Passed} Passed\033[0m, \033[31m{$this->Failed} Failed\033[0m\n";
        echo str_repeat('=', 50) . "\n";
    }
    
    public function isSuccess(): bool
    {
        return $this->Failed === 0;
    }
}

// =============================================
// TESTLER
// =============================================

echo "\n\033[34m=== Permission Logic Tests ===\033[0m\n\n";
echo "(Note: Middleware HTTP response gerektirdigindan, servis katmani test edilir)\n\n";

$Test = new TestRunner();

// ---------------------------------------------
// AuthorizationService Permission Kontrolleri
// ---------------------------------------------
echo "\033[33m[Permission Check Logic]\033[0m\n";

try {
    $AuthService = AuthorizationService::getInstance();
    
    // Superadmin (UserId=1) icin testler
    $IsSuperadmin = $AuthService->superadminMi(1);
    
    if ($IsSuperadmin) {
        // Superadmin tum izinlere sahip olmali
        $Test->assertTrue($AuthService->izinVarMi(1, 'users.create'), 'Superadmin users.create yetkisine sahip olmali');
        $Test->assertTrue($AuthService->izinVarMi(1, 'roles.delete'), 'Superadmin roles.delete yetkisine sahip olmali');
        $Test->assertTrue($AuthService->izinVarMi(1, 'logs.read'), 'Superadmin logs.read yetkisine sahip olmali');
        
        // Modul erisimi
        $Test->assertTrue($AuthService->modulErisimVarMi(1, 'users'), 'Superadmin users modulune erismeli');
        $Test->assertTrue($AuthService->modulErisimVarMi(1, 'invoices'), 'Superadmin invoices modulune erismeli');
        
        // Birden fazla permission kontrolu
        $Test->assertTrue(
            $AuthService->izinlerdenBiriVarMi(1, ['users.create', 'invalid.permission']),
            'izinlerdenBiriVarMi dogru calismali'
        );
        
        $Test->assertTrue(
            $AuthService->tumIzinlerVarMi(1, ['users.create', 'users.read']),
            'tumIzinlerVarMi dogru calismali'
        );
    } else {
        echo "  \033[33mNote: UserId=1 superadmin degil, bazi testler atlanacak\033[0m\n";
    }
    
    // Gecersiz kullanici
    $Test->assertFalse($AuthService->izinVarMi(999999, 'users.create'), 'Gecersiz kullanici icin permission false olmali');
    $Test->assertFalse($AuthService->superadminMi(999999), 'Gecersiz kullanici superadmin olmamali');
    $Test->assertFalse($AuthService->modulErisimVarMi(999999, 'users'), 'Gecersiz kullanici modul erisimi olmamali');
    
} catch (Exception $E) {
    $Test->assert(false, 'Permission kontrol testi basarisiz: ' . $E->getMessage());
}

// ---------------------------------------------
// Rol Kontrolleri
// ---------------------------------------------
echo "\n\033[33m[Role Check Logic]\033[0m\n";

try {
    $AuthService = AuthorizationService::getInstance();
    
    // UserId=1 icin rol kontrolu
    $Roller = $AuthService->kullaniciRolleriGetir(1);
    
    if (!empty($Roller)) {
        $IlkRol = $Roller[0]['RolKodu'];
        $Test->assertTrue($AuthService->rolVarMi(1, $IlkRol), "rolVarMi '{$IlkRol}' icin true donmeli");
    }
    
    // Olmayan rol
    $Test->assertFalse($AuthService->rolVarMi(1, 'gecersiz_rol_kodu_xyz'), 'Olmayan rol icin false donmeli');
    
    // En yuksek seviye
    $Seviye = $AuthService->kullaniciEnYuksekSeviye(1);
    $Test->assertTrue($Seviye >= 0, 'kullaniciEnYuksekSeviye 0 veya ustu olmali');
    
    // Gecersiz kullanici seviyesi
    $GecersizSeviye = $AuthService->kullaniciEnYuksekSeviye(999999);
    $Test->assertTrue($GecersizSeviye === 0, 'Gecersiz kullanici seviyesi 0 olmali');
    
} catch (Exception $E) {
    $Test->assert(false, 'Rol kontrol testi basarisiz: ' . $E->getMessage());
}

// ---------------------------------------------
// Subset Constraint Mantigi
// ---------------------------------------------
echo "\n\033[33m[Subset Constraint Logic]\033[0m\n";

try {
    $AuthService = AuthorizationService::getInstance();
    
    // Superadmin her seyi atayabilmeli
    if ($AuthService->superadminMi(1)) {
        $Test->assertTrue($AuthService->rolAtayabilirMi(1, 2), 'Superadmin her rolu atayabilmeli');
        $Test->assertTrue($AuthService->rolePermissionEkleyebilirMi(1, 'any.permission'), 'Superadmin her permissioni ekleyebilmeli');
    }
    
    // Atanabilir roller kontrolu
    $AtanabilirRoller = $AuthService->atanabilirRolleriGetir(999999);
    $Test->assertTrue(is_array($AtanabilirRoller), 'atanabilirRolleriGetir array donmeli');
    
    // Permission ekleyebilir mi kontrolu
    $EkleyebilirMi = $AuthService->rolePermissionlarEkleyebilirMi(1, ['users.create', 'invalid.perm']);
    $Test->assertTrue(isset($EkleyebilirMi['izinVerilenler']), 'izinVerilenler key olmali');
    $Test->assertTrue(isset($EkleyebilirMi['izinVerilmeyenler']), 'izinVerilmeyenler key olmali');
    
} catch (Exception $E) {
    $Test->assert(false, 'Subset constraint testi basarisiz: ' . $E->getMessage());
}

// =============================================
// SONUC
// =============================================
$Test->summary();

exit($Test->isSuccess() ? 0 : 1);
