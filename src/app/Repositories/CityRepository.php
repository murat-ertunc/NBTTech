<?php
/**
 * City Repository için veri erişim işlemlerini yürütür.
 * Sorgu ve kalıcılık katmanını soyutlar.
 */

namespace App\Repositories;

use App\Core\Transaction;
use App\Services\Logger\ActionLogger;

class CityRepository extends BaseRepository
{
    protected string $Tablo = 'tnm_sehir';

    public function tumAktifler(): array
    {
        $Sql = "SELECT Id, Guid, PlakaKodu, Ad, Bolge, EklemeZamani, DegisiklikZamani
                FROM {$this->Tablo}
                WHERE Sil = 0
                ORDER BY Ad ASC";
        $Stmt = $this->Db->query($Sql);
        $Sonuclar = $Stmt->fetchAll();
        $this->logSelect(['Sil' => 0], $Sonuclar);
        return $Sonuclar;
    }

    public function bul(int $Id): ?array
    {
        $Sql = "SELECT Id, Guid, PlakaKodu, Ad, Bolge, EklemeZamani, DegisiklikZamani
                FROM {$this->Tablo}
                WHERE Id = :Id AND Sil = 0";
        $Stmt = $this->Db->prepare($Sql);
        $Stmt->execute(['Id' => $Id]);
        $Sonuc = $Stmt->fetch();
        return $Sonuc ?: null;
    }

    public function ekle(array $Veri, ?int $KullaniciId = null): int
    {
        return Transaction::wrap(function () use ($Veri, $KullaniciId) {
            $Sql = "INSERT INTO {$this->Tablo} (PlakaKodu, Ad, Bolge, EkleyenUserId, DegistirenUserId)
                    OUTPUT INSERTED.Id
                    VALUES (:PlakaKodu, :Ad, :Bolge, :EkleyenUserId, :DegistirenUserId)";
            $Stmt = $this->Db->prepare($Sql);
            $Stmt->execute([
                'PlakaKodu' => $Veri['PlakaKodu'] ?? '',
                'Ad' => $Veri['Ad'],
                'Bolge' => $Veri['Bolge'] ?? null,
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
            $Sql = "UPDATE {$this->Tablo} SET Sil = 1, DegisiklikZamani = SYSUTCDATETIME(), DegistirenUserId = :DegistirenUserId WHERE Id = :Id";
            $Stmt = $this->Db->prepare($Sql);
            $Stmt->execute(['DegistirenUserId' => $KullaniciId, 'Id' => $Id]);
            ActionLogger::delete($this->Tablo, ['Id' => $Id]);
        });
    }

    public function adIleBul(string $Ad): ?array
    {
        $Sql = "SELECT Id, Ad FROM {$this->Tablo} WHERE Ad = :Ad AND Sil = 0";
        $Stmt = $this->Db->prepare($Sql);
        $Stmt->execute(['Ad' => $Ad]);
        $Sonuc = $Stmt->fetch();
        return $Sonuc ?: null;
    }
}
