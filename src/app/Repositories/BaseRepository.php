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

    



    protected function paginatedQuery(string $BaseSql, array $Parametreler = [], int $Sayfa = 1, int $Limit = 10): array
    {
        $SayimBaseSql = preg_replace('/\s+ORDER\s+BY\s+[\w\s,\.]+(?:ASC|DESC)?(?:\s*,\s*[\w\.]+\s*(?:ASC|DESC)?)*\s*$/is', '', $BaseSql);
        $SayimSql = "SELECT COUNT(*) as Total FROM ({$SayimBaseSql}) as CountQuery";
        $SayimStmt = $this->Db->prepare($SayimSql);
        $SayimStmt->execute($Parametreler);
        $Toplam = (int) $SayimStmt->fetch()['Total'];
        
        $ToplamSayfa = $Limit > 0 ? (int) ceil($Toplam / $Limit) : 1;
        $Sayfa = max(1, min($Sayfa, $ToplamSayfa > 0 ? $ToplamSayfa : 1));
        $Offset = ($Sayfa - 1) * $Limit;
        
        $SayfaliBirSql = $BaseSql . " OFFSET {$Offset} ROWS FETCH NEXT {$Limit} ROWS ONLY";
        
        $Stmt = $this->Db->prepare($SayfaliBirSql);
        $Stmt->execute($Parametreler);
        $Veri = $Stmt->fetchAll();
        
        return [
            'data' => $Veri,
            'pagination' => [
                'page' => $Sayfa,
                'limit' => $Limit,
                'total' => $Toplam,
                'totalPages' => $ToplamSayfa,
                'hasNext' => $Sayfa < $ToplamSayfa,
                'hasPrev' => $Sayfa > 1
            ]
        ];
    }

    public function tumAktifler(): array
    {
        $Stmt = $this->Db->query("SELECT * FROM {$this->Tablo} WHERE Sil = 0 ORDER BY Id DESC");
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

    



    public function bulTemel(int $Id): ?array
    {
        $Sql = "SELECT * FROM {$this->Tablo} WHERE Id = :Id AND Sil = 0";
        $Stmt = $this->Db->prepare($Sql);
        $Stmt->execute(['Id' => $Id]);
        return $Stmt->fetch() ?: null;
    }

    public function yedekle(int $Id, string $YedekTablo, ?int $KullaniciId = null): void
    {
        
        $Kayit = $this->bulTemel($Id);
        if (!$Kayit) {
            return;
        }
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

        return Transaction::wrap(function () use ($Sql, $Yukleme, $Veri) {
            $Stmt = $this->Db->prepare($Sql);
            $Stmt->execute($Yukleme);
            $Id = (int) $this->Db->lastInsertId();
            ActionLogger::insert($this->Tablo, ['Id' => $Id], $Veri);
            return $Id;
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

        $BckTablo = 'bck_' . $this->Tablo;

        Transaction::wrap(function () use ($Sql, $Yukleme, $Id, $BckTablo, $KullaniciId, $Veri, $EkKosul) {
            try {
                $this->yedekle($Id, $BckTablo, $KullaniciId);
            } catch (\Throwable $Ignored) {
                
                
            }

            $Stmt = $this->Db->prepare($Sql);
            $Stmt->execute($Yukleme);

            ActionLogger::update($this->Tablo, array_merge(['Id' => $Id], $EkKosul), $Veri);
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

        Transaction::wrap(function () use ($Sql, $Yukleme, $Id, $EkKosul) {
            $Stmt = $this->Db->prepare($Sql);
            $Stmt->execute($Yukleme);
            ActionLogger::delete($this->Tablo, array_merge(['Id' => $Id], $EkKosul), 'Soft Delete');
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
