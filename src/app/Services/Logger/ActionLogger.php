<?php

namespace App\Services\Logger;

use App\Core\Context;

/**
 * Islem Loglayici
 * 
 * Tum CRUD islemlerini log_action tablosuna kaydeder.
 * Her islem mutlaka loglanmalidir.
 */
class ActionLogger
{
    /**
     * INSERT islemi logla
     * 
     * @param string $Tablo Tablo adi
     * @param array $Kimlik Kayit kimligi (Id, Guid vb.)
     * @param array $Veri Eklenen veri
     */
    public static function insert(string $Tablo, array $Kimlik, array $Veri): void
    {
        self::logla('CREATE', $Tablo, [
            'Kimlik' => $Kimlik,
            'Veri' => $Veri
        ]);
    }

    /**
     * UPDATE islemi logla
     * 
     * @param string $Tablo Tablo adi
     * @param array $Filtreler Guncellenen kayit filtreleri
     * @param array $Degisiklikler Degisen alanlar
     */
    public static function update(string $Tablo, array $Filtreler, array $Degisiklikler): void
    {
        self::logla('UPDATE', $Tablo, [
            'Filtreler' => $Filtreler,
            'Degisiklikler' => $Degisiklikler
        ]);
    }

    /**
     * DELETE (soft delete) islemi logla
     * 
     * @param string $Tablo Tablo adi
     * @param array $Filtreler Silinen kayit filtreleri
     * @param string $Aciklama Ek aciklama
     */
    public static function delete(string $Tablo, array $Filtreler, string $Aciklama = ''): void
    {
        self::logla('DELETE', $Tablo, [
            'Filtreler' => $Filtreler,
            'Aciklama' => $Aciklama
        ]);
    }

    /**
     * SELECT islemi logla
     * 
     * @param string $Tablo Tablo adi
     * @param array $Filtreler Sorgu filtreleri
     * @param int $Adet Donen kayit sayisi
     * @param array $VeriSeti Donen veriler (opsiyonel)
     */
    public static function select(string $Tablo, array $Filtreler, int $Adet, array $VeriSeti = []): void
    {
        self::logla('SELECT', $Tablo, [
            'Filtreler' => $Filtreler,
            'Adet' => $Adet
        ]);
    }

    /**
     * Genel loglama metodu
     * 
     * @param string $Islem Islem tipi (CREATE, UPDATE, DELETE, SELECT)
     * @param string $Tablo Tablo adi
     * @param array $Yukleme Log verisi
     * @param string $Sonuc Islem sonucu
     */
    public static function logla(string $Islem, string $Tablo, array $Yukleme = [], string $Sonuc = 'ok'): void
    {
        $Logger = logger();
        $Logger->log(self::veriHazirla($Islem, $Tablo, $Yukleme, $Sonuc));
    }

    /**
     * Log verisini hazirla
     */
    private static function veriHazirla(string $Islem, string $Tablo, array $Yukleme, string $Sonuc): array
    {
        return [
            'Islem' => $Islem,
            'Tablo' => $Tablo,
            'IpAdresi' => Context::ipAdresi(),
            'Veri' => json_encode([
                'Yukleme' => $Yukleme,
                'Sonuc' => $Sonuc,
            ], JSON_UNESCAPED_UNICODE),
        ];
    }
}
