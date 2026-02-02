<?php

namespace App\Repositories;

class FileRepository extends BaseRepository
{
    protected string $Tablo = 'tbl_dosya';

    


    public function tumAktifler(): array
    {
        $Sql = "SELECT f.*, 
                   f.EklemeZamani AS OlusturmaZamani,
                   f.EklemeZamani AS OlusturmaTarihi,
                   m.Unvan AS MusteriUnvan, 
                   p.ProjeAdi AS ProjeAdi 
                FROM {$this->Tablo} f 
                LEFT JOIN tbl_musteri m ON f.MusteriId = m.Id 
                LEFT JOIN tbl_proje p ON f.ProjeId = p.Id 
                WHERE f.Sil = 0 
                ORDER BY f.EklemeZamani DESC";
        $Stmt = $this->Db->query($Sql);
        $Sonuclar = $Stmt->fetchAll();
        $this->logSelect(['Sil' => 0], $Sonuclar);
        return $Sonuclar;
    }

    public function musteriDosyalari(int $MusteriId): array
    {
        $Sql = "SELECT f.*, 
                   f.EklemeZamani AS OlusturmaZamani,
                   f.EklemeZamani AS OlusturmaTarihi,
                   m.Unvan AS MusteriUnvan, 
                   p.ProjeAdi AS ProjeAdi 
                FROM {$this->Tablo} f 
                LEFT JOIN tbl_musteri m ON f.MusteriId = m.Id 
                LEFT JOIN tbl_proje p ON f.ProjeId = p.Id 
                WHERE f.MusteriId = :Mid AND f.Sil = 0 
                ORDER BY f.EklemeZamani DESC";
        $Stmt = $this->Db->prepare($Sql);
        $Stmt->execute(['Mid' => $MusteriId]);
        $Sonuclar = $Stmt->fetchAll();
        $this->logSelect(['MusteriId' => $MusteriId, 'Sil' => 0], $Sonuclar);
        return $Sonuclar;
    }

    public function musteriDosyalariPaginated(int $MusteriId, int $page = 1, int $limit = 10): array
    {
        $Sql = "SELECT f.*, 
                   f.EklemeZamani AS OlusturmaZamani,
                   f.EklemeZamani AS OlusturmaTarihi,
                   m.Unvan AS MusteriUnvan, 
                   p.ProjeAdi AS ProjeAdi 
                FROM {$this->Tablo} f 
                LEFT JOIN tbl_musteri m ON f.MusteriId = m.Id 
                LEFT JOIN tbl_proje p ON f.ProjeId = p.Id 
                WHERE f.MusteriId = :Mid AND f.Sil = 0 
                ORDER BY f.EklemeZamani DESC";
        $result = $this->paginatedQuery($Sql, ['Mid' => $MusteriId], $page, $limit);
        $this->logSelect(['MusteriId' => $MusteriId, 'Sil' => 0, 'page' => $page], $result['data']);
        return $result;
    }
}
