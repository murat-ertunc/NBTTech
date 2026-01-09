<?php

namespace App\Repositories;

class FileRepository extends BaseRepository
{
    protected string $Tablo = 'tbl_dosya';

    /**
     * Tüm aktif dosyaları müşteri adı ile birlikte getir
     */
    public function tumAktifler(): array
    {
        $Sql = "SELECT f.*, m.Unvan AS MusteriUnvan 
                FROM {$this->Tablo} f 
                LEFT JOIN tbl_musteri m ON f.MusteriId = m.Id 
                WHERE f.Sil = 0 
                ORDER BY f.OlusturmaZamani DESC";
        $Stmt = $this->Db->query($Sql);
        $Sonuclar = $Stmt->fetchAll();
        $this->logSelect(['Sil' => 0], $Sonuclar);
        return $Sonuclar;
    }

    public function musteriDosyalari(int $MusteriId): array
    {
        $Stmt = $this->Db->prepare("SELECT * FROM {$this->Tablo} WHERE MusteriId = :Mid AND Sil = 0 ORDER BY OlusturmaZamani DESC");
        $Stmt->execute(['Mid' => $MusteriId]);
        $Sonuclar = $Stmt->fetchAll();
        $this->logSelect(['MusteriId' => $MusteriId, 'Sil' => 0], $Sonuclar);
        return $Sonuclar;
    }
}
