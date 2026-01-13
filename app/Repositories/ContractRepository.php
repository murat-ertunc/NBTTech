<?php

namespace App\Repositories;

class ContractRepository extends BaseRepository
{
    protected string $Tablo = 'tbl_sozlesme';

    /**
     * Tüm aktif sözleşmeleri müşteri adı ile birlikte getir
     */
    public function tumAktifler(): array
    {
        $Sql = "SELECT s.*, m.Unvan AS MusteriUnvan 
                FROM {$this->Tablo} s 
                LEFT JOIN tbl_musteri m ON s.MusteriId = m.Id 
                WHERE s.Sil = 0 
                ORDER BY s.Id DESC";
        $Stmt = $this->Db->query($Sql);
        $Sonuclar = $Stmt->fetchAll();
        $this->logSelect(['Sil' => 0], $Sonuclar);
        return $Sonuclar;
    }

    public function musteriSozlesmeleri(int $MusteriId): array
    {
        $Stmt = $this->Db->prepare("SELECT * FROM {$this->Tablo} WHERE MusteriId = :Mid AND Sil = 0 ORDER BY Id DESC");
        $Stmt->execute(['Mid' => $MusteriId]);
        $Sonuclar = $Stmt->fetchAll();
        $this->logSelect(['MusteriId' => $MusteriId, 'Sil' => 0], $Sonuclar);
        return $Sonuclar;
    }

    public function musteriSozlesmeleriPaginated(int $MusteriId, int $page = 1, int $limit = 10): array
    {
        $Sql = "SELECT * FROM {$this->Tablo} WHERE MusteriId = :Mid AND Sil = 0 ORDER BY Id DESC";
        $result = $this->paginatedQuery($Sql, ['Mid' => $MusteriId], $page, $limit);
        $this->logSelect(['MusteriId' => $MusteriId, 'Sil' => 0, 'page' => $page], $result['data']);
        return $result;
    }

    public function tumAktiflerPaginated(int $page = 1, int $limit = 10): array
    {
        $Sql = "SELECT s.*, m.Unvan AS MusteriUnvan 
                FROM {$this->Tablo} s 
                LEFT JOIN tbl_musteri m ON s.MusteriId = m.Id 
                WHERE s.Sil = 0 
                ORDER BY s.Id DESC";
        $result = $this->paginatedQuery($Sql, [], $page, $limit);
        $this->logSelect(['Sil' => 0, 'page' => $page], $result['data']);
        return $result;
    }
}
