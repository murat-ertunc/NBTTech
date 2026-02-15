<?php
/**
 * Cache - Redis tabanlı önbellek soyutlama katmanı.
 * Mevcut Redis sınıfını CacheInterface üzerinden sarmalar.
 */

namespace App\Core;

class Cache implements CacheInterface
{
    private static ?Cache $Instance = null;
    private Redis $Redis;

    private function __construct()
    {
        $this->Redis = Redis::getInstance();
    }

    public static function getInstance(): self
    {
        if (self::$Instance === null) {
            self::$Instance = new self();
        }
        return self::$Instance;
    }

    public function al(string $Anahtar, $VarsayilanDeger = null)
    {
        return $this->Redis->al($Anahtar, $VarsayilanDeger);
    }

    public function kaydet(string $Anahtar, $Deger, int $Ttl = 3600): bool
    {
        return $this->Redis->kaydet($Anahtar, $Deger, $Ttl);
    }

    public function sil(string $Anahtar): bool
    {
        return $this->Redis->sil($Anahtar);
    }

    public function varMi(string $Anahtar): bool
    {
        $Deger = $this->Redis->al($Anahtar);
        return $Deger !== null;
    }

    public function temizle(): bool
    {
        try {
            $this->Redis->patternIleSil('*');
            return true;
        } catch (\Throwable $E) {
            error_log('[Cache] Temizleme hatası: ' . $E->getMessage());
            return false;
        }
    }

    public function hatirla(string $Anahtar, int $Ttl, callable $Callback)
    {
        $Deger = $this->al($Anahtar);
        if ($Deger !== null) {
            return $Deger;
        }

        $Deger = $Callback();

        if ($Deger !== null) {
            $this->kaydet($Anahtar, $Deger, $Ttl);
        }

        return $Deger;
    }

    private function __clone() {}

    public function __wakeup()
    {
        throw new \Exception("Singleton sınıfları unserialize edilemez.");
    }
}
