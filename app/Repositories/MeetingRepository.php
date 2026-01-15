<?php

namespace App\Repositories;

class MeetingRepository extends BaseRepository
{
    protected string $Tablo = 'tbl_gorusme';

    /**
     * Tum aktif gorusmeleri musteri adi ile birlikte getir
     */
    public function tumAktifler(): array
    {
        $Sql = "SELECT g.*, m.Unvan AS MusteriUnvan, p.ProjeAdi AS ProjeAdi 
                FROM {$this->Tablo} g 
                LEFT JOIN tbl_musteri m ON g.MusteriId = m.Id 
                LEFT JOIN tbl_proje p ON g.ProjeId = p.Id 
                WHERE g.Sil = 0 
                ORDER BY g.Tarih DESC, g.Id DESC";
        $Stmt = $this->Db->query($Sql);
        $Sonuclar = $Stmt->fetchAll();
        $this->logSelect(['Sil' => 0], $Sonuclar);
        return $Sonuclar;
    }

    public function musteriGorusmeleri(int $MusteriId): array
    {
        $Sql = "SELECT g.*, m.Unvan AS MusteriUnvan, p.ProjeAdi AS ProjeAdi 
                FROM {$this->Tablo} g 
                LEFT JOIN tbl_musteri m ON g.MusteriId = m.Id 
                LEFT JOIN tbl_proje p ON g.ProjeId = p.Id 
                WHERE g.MusteriId = :Mid AND g.Sil = 0 
                ORDER BY g.Tarih DESC, g.Id DESC";
        $Stmt = $this->Db->prepare($Sql);
        $Stmt->execute(['Mid' => $MusteriId]);
        $Sonuclar = $Stmt->fetchAll();
        $this->logSelect(['MusteriId' => $MusteriId, 'Sil' => 0], $Sonuclar);
        return $Sonuclar;
    }

    public function musteriGorusmeleriPaginated(int $MusteriId, int $Sayfa = 1, int $Limit = 10): array
    {
        $Sql = "SELECT g.*, m.Unvan AS MusteriUnvan, p.ProjeAdi AS ProjeAdi 
                FROM {$this->Tablo} g 
                LEFT JOIN tbl_musteri m ON g.MusteriId = m.Id 
                LEFT JOIN tbl_proje p ON g.ProjeId = p.Id 
                WHERE g.MusteriId = :Mid AND g.Sil = 0 
                ORDER BY g.Tarih DESC, g.Id DESC";
        $Sonuc = $this->paginatedQuery($Sql, ['Mid' => $MusteriId], $Sayfa, $Limit);
        $this->logSelect(['MusteriId' => $MusteriId, 'Sil' => 0, 'page' => $Sayfa], $Sonuc['data']);
        return $Sonuc;
    }
}
