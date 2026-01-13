<?php

namespace App\Repositories;

class ProjectRepository extends BaseRepository
{
    protected string $Tablo = 'tbl_proje';

    /**
     * Tüm aktif projeleri müşteri adı ile birlikte getir
     */
    public function tumAktifler(): array
    {
        $Sql = "SELECT p.*, m.Unvan AS MusteriUnvan 
                FROM {$this->Tablo} p 
                LEFT JOIN tbl_musteri m ON p.MusteriId = m.Id 
                WHERE p.Sil = 0 
                ORDER BY p.Id DESC";
        $Stmt = $this->Db->query($Sql);
        $Sonuclar = $Stmt->fetchAll();
        $this->logSelect(['Sil' => 0], $Sonuclar);
        return $Sonuclar;
    }

    public function musteriProjeleri(int $MusteriId): array
    {
        $Stmt = $this->Db->prepare("SELECT * FROM {$this->Tablo} WHERE MusteriId = :Mid AND Sil = 0 ORDER BY Id DESC");
        $Stmt->execute(['Mid' => $MusteriId]);
        $Sonuclar = $Stmt->fetchAll();
        $this->logSelect(['MusteriId' => $MusteriId, 'Sil' => 0], $Sonuclar);
        return $Sonuclar;
    }

    public function musteriProjeleriPaginated(int $MusteriId, int $page = 1, int $limit = 10): array
    {
        $Sql = "SELECT * FROM {$this->Tablo} WHERE MusteriId = :Mid AND Sil = 0 ORDER BY Id DESC";
        $result = $this->paginatedQuery($Sql, ['Mid' => $MusteriId], $page, $limit);
        $this->logSelect(['MusteriId' => $MusteriId, 'Sil' => 0, 'page' => $page], $result['data']);
        return $result;
    }

    /**
     * Tüm aktif projeleri sayfalı olarak getir
     */
    public function tumAktiflerPaginated(int $page = 1, int $limit = 10): array
    {
        $Sql = "SELECT p.*, m.Unvan AS MusteriUnvan 
                FROM {$this->Tablo} p 
                LEFT JOIN tbl_musteri m ON p.MusteriId = m.Id 
                WHERE p.Sil = 0 
                ORDER BY p.Id DESC";
        $result = $this->paginatedQuery($Sql, [], $page, $limit);
        $this->logSelect(['Sil' => 0, 'page' => $page], $result['data']);
        return $result;
    }
}
