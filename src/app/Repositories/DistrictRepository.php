<?php
/**
 * District Repository için veri erişim işlemlerini yürütür.
 * Sorgu ve kalıcılık katmanını soyutlar.
 */

namespace App\Repositories;

use App\Core\Transaction;
use App\Services\Logger\ActionLogger;

class DistrictRepository extends BaseRepository
{
    protected string $Tablo = 'tnm_ilce';

    public function tumAktifler(): array
    {
        $Sql = "SELECT i.Id, i.Guid, i.SehirId, i.Ad, s.Ad AS SehirAdi, s.PlakaKodu,
                       i.EklemeZamani, i.DegisiklikZamani
                FROM {$this->Tablo} i
                INNER JOIN tnm_sehir s ON i.SehirId = s.Id
                WHERE i.Sil = 0 AND s.Sil = 0
                ORDER BY s.Ad ASC, i.Ad ASC";
        $Stmt = $this->Db->query($Sql);
        $Sonuclar = $Stmt->fetchAll();
        $this->logSelect(['Sil' => 0], $Sonuclar);
        return $Sonuclar;
    }

    public function sehireGore(int $SehirId): array
    {
        $Sql = "SELECT Id, Guid, SehirId, Ad, EklemeZamani, DegisiklikZamani
                FROM {$this->Tablo}
                WHERE SehirId = :SehirId AND Sil = 0
                ORDER BY Ad ASC";
        $Stmt = $this->Db->prepare($Sql);
        $Stmt->execute(['SehirId' => $SehirId]);
        return $Stmt->fetchAll();
    }

    public function bul(int $Id): ?array
    {
        $Sql = "SELECT i.Id, i.Guid, i.SehirId, i.Ad, s.Ad AS SehirAdi, s.PlakaKodu,
                       i.EklemeZamani, i.DegisiklikZamani
                FROM {$this->Tablo} i
                INNER JOIN tnm_sehir s ON i.SehirId = s.Id
                WHERE i.Id = :Id AND i.Sil = 0";
        $Stmt = $this->Db->prepare($Sql);
        $Stmt->execute(['Id' => $Id]);
        $Sonuc = $Stmt->fetch();
        return $Sonuc ?: null;
    }

    public function ekle(array $Veri, ?int $KullaniciId = null): int
    {
        return Transaction::wrap(function () use ($Veri, $KullaniciId) {
            $Sql = "INSERT INTO {$this->Tablo} (SehirId, Ad, EkleyenUserId, DegistirenUserId)
                    OUTPUT INSERTED.Id
                    VALUES (:SehirId, :Ad, :EkleyenUserId, :DegistirenUserId)";
            $Stmt = $this->Db->prepare($Sql);
            $Stmt->execute([
                'SehirId' => (int)$Veri['SehirId'],
                'Ad' => $Veri['Ad'],
                'EkleyenUserId' => $KullaniciId,
                'DegistirenUserId' => $KullaniciId
            ]);
            $Sonuc = $Stmt->fetch();
            $Id = $Sonuc['Id'] ?? 0;
            ActionLogger::insert($this->Tablo, ['Id' => $Id], $Veri);
            return $Id;
        });
    }

    public function guncelle(int $Id, array $Veri, ?int $KullaniciId = null, array $EkKosul = []): void
    {
        Transaction::wrap(function () use ($Id, $Veri, $KullaniciId) {
            try {
                $this->yedekle($Id, 'bck_tnm_ilce', $KullaniciId);
            } catch (\Throwable $Ignored) {
            }

            $SetParts = ['DegisiklikZamani = SYSUTCDATETIME()', 'DegistirenUserId = :DegistirenUserId'];
            $Params = ['DegistirenUserId' => $KullaniciId];

            foreach ($Veri as $Alan => $Deger) {
                $SetParts[] = "$Alan = :$Alan";
                $Params[$Alan] = $Deger;
            }
            $Params['Id'] = $Id;

            $Sql = "UPDATE {$this->Tablo} SET " . implode(', ', $SetParts) . " WHERE Id = :Id";
            $Stmt = $this->Db->prepare($Sql);
            $Stmt->execute($Params);
            ActionLogger::update($this->Tablo, ['Id' => $Id], $Veri);
        });
    }

    public function softSil(int $Id, ?int $KullaniciId = null, array $EkKosul = []): void
    {
        Transaction::wrap(function () use ($Id, $KullaniciId) {
            try {
                $this->yedekle($Id, 'bck_tnm_ilce', $KullaniciId);
            } catch (\Throwable $Ignored) {
            }

            $Sql = "UPDATE {$this->Tablo} SET Sil = 1, DegisiklikZamani = SYSUTCDATETIME(), DegistirenUserId = :DegistirenUserId WHERE Id = :Id";
            $Stmt = $this->Db->prepare($Sql);
            $Stmt->execute(['DegistirenUserId' => $KullaniciId, 'Id' => $Id]);
            ActionLogger::delete($this->Tablo, ['Id' => $Id]);
        });
    }

    public function sehirVeAdIleBul(int $SehirId, string $Ad): ?array
    {
        $Sql = "SELECT Id, Ad FROM {$this->Tablo} WHERE SehirId = :SehirId AND Ad = :Ad AND Sil = 0";
        $Stmt = $this->Db->prepare($Sql);
        $Stmt->execute(['SehirId' => $SehirId, 'Ad' => $Ad]);
        $Sonuc = $Stmt->fetch();
        return $Sonuc ?: null;
    }
}
