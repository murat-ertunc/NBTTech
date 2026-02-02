<?php

namespace App\Core;

class Redis
{

    private static ?Redis $Instance = null;

    private \Redis $Baglanti;

    private bool $Bagli = false;

    private const CACHE_PREFIX = 'nbt:';

    private const VARSAYILAN_TTL = 3600;

    private function __construct()
    {
        $this->baglan();
    }

    public static function getInstance(): self
    {
        if (self::$Instance === null) {
            self::$Instance = new self();
        }
        return self::$Instance;
    }

    private function baglan(): bool
    {
        if ($this->Bagli) {
            return true;
        }

        if (!class_exists('Redis')) {
            error_log('Redis PHP extension bulunamadi. Cache devre disi.');
            $this->Bagli = false;
            return false;
        }

        try {
            $Config = require CONFIG_PATH . 'redis.php';

            $this->Baglanti = new \Redis();
            $this->Bagli = $this->Baglanti->connect(
                $Config['host'],
                $Config['port'],
                2.0
            );

            if (!empty($Config['password'])) {
                $this->Baglanti->auth($Config['password']);
            }

            if (isset($Config['database'])) {
                $this->Baglanti->select($Config['database']);
            }

            return $this->Bagli;

        } catch (\Exception $E) {
            error_log("Redis baglanti hatasi: " . $E->getMessage());
            $this->Bagli = false;
            return false;
        }
    }

    public function bagliMi(): bool
    {
        if (!$this->Bagli) {
            return false;
        }

        try {
            return $this->Baglanti->ping() === true || $this->Baglanti->ping() === '+PONG';
        } catch (\Exception $E) {
            return false;
        }
    }

    private function anahtarOlustur(string $Anahtar): string
    {
        return self::CACHE_PREFIX . $Anahtar;
    }

    public function al(string $Anahtar, $VarsayilanDeger = null)
    {
        if (!$this->bagliMi()) {
            return $VarsayilanDeger;
        }

        try {
            $Deger = $this->Baglanti->get($this->anahtarOlustur($Anahtar));

            if ($Deger === false) {
                return $VarsayilanDeger;
            }

            $Decoded = json_decode($Deger, true);
            return $Decoded !== null ? $Decoded : $Deger;

        } catch (\Exception $E) {
            error_log("Redis get hatasi: " . $E->getMessage());
            return $VarsayilanDeger;
        }
    }

    public function kaydet(string $Anahtar, $Deger, int $Ttl = self::VARSAYILAN_TTL): bool
    {
        if (!$this->bagliMi()) {
            return false;
        }

        try {
            $CacheAnahtar = $this->anahtarOlustur($Anahtar);

            if (is_array($Deger) || is_object($Deger)) {
                $Deger = json_encode($Deger, JSON_UNESCAPED_UNICODE);
            }

            if ($Ttl > 0) {
                return $this->Baglanti->setex($CacheAnahtar, $Ttl, $Deger);
            }

            return $this->Baglanti->set($CacheAnahtar, $Deger);

        } catch (\Exception $E) {
            error_log("Redis set hatasi: " . $E->getMessage());
            return false;
        }
    }

    public function sil(string $Anahtar): bool
    {
        if (!$this->bagliMi()) {
            return false;
        }

        try {
            return $this->Baglanti->del($this->anahtarOlustur($Anahtar)) > 0;
        } catch (\Exception $E) {
            error_log("Redis del hatasi: " . $E->getMessage());
            return false;
        }
    }

    public function patternIleSil(string $Pattern): int
    {
        if (!$this->bagliMi()) {
            return 0;
        }

        try {
            $FullPattern = $this->anahtarOlustur($Pattern);
            $Anahtarlar = $this->Baglanti->keys($FullPattern);

            if (empty($Anahtarlar)) {
                return 0;
            }

            return $this->Baglanti->del($Anahtarlar);

        } catch (\Exception $E) {
            error_log("Redis pattern delete hatasi: " . $E->getMessage());
            return 0;
        }
    }

