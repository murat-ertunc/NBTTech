<?php

namespace App\Services\Logger;

use App\Core\Context;

class ActionLogger
{
    public static function insert(string $Varlik, array $Veri): void
    {
        self::logla('insert', $Varlik, ['Veri' => $Veri]);
    }

    public static function update(string $Varlik, array $Filtreler, array $Degisiklikler): void
    {
        self::logla('update', $Varlik, ['Filtreler' => $Filtreler, 'Degisiklikler' => $Degisiklikler]);
    }

    public static function delete(string $Varlik, array $Filtreler): void
    {
        self::logla('delete', $Varlik, ['Filtreler' => $Filtreler]);
    }

    public static function select(string $Varlik, array $Filtreler, int $Adet, array $VeriSeti = []): void
    {
        self::logla('select', $Varlik, ['Filtreler' => $Filtreler, 'Adet' => $Adet, 'VeriSeti' => $VeriSeti]);
    }

    public static function logla(string $Islem, string $Varlik, array $Yukleme = [], string $Sonuc = 'ok'): void
    {
        $Logger = logger();
        $Logger->log(self::veriHazirla($Islem, $Varlik, $Yukleme, $Sonuc));
    }

    private static function veriHazirla(string $Islem, string $Varlik, array $Yukleme, string $Sonuc): array
    {
        return [
            'Islem' => $Islem,
            'Varlik' => $Varlik,
            'IpAdresi' => Context::ipAdresi(),
            'Veri' => json_encode([
                'Yukleme' => $Yukleme,
                'Sonuc' => $Sonuc,
            ], JSON_UNESCAPED_UNICODE),
        ];
    }
}
