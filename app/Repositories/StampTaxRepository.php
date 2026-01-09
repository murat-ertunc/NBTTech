<?php

namespace App\Repositories;

class StampTaxRepository extends BaseRepository
{
    protected string $Tablo = 'tbl_damgavergisi';

    /**
     * Tüm aktif damga vergilerini müşteri adı ile birlikte getir
     */
    public function tumAktifler(): array
    {
        $Sql = "SELECT d.*, m.Unvan AS MusteriUnvan 
                FROM {$this->Tablo} d 
                LEFT JOIN tbl_musteri m ON d.MusteriId = m.Id 
                WHERE d.Sil = 0 
                ORDER BY d.Tarih DESC, d.Id DESC";
        $Stmt = $this->Db->query($Sql);
        $Sonuclar = $Stmt->fetchAll();
        $this->logSelect(['Sil' => 0], $Sonuclar);
        return $Sonuclar;
    }

    public function musteriDamgaVergileri(int $MusteriId): array
    {
        $Stmt = $this->Db->prepare("SELECT * FROM {$this->Tablo} WHERE MusteriId = :Mid AND Sil = 0 ORDER BY Tarih DESC, Id DESC");
        $Stmt->execute(['Mid' => $MusteriId]);
        $Sonuclar = $Stmt->fetchAll();
        $this->logSelect(['MusteriId' => $MusteriId, 'Sil' => 0], $Sonuclar);
        return $Sonuclar;
    }
}
