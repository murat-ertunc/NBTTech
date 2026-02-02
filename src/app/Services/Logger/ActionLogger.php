<?php

namespace App\Services\Logger;

use App\Core\Context;







class ActionLogger
{
    






    public static function insert(string $Tablo, array $Kimlik, array $Veri): void
    {
        self::logla('CREATE', $Tablo, [
            'Kimlik' => $Kimlik,
            'Veri' => $Veri
        ]);
    }

    






    public static function update(string $Tablo, array $Filtreler, array $Degisiklikler): void
    {
        self::logla('UPDATE', $Tablo, [
            'Filtreler' => $Filtreler,
            'Degisiklikler' => $Degisiklikler
        ]);
    }

    






    public static function delete(string $Tablo, array $Filtreler, string $Aciklama = ''): void
    {
        self::logla('DELETE', $Tablo, [
            'Filtreler' => $Filtreler,
            'Aciklama' => $Aciklama
        ]);
    }

    







    public static function select(string $Tablo, array $Filtreler, int $Adet, array $VeriSeti = []): void
    {
        self::logla('SELECT', $Tablo, [
            'Filtreler' => $Filtreler,
            'Adet' => $Adet
        ]);
    }

    







    public static function logla(string $Islem, string $Tablo, array $Yukleme = [], string $Sonuc = 'ok'): void
    {
        $Logger = logger();
        $Logger->log(self::veriHazirla($Islem, $Tablo, $Yukleme, $Sonuc));
    }

    






    public static function error(string $Kaynak, string $Mesaj, array $Ek = []): void
    {
        self::logla('ERROR', 'system', [
            'Kaynak' => $Kaynak,
            'Mesaj' => $Mesaj,
            'Ek' => $Ek,
        ], 'fail');
    }

    


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
