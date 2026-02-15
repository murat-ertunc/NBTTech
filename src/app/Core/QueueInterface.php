<?php
/**
 * Queue Interface - kuyruk soyutlama katmanı.
 * Tüm kuyruk sürücüleri bu arayüzü implemente etmelidir.
 */

namespace App\Core;

interface QueueInterface
{
    /**
     * Kuyruğa mesaj ekler.
     *
     * @param string $KuyrukAdi Kuyruk adı
     * @param array  $Yukleme   Gönderilecek veri
     * @return bool
     */
    public function gonder(string $KuyrukAdi, array $Yukleme): bool;

    /**
     * Kuyruktan tek mesaj tüketir.
     *
     * @param string   $KuyrukAdi Kuyruk adı
     * @param callable $Isleyici  Mesaj işleyici fonksiyon (array $Yukleme): void
     * @return bool Mesaj işlendiyse true
     */
    public function tuket(string $KuyrukAdi, callable $Isleyici): bool;

    /**
     * Bağlantının aktif olup olmadığını döner.
     *
     * @return bool
     */
    public function bagliMi(): bool;
}
