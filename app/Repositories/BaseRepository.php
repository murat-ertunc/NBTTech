<?php

namespace App\Repositories;

use App\Core\Database;
use App\Core\Transaction;
use App\Models\BaseModel;
use App\Services\Logger\ActionLogger;
use PDO;

class BaseRepository
{
    protected string $Tablo;
    private ?PDO $DbInstance = null;

    public function __get($Isim)
    {
        if ($Isim === 'Db') {
            if ($this->DbInstance === null) {
                $this->DbInstance = Database::connection();
            }
            return $this->DbInstance;
        }
        return null;
    }

    public function tumAktifler(): array
    {
        $Stmt = $this->Db->query("SELECT * FROM {$this->Tablo} WHERE Sil = 0");
        $Sonuclar = $Stmt->fetchAll();
        $this->logSelect(['Sil' => 0], $Sonuclar);
        return $Sonuclar;
    }

    public function bul(int $Id): ?array
    {
        $Stmt = $this->Db->prepare("SELECT * FROM {$this->Tablo} WHERE Id = :Id AND Sil = 0");
        $Stmt->execute(['Id' => $Id]);
        $Sonuc = $Stmt->fetch();
        $this->logSelect(['Id' => $Id, 'Sil' => 0], $Sonuc ? [$Sonuc] : []);
        return $Sonuc ?: null;
    }

    public function yedekle(int $Id, string $YedekTablo, ?int $KullaniciId = null): void
    {
        $Kayit = $this->bul($Id);
        if (!$Kayit) {
            return;
        }
        // Orijinal Id'yi KaynakId olarak sakla, yeni tablo icin Id'yi kaldir
        $KaynakId = $Kayit['Id'];
        unset($Kayit['Id']);
        $Kayit['KaynakId'] = $KaynakId;
        $Kayit['BackupZamani'] = date('Y-m-d H:i:s');
        $Kayit['BackupUserId'] = $KullaniciId;

        $Kolonlar = array_keys($Kayit);
        $Tutucular = array_map(fn($K) => ':' . $K, $Kolonlar);
        $Sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $YedekTablo,
            implode(', ', $Kolonlar),
            implode(', ', $Tutucular)
        );
        Transaction::wrap(function () use ($Sql, $Kayit) {
            $Stmt = $this->Db->prepare($Sql);
            $Stmt->execute($Kayit);
        });
    }

    public function ekle(array $Veri, ?int $KullaniciId = null): int
    {
        $Yukleme = BaseModel::insertIcinStandartAlanlar($Veri, $KullaniciId);
        $Kolonlar = array_keys($Yukleme);
        $Tutucular = array_map(fn($K) => ':' . $K, $Kolonlar);
        $Sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $this->Tablo,
            implode(', ', $Kolonlar),
            implode(', ', $Tutucular)
        );

        return Transaction::wrap(function () use ($Sql, $Yukleme) {
            $Stmt = $this->Db->prepare($Sql);
            $Stmt->execute($Yukleme);
            return (int) $this->Db->lastInsertId();
        });
    }

    public function guncelle(int $Id, array $Veri, ?int $KullaniciId = null, array $EkKosul = []): void
    {
        $Yukleme = BaseModel::updateIcinStandartAlanlar($Veri, $KullaniciId);
        $Yukleme['Id'] = $Id;
        $Kolonlar = array_merge(array_keys($Veri), ['DegisiklikZamani', 'DegistirenUserId']);
        $SetParcalari = array_map(fn($K) => "$K = :$K", $Kolonlar);
        $SetSql = implode(', ', $SetParcalari);
        $WhereParcalari = ['Id = :Id'];
        foreach ($EkKosul as $Anahtar => $Deger) {
            $Tutucu = 'w_' . $Anahtar;
            $Yukleme[$Tutucu] = $Deger;
            $WhereParcalari[] = "$Anahtar = :$Tutucu";
        }
        $Sql = "UPDATE {$this->Tablo} SET {$SetSql} WHERE " . implode(' AND ', $WhereParcalari);

        Transaction::wrap(function () use ($Sql, $Yukleme) {
            $Stmt = $this->Db->prepare($Sql);
            $Stmt->execute($Yukleme);
        });
    }

    public function softSil(int $Id, ?int $KullaniciId = null, array $EkKosul = []): void
    {
        $StandartAlanlar = BaseModel::softDeleteIcinStandartAlanlar($KullaniciId);
        $SetParcalari = [];
        $Yukleme = ['Id' => $Id];
        foreach ($StandartAlanlar as $Anahtar => $Deger) {
            $SetParcalari[] = "$Anahtar = :$Anahtar";
            $Yukleme[$Anahtar] = $Deger;
        }
        $WhereParcalari = ['Id = :Id'];
        foreach ($EkKosul as $Anahtar => $Deger) {
            $Tutucu = 'w_' . $Anahtar;
            $Yukleme[$Tutucu] = $Deger;
            $WhereParcalari[] = "$Anahtar = :$Tutucu";
        }
        $Sql = "UPDATE {$this->Tablo} SET " . implode(', ', $SetParcalari) . " WHERE " . implode(' AND ', $WhereParcalari);

        Transaction::wrap(function () use ($Sql, $Yukleme) {
            $Stmt = $this->Db->prepare($Sql);
            $Stmt->execute($Yukleme);
        });
    }

    protected function logSelect(array $Filtreler, array $VeriSeti): void
    {
        $TemizVeri = array_map(fn($Satir) => $this->sanitizeRow($Satir), $VeriSeti);
        ActionLogger::select($this->Tablo, $Filtreler, count($VeriSeti), $TemizVeri);
    }

    protected function sanitizeRow(array $Satir): array
    {
        return $Satir;
    }
}
