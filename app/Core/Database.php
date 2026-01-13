<?php

namespace App\Core;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $Baglanti = null;

    public static function connection(): PDO
    {
        if (self::$Baglanti === null) {
            self::$Baglanti = self::baglantiOlustur();
        }
        return self::$Baglanti;
    }

    private static function baglantiOlustur(): PDO
    {
        $Host = config('db.host');
        $Port = config('db.port');
        $Veritabani = config('db.database');
        $Kullanici = config('db.username');
        $Parola = config('db.password');
        $GuvenliSertifika = config('db.trust_server_certificate') ? 'yes' : 'no';

        $Dsn = "sqlsrv:Server={$Host},{$Port};Database={$Veritabani};TrustServerCertificate={$GuvenliSertifika}";

        try {
            $Pdo = new PDO($Dsn, $Kullanici, $Parola, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            return $Pdo;
        } catch (PDOException $Hata) {
            throw new \RuntimeException('Veritabani baglantisi basarisiz: ' . $Hata->getMessage());
        }
    }
}
