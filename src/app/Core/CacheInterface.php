<?php
/**
 * Cache Interface - önbellek soyutlama katmanı.
 * Tüm cache sürücüleri bu arayüzü implemente etmelidir.
 */

namespace App\Core;

interface CacheInterface
{
    /**
     * Anahtara göre değer getirir.
     *
     * @param string $Anahtar
     * @param mixed  $VarsayilanDeger Bulunamazsa döndürülecek değer
     * @return mixed
     */
    public function al(string $Anahtar, $VarsayilanDeger = null);

    /**
     * Önbelleğe yazma.
     *
     * @param string $Anahtar
     * @param mixed  $Deger
     * @param int    $Ttl Saniye cinsinden yaşam süresi (0 = sınırsız)
     * @return bool
     */
    public function kaydet(string $Anahtar, $Deger, int $Ttl = 3600): bool;

    /**
     * Anahtarı siler.
     *
     * @param string $Anahtar
     * @return bool
     */
    public function sil(string $Anahtar): bool;

    /**
     * Anahtarın var olup olmadığını kontrol eder.
     *
     * @param string $Anahtar
     * @return bool
     */
    public function varMi(string $Anahtar): bool;

    /**
     * Tüm cache'i temizler.
     *
     * @return bool
     */
    public function temizle(): bool;

    /**
     * "Remember" pattern — cache'te varsa döndür, yoksa callback'i çalıştır, kaydet, döndür.
     *
     * @param string   $Anahtar
     * @param int      $Ttl
     * @param callable $Callback Değer bulunamazsa çağrılacak fonksiyon
     * @return mixed
     */
    public function hatirla(string $Anahtar, int $Ttl, callable $Callback);
}
