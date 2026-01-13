<?php

namespace App\Repositories;

class ContactRepository extends BaseRepository
{
    protected string $Tablo = 'tbl_kisi';

    /**
     * Tüm aktif kişileri müşteri adı ile birlikte getir
     */
    public function tumAktifler(): array
    {
        $Sql = "SELECT k.*, m.Unvan AS MusteriUnvan 
                FROM {$this->Tablo} k 
                LEFT JOIN tbl_musteri m ON k.MusteriId = m.Id 
                WHERE k.Sil = 0 
                ORDER BY k.AdSoyad ASC";
        $Stmt = $this->Db->query($Sql);
        $Sonuclar = $Stmt->fetchAll();
        $this->logSelect(['Sil' => 0], $Sonuclar);
        return $Sonuclar;
    }

    public function musteriKisileri(int $MusteriId): array
    {
        $Sql = "SELECT k.*, m.Unvan AS MusteriUnvan 
                FROM {$this->Tablo} k 
                LEFT JOIN tbl_musteri m ON k.MusteriId = m.Id 
                WHERE k.MusteriId = :Mid AND k.Sil = 0 
                ORDER BY k.AdSoyad ASC";
        $Stmt = $this->Db->prepare($Sql);
        $Stmt->execute(['Mid' => $MusteriId]);
        $Sonuclar = $Stmt->fetchAll();
        $this->logSelect(['MusteriId' => $MusteriId, 'Sil' => 0], $Sonuclar);
        return $Sonuclar;
    }

    public function musteriKisileriPaginated(int $MusteriId, int $page = 1, int $limit = 10): array
    {
        $Sql = "SELECT k.*, m.Unvan AS MusteriUnvan 
                FROM {$this->Tablo} k 
                LEFT JOIN tbl_musteri m ON k.MusteriId = m.Id 
                WHERE k.MusteriId = :Mid AND k.Sil = 0 
                ORDER BY k.AdSoyad ASC";
        $result = $this->paginatedQuery($Sql, ['Mid' => $MusteriId], $page, $limit);
        $this->logSelect(['MusteriId' => $MusteriId, 'Sil' => 0, 'page' => $page], $result['data']);
        return $result;
    }
}
