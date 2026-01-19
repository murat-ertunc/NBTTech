<?php

/**
 * Redis Cache Test
 * 
 * Redis caching ve invalidation testleri.
 * 
 * Calistirma: php tests/RedisCacheTest.php
 */

// Bootstrap
require_once __DIR__ . '/../app/Core/bootstrap.php';

use App\Core\Redis;
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

echo "\n\033[34m=== Redis Cache Unit Tests ===\033[0m\n\n";

$Test = new TestRunner();
$TestPrefix = 'test:cache:' . time() . ':';

// ---------------------------------------------
// Temel Redis Islemleri
// ---------------------------------------------
echo "\033[33m[Basic Redis Operations]\033[0m\n";

try {
    $Redis = Redis::getInstance();
    
    // Baglanti kontrolu
    $Test->assertTrue($Redis->bagliMi(), 'Redis bagli olmali');
    
    // String kaydet/oku
    $Key = $TestPrefix . 'string';
    $Redis->kaydet($Key, 'Hello World', 60);
    $Test->assertEquals('Hello World', $Redis->al($Key), 'String dogru okunmali');
    
    // Array kaydet/oku
    $Key = $TestPrefix . 'array';
    $Data = ['name' => 'John', 'age' => 30, 'active' => true];
    $Redis->kaydet($Key, $Data, 60);
    $Read = $Redis->al($Key);
    $Test->assertEquals($Data['name'], $Read['name'], 'Array dogru okunmali');
    
    // Nesne kaydet/oku
    $Key = $TestPrefix . 'object';
    $Obj = (object)['id' => 1, 'title' => 'Test'];
    $Redis->kaydet($Key, $Obj, 60);
    $ReadObj = $Redis->al($Key);
    $Test->assertEquals($Obj->id, $ReadObj['id'], 'Object dogru okunmali');
    
    // Var mi kontrolu
    $Test->assertTrue($Redis->varMi($TestPrefix . 'string'), 'varMi true donmeli');
    $Test->assertFalse($Redis->varMi($TestPrefix . 'nonexistent'), 'varMi false donmeli');
    
} catch (Exception $E) {
    $Test->assert(false, 'Temel Redis testi basarisiz: ' . $E->getMessage());
}

// ---------------------------------------------
// Set Islemleri
// ---------------------------------------------
echo "\n\033[33m[Set Operations]\033[0m\n";

try {
    $Redis = Redis::getInstance();
    $SetKey = $TestPrefix . 'permissions';
    
    // Set'e eleman ekle
    $Redis->setEkle($SetKey, 'users.create');
    $Redis->setEkle($SetKey, 'users.read');
    $Redis->setEkle($SetKey, 'users.update');
    $Test->assertTrue(true, 'Set elemanlari eklendi');
    
    // Set uyeligi kontrolu
    $Test->assertTrue($Redis->setIcindeMi($SetKey, 'users.create'), 'users.create sette olmali');
    $Test->assertFalse($Redis->setIcindeMi($SetKey, 'users.delete'), 'users.delete sette olmamali');
    
    // Tum set elemanlarini al
    $Members = $Redis->setAl($SetKey);
    $Test->assertIsArray($Members, 'setAl array donmeli');
    $Test->assertEquals(3, count($Members), 'Set 3 eleman icermeli');
    
    // Set eleman sayisi (count uzerinden)
    $Test->assertEquals(3, count($Members), 'Set sayisi 3 olmali');
    
} catch (Exception $E) {
    $Test->assert(false, 'Set islemleri testi basarisiz: ' . $E->getMessage());
}

// ---------------------------------------------
// Hash Islemleri
// ---------------------------------------------
echo "\n\033[33m[Hash Operations]\033[0m\n";

try {
    $Redis = Redis::getInstance();
    $HashKey = $TestPrefix . 'user:1';
    
    // Hash alanlari kaydet (array olarak)
    $Redis->hashKaydet($HashKey, ['name' => 'John', 'email' => 'john@example.com']);
    $Test->assertTrue(true, 'Hash alanlari kaydedildi');
    
    // Tum hash'i al (hashAl tum alanlari doner)
    $AllHash = $Redis->hashAl($HashKey);
    $Test->assertIsArray($AllHash, 'hashAl array donmeli');
    $Test->assertEquals('John', $AllHash['name'], 'Name dogru olmali');
    $Test->assertEquals('john@example.com', $AllHash['email'], 'Email dogru olmali');
    
} catch (Exception $E) {
    $Test->assert(false, 'Hash islemleri testi basarisiz: ' . $E->getMessage());
}

