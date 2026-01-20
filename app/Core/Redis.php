<?php

namespace App\Core;

/**
 * Redis Baglanti ve Cache Yoneticisi
 * 
 * Singleton pattern ile tek bir Redis baglantisi yonetir.
 * Permission cache, session cache gibi islemler icin kullanilir.
 * 
 * @package App\Core
 */
class Redis
{
    /** @var \Redis|null Singleton instance */
    private static ?Redis $Instance = null;
    
    /** @var \Redis Redis baglantisi */
    private \Redis $Baglanti;
    
    /** @var bool Baglanti durumu */
    private bool $Bagli = false;
    
    /** @var string Cache prefix */
    private const CACHE_PREFIX = 'nbt:';
    
    /** @var int Varsayilan TTL (1 saat) */
    private const VARSAYILAN_TTL = 3600;
    
    /**
     * Private constructor - Singleton pattern
     */
    private function __construct()
    {
        $this->baglan();
    }
    
    /**
     * Singleton instance dondurur
     * 
     * @return self
     */
    public static function getInstance(): self
    {
        if (self::$Instance === null) {
            self::$Instance = new self();
        }
        return self::$Instance;
    }
    
    /**
     * Redis'e baglanir
     * 
     * @return bool Baglanti basarili mi
     */
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
            $Config = require ROOT_PATH . '/config/redis.php';
            
            $this->Baglanti = new \Redis();
            $this->Bagli = $this->Baglanti->connect(
                $Config['host'],
                $Config['port'],
                2.0 // timeout
            );
            
            // Auth varsa
            if (!empty($Config['password'])) {
                $this->Baglanti->auth($Config['password']);
            }
            
            // Database secimi
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
    
    /**
     * Baglanti durumunu kontrol eder
     * 
     * @return bool
     */
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
    
    /**
     * Cache key olusturur (prefix ekler)
     * 
     * @param string $Anahtar
     * @return string
     */
    private function anahtarOlustur(string $Anahtar): string
    {
        return self::CACHE_PREFIX . $Anahtar;
    }
    
    /**
     * Deger alir
     * 
     * @param string $Anahtar
     * @param mixed $VarsayilanDeger
     * @return mixed
     */
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
            
            // JSON decode dene
            $Decoded = json_decode($Deger, true);
            return $Decoded !== null ? $Decoded : $Deger;
            
        } catch (\Exception $E) {
            error_log("Redis get hatasi: " . $E->getMessage());
            return $VarsayilanDeger;
        }
    }
    
    /**
     * Deger kaydeder
     * 
     * @param string $Anahtar
     * @param mixed $Deger
     * @param int $Ttl Saniye cinsinden sure (0 = suresiz)
     * @return bool
     */
    public function kaydet(string $Anahtar, $Deger, int $Ttl = self::VARSAYILAN_TTL): bool
    {
        if (!$this->bagliMi()) {
            return false;
        }
        
        try {
            $CacheAnahtar = $this->anahtarOlustur($Anahtar);
            
            // Array veya object ise JSON'a cevir
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
    
    /**
     * Anahtar siler
     * 
     * @param string $Anahtar
     * @return bool
     */
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
    
    /**
     * Pattern ile anahtarlari siler
     * 
     * @param string $Pattern Ornek: "user:*:permissions"
     * @return int Silinen anahtar sayisi
     */
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
    
    /**
     * Anahtar var mi kontrol eder
     * 
     * @param string $Anahtar
     * @return bool
     */
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
    
    /**
     * Kalan TTL'i dondurur
     * 
     * @param string $Anahtar
     * @return int -2: anahtar yok, -1: suresiz, >=0: kalan saniye
     */
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
    
    /**
     * Hash set (birden fazla alan icin)
     * 
     * @param string $Anahtar
     * @param array $Degerler ['alan1' => 'deger1', 'alan2' => 'deger2']
     * @param int $Ttl
     * @return bool
     */
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
    
    /**
     * Hash get (tum alanlari alir)
     * 
     * @param string $Anahtar
     * @return array
     */
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
            
            // JSON decode dene
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
    
    /**
     * Set'e eleman ekler
     * 
     * @param string $Anahtar
     * @param mixed ...$Uyeler
     * @return int Eklenen eleman sayisi
     */
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
    
    /**
     * Set'teki tum elemanlari alir
     * 
     * @param string $Anahtar
     * @return array
     */
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
    
    /**
     * Set'te eleman var mi kontrol eder
     * 
     * @param string $Anahtar
     * @param string $Uye
     * @return bool
     */
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
    
    /**
     * Increment (sayac artirma)
     * 
     * @param string $Anahtar
     * @param int $Miktar
     * @return int Yeni deger
     */
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
    
    /**
     * Tum cache'i temizler (dikkatli kullan!)
     * 
     * @return bool
     */
    public function temizle(): bool
    {
        if (!$this->bagliMi()) {
            return false;
        }
        
        try {
            // Sadece bu uygulamanin prefix'i ile baslayan anahtarlari sil
            return $this->patternIleSil('*') >= 0;
        } catch (\Exception $E) {
            return false;
        }
    }
    
    /**
     * Baglantıyı kapatir
     */
    public function kapat(): void
    {
        if ($this->Bagli) {
            try {
                $this->Baglanti->close();
            } catch (\Exception $E) {
                // Ignore
            }
            $this->Bagli = false;
        }
    }
    
    /**
     * Destructor
     */
    public function __destruct()
    {
        $this->kapat();
    }
    
    /**
     * Clone engelleme (Singleton)
     */
    private function __clone() {}
    
    /**
     * Wakeup engelleme (Singleton)
     */
    public function __wakeup()
    {
        throw new \Exception("Singleton siniflari unserialize edilemez.");
    }
}
