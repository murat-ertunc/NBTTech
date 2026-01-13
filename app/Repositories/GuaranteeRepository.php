<?php

namespace App\Repositories;

class GuaranteeRepository extends BaseRepository
{
    protected string $Tablo = 'tbl_teminat';

    /**
     * Tüm aktif teminatları müşteri adı ile birlikte getir
     */
    public function tumAktifler(): array
    {
        $Sql = "SELECT t.*, m.Unvan AS MusteriUnvan 
                FROM {$this->Tablo} t 
                LEFT JOIN tbl_musteri m ON t.MusteriId = m.Id 
                WHERE t.Sil = 0 
                ORDER BY t.Id DESC";
        $Stmt = $this->Db->query($Sql);
        $Sonuclar = $Stmt->fetchAll();
        $this->logSelect(['Sil' => 0], $Sonuclar);
        return $Sonuclar;
    }

    public function musteriTeminatlari(int $MusteriId): array
    {
        $Stmt = $this->Db->prepare("SELECT * FROM {$this->Tablo} WHERE MusteriId = :Mid AND Sil = 0 ORDER BY Id DESC");
        $Stmt->execute(['Mid' => $MusteriId]);
        $Sonuclar = $Stmt->fetchAll();
        $this->logSelect(['MusteriId' => $MusteriId, 'Sil' => 0], $Sonuclar);
        return $Sonuclar;
    }

    public function musteriTeminatlariPaginated(int $MusteriId, int $page = 1, int $limit = 10): array
    {
        $Sql = "SELECT * FROM {$this->Tablo} WHERE MusteriId = :Mid AND Sil = 0 ORDER BY Id DESC";
        $result = $this->paginatedQuery($Sql, ['Mid' => $MusteriId], $page, $limit);
        $this->logSelect(['MusteriId' => $MusteriId, 'Sil' => 0, 'page' => $page], $result['data']);
        return $result;
    }

    public function tumAktiflerPaginated(int $page = 1, int $limit = 10): array
    {
        $Sql = "SELECT t.*, m.Unvan AS MusteriUnvan 
                FROM {$this->Tablo} t 
                LEFT JOIN tbl_musteri m ON t.MusteriId = m.Id 
                WHERE t.Sil = 0 
                ORDER BY t.Id DESC";
        $result = $this->paginatedQuery($Sql, [], $page, $limit);
        $this->logSelect(['Sil' => 0, 'page' => $page], $result['data']);
        return $result;
    }
}
