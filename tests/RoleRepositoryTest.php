<?php

/**
 * Role Repository Test
 * 
 * Rol CRUD ve permission atama testleri.
 * 
 * Calistirma: php tests/RoleRepositoryTest.php
 */

// Bootstrap
require_once __DIR__ . '/../app/Core/bootstrap.php';

use App\Repositories\RoleRepository;
use App\Services\Authorization\AuthorizationService;

/**
 * Basit Test Framework (AuthorizationServiceTest'ten kopyalandi)
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
    
    public function assertEmpty($Value, string $Mesaj): void
    {
        $this->assert(empty($Value), $Mesaj);
    }
    
    public function assertIsArray($Value, string $Mesaj): void
    {
        $this->assert(is_array($Value), $Mesaj);
    }
    
    public function assertNull($Value, string $Mesaj): void
    {
        $this->assert($Value === null, $Mesaj);
    }
    
    public function assertNotNull($Value, string $Mesaj): void
    {
        $this->assert($Value !== null, $Mesaj);
    }
    
    public function assertThrows(callable $Fn, string $ExpectedMessage, string $Mesaj): void
    {
        try {
            $Fn();
            $this->assert(false, $Mesaj . ' - Exception bekleniyor ama atilmadi');
        } catch (Exception $E) {
            if (strpos($E->getMessage(), $ExpectedMessage) !== false) {
                $this->assert(true, $Mesaj);
            } else {
                $this->assert(false, $Mesaj . ' - Beklenen: "' . $ExpectedMessage . '", Gelen: "' . $E->getMessage() . '"');
            }
        }
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

echo "\n\033[34m=== RoleRepository Unit Tests ===\033[0m\n\n";

$Test = new TestRunner();
$TestRolId = null;

// ---------------------------------------------
// Rol Listeleme Testleri
// ---------------------------------------------
echo "\033[33m[Role List Tests]\033[0m\n";

try {
    $Repo = new RoleRepository();
    
    // Tum rolleri getir
    $Roller = $Repo->tumRoller();
    $Test->assertIsArray($Roller, 'tumRoller array donmeli');
    $Test->assertNotEmpty($Roller, 'En az bir rol olmali (seed sonrasi)');
    
    // Rol detayi
    if (!empty($Roller)) {
        $IlkRol = $Roller[0];
        $Detay = $Repo->rolDetay($IlkRol['Id']);
        $Test->assertNotNull($Detay, 'rolDetay null olmamali');
        $Test->assertTrue(isset($Detay['Permissionlar']), 'Rol detayi Permissionlar icermeli');
    }
    
    // Olmayan rol
    $OlmayanRol = $Repo->bul(999999);
    $Test->assertNull($OlmayanRol, 'Olmayan rol icin null donmeli');
    
} catch (Exception $E) {
    $Test->assert(false, 'Rol listeleme testi basarisiz: ' . $E->getMessage());
}

// ---------------------------------------------
// Rol CRUD Testleri
// ---------------------------------------------
echo "\n\033[33m[Role CRUD Tests]\033[0m\n";

try {
    $Repo = new RoleRepository();
    
    // Yeni rol olustur
    $TestRolKodu = 'test_role_' . time();
    $TestRolId = $Repo->rolEkle([
        'RolKodu'  => $TestRolKodu,
        'RolAdi'   => 'Test Rolü',
        'Aciklama' => 'Unit test için oluşturuldu',
        'Seviye'   => 10
    ], 1);
    
    $Test->assertNotEmpty($TestRolId, 'Rol ekleme ID donmeli');
    
    // Eklenen rolu getir
    $EklenenRol = $Repo->bul($TestRolId);
    $Test->assertNotNull($EklenenRol, 'Eklenen rol bulunabilmeli');
    $Test->assertEquals($TestRolKodu, $EklenenRol['RolKodu'], 'Rol kodu dogru olmali');
    
    // Rol guncelle
    $Repo->rolGuncelle($TestRolId, [
        'RolAdi'   => 'Güncellenmiş Test Rolü',
        'Aciklama' => 'Güncellendi'
    ], 1);
    
    $GuncellenenRol = $Repo->bul($TestRolId);
    $Test->assertEquals('Güncellenmiş Test Rolü', $GuncellenenRol['RolAdi'], 'Rol adi guncellenmeli');
    
} catch (Exception $E) {
    $Test->assert(false, 'Rol CRUD testi basarisiz: ' . $E->getMessage());
}

// ---------------------------------------------
// Validasyon Testleri
// ---------------------------------------------
echo "\n\033[33m[Validation Tests]\033[0m\n";

try {
    $Repo = new RoleRepository();
    
    // Bos rol kodu
    $Test->assertThrows(function() use ($Repo) {
        $Repo->rolEkle(['RolKodu' => '', 'RolAdi' => 'Test'], 1);
    }, 'zorunlu', 'Bos rol kodu exception atilmali');
    
    // Duplicate rol kodu
    $Test->assertThrows(function() use ($Repo) {
        $Repo->rolEkle(['RolKodu' => 'superadmin', 'RolAdi' => 'Duplicate'], 1);
    }, 'zaten kullaniliyor', 'Duplicate rol kodu exception atilmali');
    
    // Sistem rolu duzenleme
    $SuperadminRol = $Repo->rolKoduIleBul('superadmin');
    if ($SuperadminRol) {
        $Test->assertThrows(function() use ($Repo, $SuperadminRol) {
            $Repo->rolGuncelle($SuperadminRol['Id'], ['RolAdi' => 'Hack'], 1);
        }, 'Sistem rolleri', 'Sistem rolu duzenleme exception atilmali');
    }
    
} catch (Exception $E) {
    $Test->assert(false, 'Validasyon testi basarisiz: ' . $E->getMessage());
}

// ---------------------------------------------
// Permission Atama Testleri
// ---------------------------------------------
echo "\n\033[33m[Permission Assignment Tests]\033[0m\n";

try {
    $Repo = new RoleRepository();
    $AuthService = AuthorizationService::getInstance();
    
    if ($TestRolId) {
        // Permissionlari getir
        $TumPermissionlar = $AuthService->tumPermissionlariGetir();
        
        if (!empty($TumPermissionlar)) {
            // Ilk 3 permissioni ata
            $AtanacakIdler = array_slice(array_column($TumPermissionlar, 'Id'), 0, 3);
            
            $Repo->rolePermissionAta($TestRolId, $AtanacakIdler, 1);
            $Test->assertTrue(true, 'Permission atama basarili olmali');
            
            // Atanan permissionlari kontrol et
            $RolPermissionlari = $Repo->rolPermissionlariGetir($TestRolId);
            $Test->assertEquals(count($AtanacakIdler), count($RolPermissionlari), 'Atanan permission sayisi dogru olmali');
        }
    }
    
} catch (Exception $E) {
    $Test->assert(false, 'Permission atama testi basarisiz: ' . $E->getMessage());
}

// ---------------------------------------------
// Kullanici-Rol Atama Testleri  
// ---------------------------------------------
echo "\n\033[33m[User-Role Assignment Tests]\033[0m\n";

try {
    $Repo = new RoleRepository();
    
    // Kullanici rolleri (UserId=1)
    $UserRoles = $Repo->kullaniciRolleriGetir(1);
    $Test->assertIsArray($UserRoles, 'kullaniciRolleriGetir array donmeli');
    
    // Olmayan kullanici
    $NoUserRoles = $Repo->kullaniciRolleriGetir(999999);
    $Test->assertEmpty($NoUserRoles, 'Olmayan kullanici icin bos array donmeli');
    
} catch (Exception $E) {
    $Test->assert(false, 'Kullanici-Rol atama testi basarisiz: ' . $E->getMessage());
}

// ---------------------------------------------
// Temizlik - Test Rolunu Sil
// ---------------------------------------------
echo "\n\033[33m[Cleanup]\033[0m\n";

try {
    if ($TestRolId) {
        $Repo = new RoleRepository();
        $Repo->rolSil($TestRolId, 1);
        
        $SilinenRol = $Repo->bul($TestRolId);
        $Test->assertNull($SilinenRol, 'Test rolu silinmeli');
    }
} catch (Exception $E) {
    echo "  \033[33mNote: Temizlik sirasinda hata: {$E->getMessage()}\033[0m\n";
}

// =============================================
// SONUC
// =============================================
$Test->summary();

exit($Test->isSuccess() ? 0 : 1);
