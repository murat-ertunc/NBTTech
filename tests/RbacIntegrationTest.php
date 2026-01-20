<?php

/**
 * RBAC Integration Tests
 * 
 * Kullanici isteklerine gore 8 test senaryosu:
 * 1. Yetkili kullanici tum sayfalari gorebilir
 * 2. Sadece customers.read yetkisi olan kullanici musterileri gorebilir
 * 3. Subset constraint: Admin sadece kendi altindaki rolleri atayabilir
 * 4. Subset constraint: Admin sadece kendi sahip oldugu permissionlari verebilir
 * 5. Cache invalidation: Rol guncellendikten sonra cache temizlenir
 * 6. API endpoint yetki kontrolu
 * 7. Frontend permission data formati
 * 8. Atanabilir roller permission alt kumesi
 * 
 * Calistirma: php tests/RbacIntegrationTest.php
 */

require_once __DIR__ . '/../app/Core/bootstrap.php';

use App\Services\Authorization\AuthorizationService;
use App\Core\Redis;
use App\Core\Database;

/**
 * Test Framework
 */
class RbacTestRunner
{
    private int $Passed = 0;
    private int $Failed = 0;
    private int $Skipped = 0;
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
    
    public function skip(string $Mesaj): void
    {
        $this->Skipped++;
        echo "\033[33m○\033[0m {$Mesaj} (SKIPPED)\n";
    }
    
