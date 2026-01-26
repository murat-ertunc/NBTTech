<?php

namespace App\Core;

class Config
{
    private static array $Onbellek = [];

    public static function get(string $Anahtar, $Varsayilan = null)
    {
        [$Dosya, $AltAnahtar] = self::anahtarParcala($Anahtar);
        if (!isset(self::$Onbellek[$Dosya])) {
            // CONFIG_PATH sabiti bootstrap'ta tanımlı
            $Yol = CONFIG_PATH . $Dosya . '.php';
            self::$Onbellek[$Dosya] = file_exists($Yol) ? require $Yol : [];
        }

        $Ayar = self::$Onbellek[$Dosya];
        if ($AltAnahtar === null) {
            return $Ayar ?: $Varsayilan;
        }

        foreach ($AltAnahtar as $Parca) {
            if (!is_array($Ayar) || !array_key_exists($Parca, $Ayar)) {
                return $Varsayilan;
            }
            $Ayar = $Ayar[$Parca];
        }

        return $Ayar;
    }

    private static function anahtarParcala(string $Anahtar): array
    {
        $Parcalar = explode('.', $Anahtar);
        $Dosya = array_shift($Parcalar);
        return [$Dosya, $Parcalar ?: null];
    }
}
