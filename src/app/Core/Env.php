<?php

namespace App\Core;

class Env
{
    private static bool $Yuklendi = false;
    private static array $Veriler = [];

    public static function load(string $Yol): void
    {
        if (self::$Yuklendi || !is_readable($Yol)) {
            return;
        }

        $Satirlar = file($Yol, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($Satirlar as $Satir) {
            $Satir = trim($Satir);
            if ($Satir === '' || strpos($Satir, '#') === 0) {
                continue;
            }
            [$Anahtar, $Deger] = array_map('trim', explode('=', $Satir, 2) + [null, null]);
            if ($Anahtar !== null) {
                self::$Veriler[$Anahtar] = $Deger;
            }
        }

        self::$Yuklendi = true;
    }

    public static function get(string $Anahtar, $Varsayilan = null)
    {
        if (array_key_exists($Anahtar, self::$Veriler)) {
            return self::$Veriler[$Anahtar];
        }
        if (array_key_exists($Anahtar, $_ENV)) {
            return $_ENV[$Anahtar];
        }
        if (array_key_exists($Anahtar, $_SERVER)) {
            return $_SERVER[$Anahtar];
        }
        return $Varsayilan;
    }
}