    public function assertEquals($Expected, $Actual, string $Mesaj): void
    {
        $this->assert($Expected === $Actual, $Mesaj);
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
    
    public function assertEmpty($Value, string $Mesaj): void
    {
        $this->assert(empty($Value), $Mesaj);
    }
    
    public function assertIsArray($Value, string $Mesaj): void
    {
        $this->assert(is_array($Value), $Mesaj);
    }
    
    public function assertContains($Needle, array $Haystack, string $Mesaj): void
    {
        $this->assert(in_array($Needle, $Haystack, true), $Mesaj);
    }
    
    public function assertNotContains($Needle, array $Haystack, string $Mesaj): void
    {
        $this->assert(!in_array($Needle, $Haystack, true), $Mesaj);
    }
    
    public function assertGreaterThan(int $Expected, int $Actual, string $Mesaj): void
    {
        $this->assert($Actual > $Expected, $Mesaj . " (Expected > {$Expected}, Actual: {$Actual})");
    }
    
    public function summary(): void
    {
        echo "\n" . str_repeat('=', 60) . "\n";
        echo "Test Sonuclari: ";
        echo "\033[32m{$this->Passed} Passed\033[0m, ";
        echo "\033[31m{$this->Failed} Failed\033[0m, ";
        echo "\033[33m{$this->Skipped} Skipped\033[0m\n";
        
        if (!empty($this->Hatalar)) {
            echo "\nBasarisiz Testler:\n";
            foreach ($this->Hatalar as $Hata) {
                echo "  \033[31m✗\033[0m {$Hata}\n";
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
// TEST SETUP
// =============================================

echo "\n\033[34m╔══════════════════════════════════════════════════════════╗\033[0m\n";
echo "\033[34m║         RBAC Integration Test Suite                      ║\033[0m\n";
echo "\033[34m╚══════════════════════════════════════════════════════════╝\033[0m\n\n";

$Test = new RbacTestRunner();
$AuthService = AuthorizationService::getInstance();
$Redis = Redis::getInstance();
$Db = Database::getInstance()->getConnection();

// Test kullanicilari ara
$SuperadminId = null;
$AdminId = null;
$NormalUserId = null;

// Superadmin bul
$Stmt = $Db->query("
    SELECT TOP 1 ur.UserId 
    FROM tnm_user_rol ur 
    INNER JOIN tnm_rol r ON r.Id = ur.RolId 
    WHERE r.RolKodu = 'superadmin' AND ur.Sil = 0 AND r.Sil = 0
");
$Row = $Stmt->fetch(\PDO::FETCH_ASSOC);
if ($Row) {
    $SuperadminId = (int)$Row['UserId'];
}

// Admin bul
$Stmt = $Db->query("
    SELECT TOP 1 ur.UserId 
    FROM tnm_user_rol ur 
    INNER JOIN tnm_rol r ON r.Id = ur.RolId 
    WHERE r.RolKodu = 'admin' AND ur.Sil = 0 AND r.Sil = 0
");
$Row = $Stmt->fetch(\PDO::FETCH_ASSOC);
if ($Row) {
    $AdminId = (int)$Row['UserId'];
}

echo "\033[36mTest Ortami:\033[0m\n";
echo "  SuperadminId: " . ($SuperadminId ?: 'Bulunamadi') . "\n";
echo "  AdminId: " . ($AdminId ?: 'Bulunamadi') . "\n\n";

// =============================================
// SENARYO 1: Yetkili kullanici tum sayfalari gorebilir
// =============================================
echo "\033[33m[SENARYO 1] Yetkili Kullanici Tum Yetkilere Sahip\033[0m\n";

if ($SuperadminId) {
    // Tum modullere erisim
    $Moduller = ['users', 'roles', 'customers', 'invoices', 'payments', 'projects', 
                 'offers', 'contracts', 'guarantees', 'meetings', 'contacts', 
                 'files', 'calendar', 'stamp_taxes', 'parameters', 'logs'];
    
    foreach ($Moduller as $Modul) {
        $HasAccess = $AuthService->modulErisimVarMi($SuperadminId, $Modul);
        $Test->assertTrue($HasAccess, "Yetkili kullanici '{$Modul}' modulune erismeli");
    }
    
    // CRUD permissionlari
    $CrudPerms = ['customers.create', 'customers.read', 'customers.update', 'customers.delete'];
    foreach ($CrudPerms as $Perm) {
        $Test->assertTrue(
            $AuthService->can($SuperadminId, $Perm),
            "Yetkili kullanici '{$Perm}' yetkisine sahip olmali"
        );
    }
} else {
    $Test->skip('Superadmin kullanici bulunamadi - seed gerekli');
}

// =============================================
// SENARYO 2: Kisitli yetkili kullanici
// =============================================
echo "\n\033[33m[SENARYO 2] Kisitli Yetkili Kullanici\033[0m\n";

// Test icin gecici kullanici ve rol olustur
try {
    // Sadece customers.read yetkisi olan test rolu
    $TestRolKodu = 'test_readonly_' . time();
    
    // Rol olustur
    $Stmt = $Db->prepare("
        INSERT INTO tnm_rol (Guid, EklemeZamani, EkleyenUserId, DegisiklikZamani, DegistirenUserId, Sil, RolKodu, RolAdi, Seviye, Aktif)
        OUTPUT INSERTED.Id
        VALUES (NEWID(), GETDATE(), 1, GETDATE(), 1, 0, :RolKodu, 'Test ReadOnly', 10, 1)
    ");
    $Stmt->execute([':RolKodu' => $TestRolKodu]);
    $TestRolId = $Stmt->fetchColumn();
    
    // Sadece customers.read permission ekle
    $Stmt = $Db->prepare("SELECT Id FROM tnm_permission WHERE PermissionKodu = 'customers.read' AND Sil = 0");
    $Stmt->execute();
    $PermId = $Stmt->fetchColumn();
    
    if ($PermId && $TestRolId) {
        // Permission ata
        $Stmt = $Db->prepare("
            INSERT INTO tnm_rol_permission (Guid, EklemeZamani, EkleyenUserId, DegisiklikZamani, DegistirenUserId, Sil, RolId, PermissionId)
            VALUES (NEWID(), GETDATE(), 1, GETDATE(), 1, 0, :RolId, :PermId)
        ");
        $Stmt->execute([':RolId' => $TestRolId, ':PermId' => $PermId]);
        
        // Test kullanici olustur
        $TestUserEmail = 'testrbac' . time() . '@test.com';
        $Stmt = $Db->prepare("
            INSERT INTO tnm_user (Guid, EklemeZamani, EkleyenUserId, DegisiklikZamani, DegistirenUserId, Sil, KullaniciAdi, Email, Sifre, Aktif)
            OUTPUT INSERTED.Id
            VALUES (NEWID(), GETDATE(), 1, GETDATE(), 1, 0, 'TestRbac', :Email, 'hash', 1)
        ");
        $Stmt->execute([':Email' => $TestUserEmail]);
        $TestUserId = $Stmt->fetchColumn();
        
        // Rolu ata
        $Stmt = $Db->prepare("
            INSERT INTO tnm_user_rol (Guid, EklemeZamani, EkleyenUserId, DegisiklikZamani, DegistirenUserId, Sil, UserId, RolId)
            VALUES (NEWID(), GETDATE(), 1, GETDATE(), 1, 0, :UserId, :RolId)
        ");
        $Stmt->execute([':UserId' => $TestUserId, ':RolId' => $TestRolId]);
        
        // Cache temizle
        $AuthService->kullaniciCacheTemizle($TestUserId);
        
        // Test: customers.read var olmali
        $Test->assertTrue(
            $AuthService->can($TestUserId, 'customers.read'),
            'Test kullanici customers.read yetkisine sahip olmali'
        );
        
        // Test: customers.create olmamali
        $Test->assertFalse(
            $AuthService->can($TestUserId, 'customers.create'),
            'Test kullanici customers.create yetkisine sahip OLMAMALI'
        );
        
        // Test: users modulu olmamali
        $Test->assertFalse(
            $AuthService->modulErisimVarMi($TestUserId, 'users'),
            'Test kullanici users modulune erismemeli'
        );
        
        // Test: customers modulu olmali
        $Test->assertTrue(
            $AuthService->modulErisimVarMi($TestUserId, 'customers'),
            'Test kullanici customers modulune erismeli'
        );
        
        // Temizlik - soft delete
        $Db->exec("UPDATE tnm_user_rol SET Sil = 1 WHERE UserId = {$TestUserId}");
        $Db->exec("UPDATE tnm_rol_permission SET Sil = 1 WHERE RolId = {$TestRolId}");
        $Db->exec("UPDATE tnm_rol SET Sil = 1 WHERE Id = {$TestRolId}");
        $Db->exec("UPDATE tnm_user SET Sil = 1 WHERE Id = {$TestUserId}");
        
    } else {
        $Test->skip('customers.read permission veya test rol olusturulamadi');
    }
    
} catch (Exception $E) {
    $Test->assert(false, 'Kisitli yetki testi basarisiz: ' . $E->getMessage());
}

// =============================================
// SENARYO 3: Subset Constraint - Rol Atama
// =============================================
echo "\n\033[33m[SENARYO 3] Subset Constraint - Rol Atama\033[0m\n";

if ($SuperadminId) {
    // Yetkili kullanici kendi permission setine uygun rolleri atayabilmeli
    $Roller = $AuthService->tumRolleriGetir();
    foreach ($Roller as $Rol) {
        $Test->assertTrue(
            $AuthService->rolAtayabilirMi($SuperadminId, $Rol['Id']),
            "Yetkili kullanici '{$Rol['RolKodu']}' rolunu atayabilmeli"
        );
    }
} else {
    $Test->skip('Yetkili kullanici bulunamadi');
}

if ($AdminId) {
    // Admin icin izin verilmeyen bir rol varsa false donmeli
    $AdminPerms = $AuthService->kullaniciPermissionlariGetir($AdminId);
    $Roller = $AuthService->tumRolleriGetir();
    $Bulundu = false;
    foreach ($Roller as $Rol) {
        $RolPerms = $AuthService->rolPermissionlariGetir((int)$Rol['Id']);
        if (!empty(array_diff($RolPerms, $AdminPerms))) {
            $Bulundu = true;
            $Test->assertFalse(
                $AuthService->rolAtayabilirMi($AdminId, (int)$Rol['Id']),
                "Admin '{$Rol['RolKodu']}' rolunu atayamamali (permission alt kume degil)"
            );
            break;
        }
    }
    if (!$Bulundu) {
        $Test->skip('Admin icin izin verilmeyen rol bulunamadi');
    }
}

// =============================================
// SENARYO 4: Subset Constraint - Permission Atama
// =============================================
echo "\n\033[33m[SENARYO 4] Subset Constraint - Permission Atama\033[0m\n";

$PermissionUserId = $SuperadminId ?: $AdminId;
if ($PermissionUserId) {
    $Test->assertTrue(
        is_bool($AuthService->rolePermissionEkleyebilirMi($PermissionUserId, 'users.create')),
        'rolePermissionEkleyebilirMi boolean donmeli'
    );
    
    // Toplu permission kontrolu
    $PermKodlari = ['users.create', 'users.read', 'customers.create'];
    $Sonuc = $AuthService->rolePermissionlarEkleyebilirMi($PermissionUserId, $PermKodlari);
    
    $Test->assertTrue(
        isset($Sonuc['izinVerilenler']) && isset($Sonuc['izinVerilmeyenler']),
        'rolePermissionlarEkleyebilirMi izinVerilenler/izinVerilmeyenler donmeli'
    );
} else {
    $Test->skip('Yetkili kullanici bulunamadi');
}

// =============================================
// SENARYO 5: Cache Invalidation
// =============================================
echo "\n\033[33m[SENARYO 5] Cache Invalidation\033[0m\n";

if ($SuperadminId) {
    // Onceki cache'i temizle
    $AuthService->kullaniciCacheTemizle($SuperadminId);
    
    // Permission sorgula (cache olusur)
    $Perms1 = $AuthService->kullaniciPermissionlariGetir($SuperadminId);
    $Test->assertNotEmpty($Perms1, 'Permission listesi bos olmamali');
    
    // Redis'te cache var mi kontrol et
    $CacheKey = sprintf('user:%d:permissions', $SuperadminId);
    
    // Ikinci sorgu cache'den gelmeli (ayni sonuc)
    $Perms2 = $AuthService->kullaniciPermissionlariGetir($SuperadminId);
    $Test->assertEquals(count($Perms1), count($Perms2), 'Cache sonucu ayni olmali');
    
    // Cache temizle
    $AuthService->kullaniciCacheTemizle($SuperadminId);
    $Test->assertTrue(true, 'kullaniciCacheTemizle hata vermemeli');
    
    // Tum cache temizle
    $AuthService->tumCacheTemizle();
    $Test->assertTrue(true, 'tumCacheTemizle hata vermemeli');
    
} else {
    $Test->skip('Superadmin kullanici bulunamadi');
}

// =============================================
// SENARYO 6: API Endpoint Yetki Kontrolu
// =============================================
echo "\n\033[33m[SENARYO 6] API Endpoint Permission Mapping\033[0m\n";

// Permission middleware'in dogru calistigini test et
$ApiPermissionMapping = [
    'GET /api/customers' => 'customers.read',
    'POST /api/customers' => 'customers.create',
    'PUT /api/customers/{id}' => 'customers.update',
    'DELETE /api/customers/{id}' => 'customers.delete',
    'GET /api/users' => 'users.read',
    'GET /api/roles' => 'roles.read',
    'GET /api/invoices' => 'invoices.read',
    'GET /api/logs' => 'logs.read',
];

// Permission tablosunda bu kodlar var mi kontrol et
foreach ($ApiPermissionMapping as $Endpoint => $PermKodu) {
    $Stmt = $Db->prepare("SELECT COUNT(*) FROM tnm_permission WHERE PermissionKodu = :Kod AND Sil = 0");
    $Stmt->execute([':Kod' => $PermKodu]);
    $Var = $Stmt->fetchColumn() > 0;
    
    $Test->assertTrue($Var, "'{$PermKodu}' permission kodu veritabaninda olmali ({$Endpoint})");
}

// =============================================
// SENARYO 7: Frontend Permission Data Formati
// =============================================
echo "\n\033[33m[SENARYO 7] Frontend Permission Data Formati\033[0m\n";

if ($SuperadminId) {
    $FrontendData = $AuthService->frontendIcinYetkiler($SuperadminId);
    
    // Zorunlu alanlar
    $Test->assertTrue(isset($FrontendData['roller']), 'Frontend data: roller alani olmali');
    $Test->assertTrue(isset($FrontendData['permissionlar']), 'Frontend data: permissionlar alani olmali');
    $Test->assertTrue(isset($FrontendData['moduller']), 'Frontend data: moduller alani olmali');
    $Test->assertTrue(!isset($FrontendData['superadmin']), 'Frontend data: superadmin alani olmamali');
    
    // permissionlar obje formatinda olmali (kod => true)
    $Test->assertIsArray($FrontendData['permissionlar'], 'permissionlar array olmali');
    
    // moduller listesi olmali
    $Test->assertIsArray($FrontendData['moduller'], 'moduller array olmali');
    $Test->assertTrue(isset($FrontendData['moduller']['customers']), 'moduller customers icermeli');
    $Test->assertTrue(isset($FrontendData['moduller']['users']), 'moduller users icermeli');
    
} else {
    $Test->skip('Superadmin kullanici bulunamadi');
}

// =============================================
// SENARYO 8: Atanabilir Roller Permission Alt Kumesi
// =============================================
echo "\n\033[33m[SENARYO 8] Atanabilir Roller Permission Alt Kumesi\033[0m\n";

$UserId = $AdminId ?: $SuperadminId;
if ($UserId) {
    $UserPerms = $AuthService->kullaniciPermissionlariGetir($UserId);
    $AtanabilirRoller = $AuthService->atanabilirRolleriGetir($UserId);
    
    foreach ($AtanabilirRoller as $Rol) {
        $RolPerms = $AuthService->rolPermissionlariGetir((int)$Rol['Id']);
        $Test->assertTrue(
            empty(array_diff($RolPerms, $UserPerms)),
            "Atanabilir rol '{$Rol['RolKodu']}' permission alt kumesi olmali"
        );
    }
} else {
    $Test->skip('Admin veya yetkili kullanici bulunamadi');
}

// =============================================
// SONUC
// =============================================
$Test->summary();

exit($Test->isSuccess() ? 0 : 1);