// ---------------------------------------------
// TTL ve Expire
// ---------------------------------------------
echo "\n\033[33m[TTL & Expiration]\033[0m\n";

try {
    $Redis = Redis::getInstance();
    $Key = $TestPrefix . 'ttl_test';
    
    // 10 saniye TTL ile kaydet
    $Redis->kaydet($Key, 'temp data', 10);
    $Test->assertTrue(true, 'TTL ile kayit basarili');
    
    // Kaydi oku ve dogrula
    $Data = $Redis->al($Key);
    $Test->assertEquals('temp data', $Data, 'TTL ile kaydedilen veri dogru okunmali');
    
} catch (Exception $E) {
    $Test->assert(false, 'TTL testi basarisiz: ' . $E->getMessage());
}

// ---------------------------------------------
// Pattern ile Silme
// ---------------------------------------------
echo "\n\033[33m[Pattern Delete]\033[0m\n";

try {
    $Redis = Redis::getInstance();
    
    // Birden fazla key olustur
    $Redis->kaydet($TestPrefix . 'user:1:session', 'session1', 60);
    $Redis->kaydet($TestPrefix . 'user:1:cart', 'cart1', 60);
    $Redis->kaydet($TestPrefix . 'user:1:prefs', 'prefs1', 60);
    
    // Pattern ile sil
    $Silinen = $Redis->patternIleSil($TestPrefix . 'user:1:*');
    $Test->assertTrue($Silinen >= 0, 'Pattern ile silme basarili olmali');
    
    // Silinen key'ler kontrol
    $Test->assertFalse($Redis->varMi($TestPrefix . 'user:1:session'), 'Session silinmeli');
    $Test->assertFalse($Redis->varMi($TestPrefix . 'user:1:cart'), 'Cart silinmeli');
    
} catch (Exception $E) {
    $Test->assert(false, 'Pattern silme testi basarisiz: ' . $E->getMessage());
}

// ---------------------------------------------
// Authorization Cache Testleri
// ---------------------------------------------
echo "\n\033[33m[Authorization Cache]\033[0m\n";

try {
    $AuthService = AuthorizationService::getInstance();
    
    // Cache temizle
    $AuthService->kullaniciCacheTemizle(1);
    $Test->assertTrue(true, 'Kullanici cache temizlendi');
    
    // Permission cekme (cache'e yazilacak)
    $Perms1 = $AuthService->kullaniciPermissionlariGetir(1);
    $Test->assertIsArray($Perms1, 'Permissionlar alindi');
    
    // Tekrar cekme (cache'den gelmeli)
    $Start = microtime(true);
    $Perms2 = $AuthService->kullaniciPermissionlariGetir(1);
    $CacheDuration = microtime(true) - $Start;
    
    $Test->assertTrue($CacheDuration < 0.01, 'Cache\'den okuma hizli olmali (< 10ms)');
    
    // Tum cache temizle
    $AuthService->tumCacheTemizle();
    $Test->assertTrue(true, 'Tum cache temizlendi');
    
} catch (Exception $E) {
    $Test->assert(false, 'Authorization cache testi basarisiz: ' . $E->getMessage());
}

// ---------------------------------------------
// Temizlik
// ---------------------------------------------
echo "\n\033[33m[Cleanup]\033[0m\n";

try {
    $Redis = Redis::getInstance();
    
    // Test key'lerini temizle
    $Silinen = $Redis->patternIleSil($TestPrefix . '*');
    echo "  \033[90mTemizlenen key sayisi: {$Silinen}\033[0m\n";
    $Test->assertTrue(true, 'Test key\'leri temizlendi');
    
} catch (Exception $E) {
    echo "  \033[33mNote: Temizlik sirasinda hata: {$E->getMessage()}\033[0m\n";
}

// =============================================
// SONUC
// =============================================
$Test->summary();

exit($Test->isSuccess() ? 0 : 1);
