<?php

namespace App\Repositories;

class OfferRepository extends BaseRepository
{
    protected string $Tablo = 'tbl_teklif';

    public function musteriTeklifleri(int $MusteriId): array
    {
        $Stmt = $this->Db->prepare("SELECT * FROM {$this->Tablo} WHERE MusteriId = :Mid AND Sil = 0 ORDER BY Id DESC");
        $Stmt->execute(['Mid' => $MusteriId]);
        $Sonuclar = $Stmt->fetchAll();
        $this->logSelect(['MusteriId' => $MusteriId, 'Sil' => 0], $Sonuclar);
        return $Sonuclar;
    }

    public function projeTeklifleri(int $ProjeId): array
    {
        $Stmt = $this->Db->prepare("SELECT * FROM {$this->Tablo} WHERE ProjeId = :Pid AND Sil = 0 ORDER BY Id DESC");
        $Stmt->execute(['Pid' => $ProjeId]);
        $Sonuclar = $Stmt->fetchAll();
        $this->logSelect(['ProjeId' => $ProjeId, 'Sil' => 0], $Sonuclar);
        return $Sonuclar;
    }
}
