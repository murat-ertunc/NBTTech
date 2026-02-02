<?php
/**
 * Offer Repository için veri erişim işlemlerini yürütür.
 * Sorgu ve kalıcılık katmanını soyutlar.
 */

namespace App\Repositories;

class OfferRepository extends BaseRepository
{
    protected string $Tablo = 'tbl_teklif';

    public function tumAktifler(): array
    {
        $Sql = "SELECT t.*, m.Unvan AS MusteriUnvan, p.ProjeAdi AS ProjeAdi
                FROM {$this->Tablo} t
                LEFT JOIN tbl_musteri m ON t.MusteriId = m.Id
                LEFT JOIN tbl_proje p ON t.ProjeId = p.Id
                WHERE t.Sil = 0
                ORDER BY t.Id DESC";
        $Stmt = $this->Db->query($Sql);
        $Sonuclar = $Stmt->fetchAll();
        $this->logSelect(['Sil' => 0], $Sonuclar);
        return $Sonuclar;
    }

    public function musteriTeklifleri(int $MusteriId): array
    {
        $Sql = "SELECT t.*, p.ProjeAdi AS ProjeAdi
                FROM {$this->Tablo} t
                LEFT JOIN tbl_proje p ON t.ProjeId = p.Id
                WHERE t.MusteriId = :Mid AND t.Sil = 0
                ORDER BY t.Id DESC";
        $Stmt = $this->Db->prepare($Sql);
        $Stmt->execute(['Mid' => $MusteriId]);
        $Sonuclar = $Stmt->fetchAll();
        $this->logSelect(['MusteriId' => $MusteriId, 'Sil' => 0], $Sonuclar);
        return $Sonuclar;
    }

    public function projeTeklifleri(int $ProjeId): array
    {
        $Sql = "SELECT t.*, p.ProjeAdi AS ProjeAdi
                FROM {$this->Tablo} t
                LEFT JOIN tbl_proje p ON t.ProjeId = p.Id
                WHERE t.ProjeId = :Pid AND t.Sil = 0
                ORDER BY t.Id DESC";
        $Stmt = $this->Db->prepare($Sql);
        $Stmt->execute(['Pid' => $ProjeId]);
        $Sonuclar = $Stmt->fetchAll();
        $this->logSelect(['ProjeId' => $ProjeId, 'Sil' => 0], $Sonuclar);
        return $Sonuclar;
    }

    public function musteriTeklifleriPaginated(int $MusteriId, int $Sayfa = 1, int $Limit = 10): array
    {
        $Sql = "SELECT t.*, p.ProjeAdi AS ProjeAdi
                FROM {$this->Tablo} t
                LEFT JOIN tbl_proje p ON t.ProjeId = p.Id
                WHERE t.MusteriId = :Mid AND t.Sil = 0
                ORDER BY t.Id DESC";
        $Sonuc = $this->paginatedQuery($Sql, ['Mid' => $MusteriId], $Sayfa, $Limit);
        $this->logSelect(['MusteriId' => $MusteriId, 'Sil' => 0, 'page' => $Sayfa], $Sonuc['data']);
        return $Sonuc;
    }

    public function tumAktiflerPaginated(int $Sayfa = 1, int $Limit = 10): array
    {
        $Sql = "SELECT t.*, m.Unvan AS MusteriUnvan, p.ProjeAdi AS ProjeAdi
                FROM {$this->Tablo} t
                LEFT JOIN tbl_musteri m ON t.MusteriId = m.Id
                LEFT JOIN tbl_proje p ON t.ProjeId = p.Id
                WHERE t.Sil = 0
                ORDER BY t.Id DESC";
        $Sonuc = $this->paginatedQuery($Sql, [], $Sayfa, $Limit);
        $this->logSelect(['Sil' => 0, 'page' => $Sayfa], $Sonuc['data']);
        return $Sonuc;
    }
}
