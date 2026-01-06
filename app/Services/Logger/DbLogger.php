<?php

namespace App\Services\Logger;

use App\Core\Database;
use App\Models\BaseModel;
use PDO;

class DbLogger implements LoggerInterface
{
    private function db(): PDO
    {
        return Database::connection();
    }

    private function tablo(): string
    {
        return config('log.table', 'log_action');
    }

    public function log(array $Yukleme): void
    {
        $Veri = BaseModel::insertIcinStandartAlanlar($Yukleme);
        $Kolonlar = array_keys($Veri);
        $Tutucular = array_map(fn($K) => ':' . $K, $Kolonlar);
        $Sql = sprintf('INSERT INTO %s (%s) VALUES (%s)', $this->tablo(), implode(', ', $Kolonlar), implode(', ', $Tutucular));
        $Stmt = $this->db()->prepare($Sql);
        $Stmt->execute($Veri);
    }
}
