<?php

namespace App\Core;

use PDO;
use Throwable;

class Transaction
{
    public static function begin(): void
    {
        Database::connection()->beginTransaction();
    }

    public static function commit(): void
    {
        Database::connection()->commit();
    }

    public static function rollBack(): void
    {
        $Pdo = Database::connection();
        if ($Pdo->inTransaction()) {
            $Pdo->rollBack();
        }
    }

    public static function wrap(callable $Fonksiyon)
    {
        $Pdo = Database::connection();
        $MevcutTransaction = $Pdo->inTransaction();

        try {
            if (!$MevcutTransaction) {
                $Pdo->beginTransaction();
            }

            $Sonuc = $Fonksiyon($Pdo);

            if (!$MevcutTransaction) {
                $Pdo->commit();
            }

            return $Sonuc;
        } catch (Throwable $Hata) {
            if ($Pdo->inTransaction() && !$MevcutTransaction) {
                $Pdo->rollBack();
            }
            throw $Hata;
        }
    }
}
