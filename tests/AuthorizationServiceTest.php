<?php

/**
 * Authorization Service Test
 * 
 * RBAC sisteminin unit testleri.
 * 
 * Calistirma: php tests/AuthorizationServiceTest.php
 */

// Bootstrap
require_once __DIR__ . '/../app/Core/bootstrap.php';

use App\Services\Authorization\AuthorizationService;
use App\Core\Redis;

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
    
    public function assertIsArray($Value, string $Mesaj): void
    {
        $this->assert(is_array($Value), $Mesaj);
    }
    
    public function summary(): void
    {
        echo "\n" . str_repeat('=', 50) . "\n";
        echo "Test Sonuclari: \033[32m{$this->Passed} Passed\033[0m, \033[31m{$this->Failed} Failed\033[0m\n";
        
        if (!empty($this->Hatalar)) {
            echo "\nBasarisiz Testler:\n";
            foreach ($this->Hatalar as $Hata) {
                echo "  - {$Hata}\n";
            }
        }
        
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

echo "\n\033[34m=== AuthorizationService Unit Tests ===\033[0m\n\n";

$Test = new TestRunner();

// ---------------------------------------------
// Redis Baglanti Testi
// ---------------------------------------------
echo "\033[33m[Redis Tests]\033[0m\n";

try {
    $Redis = Redis::getInstance();
    $Test->assertTrue($Redis->bagliMi(), 'Redis baglantisi basarili olmali');
    
    // Set/Get testi
    $TestKey = 'test:auth:' . time();
    $TestValue = ['foo' => 'bar', 'num' => 123];
    
    $Saved = $Redis->kaydet($TestKey, $TestValue, 60);
    $Test->assertTrue($Saved, 'Redis kayit basarili olmali');
    
    $Retrieved = $Redis->al($TestKey);
    $Test->assertEquals($TestValue['foo'], $Retrieved['foo'], 'Redis deger dogru donmeli');
    
    // Temizlik
    $Redis->sil($TestKey);
    $Test->assertFalse($Redis->varMi($TestKey), 'Redis silme basarili olmali');
    
} catch (Exception $E) {
    $Test->assert(false, 'Redis testi basarisiz: ' . $E->getMessage());
}

// ---------------------------------------------
// AuthorizationService Testi
// ---------------------------------------------
echo "\n\033[33m[AuthorizationService Tests]\033[0m\n";

try {
    $AuthService = AuthorizationService::getInstance();
    $Test->assertNotEmpty($AuthService, 'AuthorizationService instance olusturulmali');
    
    // Tum rolleri getir
    $Roller = $AuthService->tumRolleriGetir();
    $Test->assertIsArray($Roller, 'tumRolleriGetir array donmeli');
    
    // Tum permissionlari getir
    $Permissionlar = $AuthService->tumPermissionlariGetir();
    $Test->assertIsArray($Permissionlar, 'tumPermissionlariGetir array donmeli');
    
    // Modul bazinda permissionlar
    $ModulBazinda = $AuthService->permissionlariModulBazindaGetir();
    $Test->assertIsArray($ModulBazinda, 'permissionlariModulBazindaGetir array donmeli');
    
    // Merkezi permission kontrolu (can)
    $HasPermission = $AuthService->can(1, 'users.create');
    echo "  Info: UserId=1 users.create: " . ($HasPermission ? 'VAR' : 'YOK') . "\n";
    
} catch (Exception $E) {
    $Test->assert(false, 'AuthorizationService testi basarisiz: ' . $E->getMessage());
}

// ---------------------------------------------
// Permission Kontrol Testleri
// ---------------------------------------------
echo "\n\033[33m[Permission Check Tests]\033[0m\n";

try {
    $AuthService = AuthorizationService::getInstance();
    
    // Gecersiz kullanici testi
    $NoPermission = $AuthService->can(999999, 'users.create');
    $Test->assertFalse($NoPermission, 'Gecersiz kullanici icin permission false donmeli');
    
    // Bos permission listesi
    $UserPerms = $AuthService->kullaniciPermissionlariGetir(999999);
    $Test->assertIsArray($UserPerms, 'Gecersiz kullanici icin bos array donmeli');
    
    // Modul erisim kontrolu
    $NoModuleAccess = $AuthService->modulErisimVarMi(999999, 'users');
    $Test->assertFalse($NoModuleAccess, 'Gecersiz kullanici icin modul erisimi false donmeli');
    
} catch (Exception $E) {
    $Test->assert(false, 'Permission kontrol testi basarisiz: ' . $E->getMessage());
}

// ---------------------------------------------
// Subset Constraint Testleri
// ---------------------------------------------
echo "\n\033[33m[Subset Constraint Tests]\033[0m\n";

try {
    $AuthService = AuthorizationService::getInstance();
    
    // Rol atama subset kontrolu (boolean donmeli)
    $CanAssign = $AuthService->rolAtayabilirMi(1, 2);
    $Test->assertTrue(is_bool($CanAssign), 'rolAtayabilirMi boolean donmeli');
    
    // Permission ekleme subset kontrolu
    $CanAddPerm = $AuthService->rolePermissionEkleyebilirMi(1, 'users.create');
    $Test->assertTrue(is_bool($CanAddPerm), 'rolePermissionEkleyebilirMi boolean donmeli');
    
    // Atanabilir roller
    $AtanabilirRoller = $AuthService->atanabilirRolleriGetir(1);
    $Test->assertIsArray($AtanabilirRoller, 'atanabilirRolleriGetir array donmeli');
    
} catch (Exception $E) {
    $Test->assert(false, 'Subset constraint testi basarisiz: ' . $E->getMessage());
}

// ---------------------------------------------
// Cache Invalidation Testleri
// ---------------------------------------------
echo "\n\033[33m[Cache Invalidation Tests]\033[0m\n";

try {
    $AuthService = AuthorizationService::getInstance();
    
    // Cache temizleme hata vermemeli
    $AuthService->kullaniciCacheTemizle(1);
    $Test->assertTrue(true, 'kullaniciCacheTemizle basarili olmali');
    
    $AuthService->tumCacheTemizle();
    $Test->assertTrue(true, 'tumCacheTemizle basarili olmali');
    
} catch (Exception $E) {
    $Test->assert(false, 'Cache invalidation testi basarisiz: ' . $E->getMessage());
}

// ---------------------------------------------
// Frontend Yetki Ciktisi Testi
// ---------------------------------------------
echo "\n\033[33m[Frontend Output Tests]\033[0m\n";

try {
    $AuthService = AuthorizationService::getInstance();
    
    $FrontendData = $AuthService->frontendIcinYetkiler(1);
    
    $Test->assertIsArray($FrontendData, 'frontendIcinYetkiler array donmeli');
    $Test->assertTrue(isset($FrontendData['roller']), 'Frontend data roller icermeli');
    $Test->assertTrue(isset($FrontendData['permissionlar']), 'Frontend data permissionlar icermeli');
    $Test->assertTrue(isset($FrontendData['moduller']), 'Frontend data moduller icermeli');
    $Test->assertTrue(!isset($FrontendData['superadmin']), 'Frontend data superadmin icermemeli');
    
} catch (Exception $E) {
    $Test->assert(false, 'Frontend output testi basarisiz: ' . $E->getMessage());
}

// =============================================
// SONUC
// =============================================
$Test->summary();

exit($Test->isSuccess() ? 0 : 1);
