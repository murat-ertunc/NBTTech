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
        $Sql = "SELECT g.*, m.Unvan AS MusteriUnvan 
                FROM {$this->Tablo} g 
                LEFT JOIN tbl_musteri m ON g.MusteriId = m.Id 
                WHERE g.MusteriId = :Mid AND g.Sil = 0 
                ORDER BY g.Tarih DESC, g.Id DESC";
        $Stmt = $this->Db->prepare($Sql);
        $Stmt->execute(['Mid' => $MusteriId]);
        $Sonuclar = $Stmt->fetchAll();
        $this->logSelect(['MusteriId' => $MusteriId, 'Sil' => 0], $Sonuclar);
        return $Sonuclar;
    }

    public function musteriGorusmeleriPaginated(int $MusteriId, int $page = 1, int $limit = 10): array
    {
        $Sql = "SELECT g.*, m.Unvan AS MusteriUnvan 
                FROM {$this->Tablo} g 
                LEFT JOIN tbl_musteri m ON g.MusteriId = m.Id 
                WHERE g.MusteriId = :Mid AND g.Sil = 0 
                ORDER BY g.Tarih DESC, g.Id DESC";
        $result = $this->paginatedQuery($Sql, ['Mid' => $MusteriId], $page, $limit);
        $this->logSelect(['MusteriId' => $MusteriId, 'Sil' => 0, 'page' => $page], $result['data']);
        return $result;
    }
}