    public function varMi(string $Anahtar): bool
    {
        if (!$this->bagliMi()) {
            return false;
        }

        try {
            return $this->Baglanti->exists($this->anahtarOlustur($Anahtar)) > 0;
        } catch (\Exception $E) {
            return false;
        }
    }

    public function kalanSure(string $Anahtar): int
    {
        if (!$this->bagliMi()) {
            return -2;
        }

        try {
            return $this->Baglanti->ttl($this->anahtarOlustur($Anahtar));
        } catch (\Exception $E) {
            return -2;
        }
    }

    public function hashKaydet(string $Anahtar, array $Degerler, int $Ttl = self::VARSAYILAN_TTL): bool
    {
        if (!$this->bagliMi()) {
            return false;
        }

        try {
            $CacheAnahtar = $this->anahtarOlustur($Anahtar);

            foreach ($Degerler as $Alan => $Deger) {
                if (is_array($Deger) || is_object($Deger)) {
                    $Degerler[$Alan] = json_encode($Deger, JSON_UNESCAPED_UNICODE);
                }
            }

            $Sonuc = $this->Baglanti->hMSet($CacheAnahtar, $Degerler);

            if ($Sonuc && $Ttl > 0) {
                $this->Baglanti->expire($CacheAnahtar, $Ttl);
            }

            return $Sonuc;

        } catch (\Exception $E) {
            error_log("Redis hMSet hatasi: " . $E->getMessage());
            return false;
        }
    }

    public function hashAl(string $Anahtar): array
    {
        if (!$this->bagliMi()) {
            return [];
        }

        try {
            $Degerler = $this->Baglanti->hGetAll($this->anahtarOlustur($Anahtar));

            if (!is_array($Degerler)) {
                return [];
            }

            foreach ($Degerler as $Alan => $Deger) {
                $Decoded = json_decode($Deger, true);
                if ($Decoded !== null) {
                    $Degerler[$Alan] = $Decoded;
                }
            }

            return $Degerler;

        } catch (\Exception $E) {
            error_log("Redis hGetAll hatasi: " . $E->getMessage());
            return [];
        }
    }

    public function setEkle(string $Anahtar, ...$Uyeler): int
    {
        if (!$this->bagliMi()) {
            return 0;
        }

        try {
            return $this->Baglanti->sAdd($this->anahtarOlustur($Anahtar), ...$Uyeler);
        } catch (\Exception $E) {
            error_log("Redis sAdd hatasi: " . $E->getMessage());
            return 0;
        }
    }

    public function setAl(string $Anahtar): array
    {
        if (!$this->bagliMi()) {
            return [];
        }

        try {
            $Sonuc = $this->Baglanti->sMembers($this->anahtarOlustur($Anahtar));
            return is_array($Sonuc) ? $Sonuc : [];
        } catch (\Exception $E) {
            error_log("Redis sMembers hatasi: " . $E->getMessage());
            return [];
        }
    }

    public function setIcindeMi(string $Anahtar, string $Uye): bool
    {
        if (!$this->bagliMi()) {
            return false;
        }

        try {
            return $this->Baglanti->sIsMember($this->anahtarOlustur($Anahtar), $Uye);
        } catch (\Exception $E) {
            return false;
        }
    }

    public function artir(string $Anahtar, int $Miktar = 1): int
    {
        if (!$this->bagliMi()) {
            return 0;
        }

        try {
            return $this->Baglanti->incrBy($this->anahtarOlustur($Anahtar), $Miktar);
        } catch (\Exception $E) {
            return 0;
        }
    }

    public function temizle(): bool
    {
        if (!$this->bagliMi()) {
            return false;
        }

        try {

            return $this->patternIleSil('*') >= 0;
        } catch (\Exception $E) {
            return false;
        }
    }

    public function kapat(): void
    {
        if ($this->Bagli) {
            try {
                $this->Baglanti->close();
            } catch (\Exception $E) {

            }
            $this->Bagli = false;
        }
    }

    public function __destruct()
    {
        $this->kapat();
    }

    private function __clone() {}

    public function __wakeup()
    {
        throw new \Exception("Singleton siniflari unserialize edilemez.");
    }
}
