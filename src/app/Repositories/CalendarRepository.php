<?php

namespace App\Repositories;

class CalendarRepository extends BaseRepository
{
    protected string $Tablo = 'tbl_takvim';

    /**
     * Tum aktif takvim kayitlarini musteri ve proje adi ile birlikte getir
     */
    public function tumAktifler(): array
    {
        $Sql = "SELECT t.*, m.Unvan AS MusteriUnvan, p.ProjeAdi 
                FROM {$this->Tablo} t 
                LEFT JOIN tbl_musteri m ON t.MusteriId = m.Id 
                LEFT JOIN tbl_proje p ON t.ProjeId = p.Id 
                WHERE t.Sil = 0 
                ORDER BY t.BaslangicTarihi DESC, t.Id DESC";
        $Stmt = $this->Db->query($Sql);
        $Sonuclar = $Stmt->fetchAll();
        $this->logSelect(['Sil' => 0], $Sonuclar);
        return $Sonuclar;
    }

    public function musteriTakvimleri(int $MusteriId): array
    {
        $Sql = "SELECT t.*, m.Unvan AS MusteriUnvan, p.ProjeAdi 
                FROM {$this->Tablo} t 
                LEFT JOIN tbl_musteri m ON t.MusteriId = m.Id 
                LEFT JOIN tbl_proje p ON t.ProjeId = p.Id 
                WHERE t.MusteriId = :Mid AND t.Sil = 0 
                ORDER BY t.EklemeZamani DESC, t.Id DESC";
        $Stmt = $this->Db->prepare($Sql);
        $Stmt->execute(['Mid' => $MusteriId]);
        $Sonuclar = $Stmt->fetchAll();
        $this->logSelect(['MusteriId' => $MusteriId, 'Sil' => 0], $Sonuclar);
        return $Sonuclar;
    }

    public function musteriTakvimleriPaginated(int $MusteriId, int $Sayfa = 1, int $Limit = 10): array
    {
        $Sql = "SELECT t.*, m.Unvan AS MusteriUnvan, p.ProjeAdi 
                FROM {$this->Tablo} t 
                LEFT JOIN tbl_musteri m ON t.MusteriId = m.Id 
                LEFT JOIN tbl_proje p ON t.ProjeId = p.Id 
                WHERE t.MusteriId = :Mid AND t.Sil = 0 
                ORDER BY t.EklemeZamani DESC, t.Id DESC";
        $Sonuc = $this->paginatedQuery($Sql, ['Mid' => $MusteriId], $Sayfa, $Limit);
        $this->logSelect(['MusteriId' => $MusteriId, 'Sil' => 0, 'page' => $Sayfa], $Sonuc['data']);
        return $Sonuc;
    }
}
