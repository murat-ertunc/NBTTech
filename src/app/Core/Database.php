<?php

namespace App\Core;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $Baglanti = null;
    private static ?Database $instance = null;
    private PDO $pdo;

    private function __construct()
    {
        $this->pdo = self::connection();
    }

    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    


    public function getConnection(): PDO
    {
        return $this->pdo;
    }

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
        $Sifreli = config('db.encrypt') ? 'yes' : 'no';
        $GuvenliSertifika = config('db.trust_server_certificate') ? 'yes' : 'no';

        $Dsn = "sqlsrv:Server={$Host},{$Port};Database={$Veritabani};Encrypt={$Sifreli};TrustServerCertificate={$GuvenliSertifika}";

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

    


    public function execute(string $sql, array $params = []): bool
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Database execute error: " . $e->getMessage() . " SQL: " . $sql);
            return false;
        }
    }

    


    public function fetchOne(string $sql, array $params = []): ?array
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (PDOException $e) {
            error_log("Database fetchOne error: " . $e->getMessage() . " SQL: " . $sql);
            return null;
        }
    }

    


    public function fetchAll(string $sql, array $params = []): array
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database fetchAll error: " . $e->getMessage() . " SQL: " . $sql);
            return [];
        }
    }

    


    public function lastInsertId(): ?int
    {
        $result = $this->fetchOne("SELECT SCOPE_IDENTITY() as id");
        return $result ? (int)$result['id'] : null;
    }
}
