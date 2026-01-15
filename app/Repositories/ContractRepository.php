<?php

namespace App\Repositories;

class ContractRepository extends BaseRepository
{
    protected string $Tablo = 'tbl_sozlesme';

    /**
     * Tum aktif sozlesmeleri musteri adi ile birlikte getir
     */
    public function tumAktifler(): array
    {
        $Sql = "SELECT s.*, m.Unvan AS MusteriUnvan, p.ProjeAdi AS ProjeAdi 
                FROM {$this->Tablo} s 
                LEFT JOIN tbl_musteri m ON s.MusteriId = m.Id 
                LEFT JOIN tbl_proje p ON s.ProjeId = p.Id 
                WHERE s.Sil = 0 
                ORDER BY s.Id DESC";
        $Stmt = $this->Db->query($Sql);
        $Sonuclar = $Stmt->fetchAll();
        $this->logSelect(['Sil' => 0], $Sonuclar);
        return $Sonuclar;
    }

    public function musteriSozlesmeleri(int $MusteriId): array
    {
        $Sql = "SELECT s.*, p.ProjeAdi AS ProjeAdi 
                FROM {$this->Tablo} s 
                LEFT JOIN tbl_proje p ON s.ProjeId = p.Id 
                WHERE s.MusteriId = :Mid AND s.Sil = 0 
                ORDER BY s.Id DESC";
        $Stmt = $this->Db->prepare($Sql);
        $Stmt->execute(['Mid' => $MusteriId]);
        $Sonuclar = $Stmt->fetchAll();
        $this->logSelect(['MusteriId' => $MusteriId, 'Sil' => 0], $Sonuclar);
        return $Sonuclar;
    }

    public function musteriSozlesmeleriPaginated(int $MusteriId, int $Sayfa = 1, int $Limit = 10): array
    {
        $Sql = "SELECT s.*, p.ProjeAdi AS ProjeAdi 
                FROM {$this->Tablo} s 
                LEFT JOIN tbl_proje p ON s.ProjeId = p.Id 
                WHERE s.MusteriId = :Mid AND s.Sil = 0 
                ORDER BY s.Id DESC";
        $Sonuc = $this->paginatedQuery($Sql, ['Mid' => $MusteriId], $Sayfa, $Limit);
        $this->logSelect(['MusteriId' => $MusteriId, 'Sil' => 0, 'page' => $Sayfa], $Sonuc['data']);
        return $Sonuc;
    }

    public function tumAktiflerPaginated(int $Sayfa = 1, int $Limit = 10): array
    {
        $Sql = "SELECT s.*, m.Unvan AS MusteriUnvan, p.ProjeAdi AS ProjeAdi 
                FROM {$this->Tablo} s 
                LEFT JOIN tbl_musteri m ON s.MusteriId = m.Id 
                LEFT JOIN tbl_proje p ON s.ProjeId = p.Id 
                WHERE s.Sil = 0 
                ORDER BY s.Id DESC";
        $Sonuc = $this->paginatedQuery($Sql, [], $Sayfa, $Limit);
        $this->logSelect(['Sil' => 0, 'page' => $Sayfa], $Sonuc['data']);
        return $Sonuc;
    }
}
