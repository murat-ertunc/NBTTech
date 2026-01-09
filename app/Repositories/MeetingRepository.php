<?php

namespace App\Repositories;

class MeetingRepository extends BaseRepository
{
    protected string $Tablo = 'tbl_gorusme';

    /**
     * Tüm aktif görüşmeleri müşteri adı ile birlikte getir
     */
    public function tumAktifler(): array
    {
        $Sql = "SELECT g.*, m.Unvan AS MusteriUnvan 
                FROM {$this->Tablo} g 
                LEFT JOIN tbl_musteri m ON g.MusteriId = m.Id 
                WHERE g.Sil = 0 
                ORDER BY g.Tarih DESC, g.Id DESC";
        $Stmt = $this->Db->query($Sql);
        $Sonuclar = $Stmt->fetchAll();
        $this->logSelect(['Sil' => 0], $Sonuclar);
        return $Sonuclar;
    }

    public function musteriGorusmeleri(int $MusteriId): array
    {
        $Stmt = $this->Db->prepare("SELECT * FROM {$this->Tablo} WHERE MusteriId = :Mid AND Sil = 0 ORDER BY Tarih DESC, Id DESC");
        $Stmt->execute(['Mid' => $MusteriId]);
        $Sonuclar = $Stmt->fetchAll();
        $this->logSelect(['MusteriId' => $MusteriId, 'Sil' => 0], $Sonuclar);
        return $Sonuclar;
    }
}
