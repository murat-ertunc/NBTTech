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
    
    // Merkezi can() kontrolu
    $Test->assertTrue(is_bool($AuthService->can(1, 'users.create')), 'can() boolean donmeli');
    
    // Modul erisimi
    $Test->assertTrue(is_bool($AuthService->modulErisimVarMi(1, 'users')), 'modulErisimVarMi boolean donmeli');
    
    // Birden fazla permission kontrolu
    $Test->assertTrue(
        is_bool($AuthService->izinlerdenBiriVarMi(1, ['users.create', 'invalid.permission'])),
        'izinlerdenBiriVarMi boolean donmeli'
    );
    
    $Test->assertTrue(
        is_bool($AuthService->tumIzinlerVarMi(1, ['users.create', 'users.read'])),
        'tumIzinlerVarMi boolean donmeli'
    );
    
    // Gecersiz kullanici
    $Test->assertFalse($AuthService->can(999999, 'users.create'), 'Gecersiz kullanici icin permission false olmali');
    $Test->assertFalse($AuthService->modulErisimVarMi(999999, 'users'), 'Gecersiz kullanici modul erisimi olmamali');
    
} catch (Exception $E) {
    $Test->assert(false, 'Permission kontrol testi basarisiz: ' . $E->getMessage());
}

// Rol kontrolu artik permission tabanli oldugu icin bu kisim kaldirildi.

// ---------------------------------------------
// Subset Constraint Mantigi
// ---------------------------------------------
echo "\n\033[33m[Subset Constraint Logic]\033[0m\n";

try {
    $AuthService = AuthorizationService::getInstance();
    
    // Subset constraint boolean sonuc donmeli
    $Test->assertTrue(is_bool($AuthService->rolAtayabilirMi(1, 2)), 'rolAtayabilirMi boolean donmeli');
    $Test->assertTrue(is_bool($AuthService->rolePermissionEkleyebilirMi(1, 'any.permission')), 'rolePermissionEkleyebilirMi boolean donmeli');
    
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
