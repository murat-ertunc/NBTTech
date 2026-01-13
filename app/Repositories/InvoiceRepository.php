<?php

namespace App\Repositories;

class InvoiceRepository extends BaseRepository
{
    protected string $Tablo = 'tbl_fatura';

    public function musteriyeGore(int $MusteriId): array
    {
        $Sql = "
            SELECT f.*, 
                   (SELECT COALESCE(SUM(o.Tutar),0) FROM tbl_odeme o WHERE o.FaturaId = f.Id AND o.Sil=0) as OdenenTutar,
                   f.Tutar - (SELECT COALESCE(SUM(o.Tutar),0) FROM tbl_odeme o WHERE o.FaturaId = f.Id AND o.Sil=0) as Kalan
            FROM tbl_fatura f
            WHERE f.Sil = 0 AND f.MusteriId = :MId 
            ORDER BY f.Tarih DESC, f.Id DESC
        ";
        $Stmt = $this->Db->prepare($Sql);
        $Stmt->execute(['MId' => $MusteriId]);
        $Sonuclar = $Stmt->fetchAll();
        $this->logSelect(['Sil' => 0, 'MusteriId' => $MusteriId], $Sonuclar);
        return $Sonuclar;
    }

    public function musteriyeGorePaginated(int $MusteriId, int $page = 1, int $limit = 10): array
    {
        $Sql = "
            SELECT f.*, 
                   (SELECT COALESCE(SUM(o.Tutar),0) FROM tbl_odeme o WHERE o.FaturaId = f.Id AND o.Sil=0) as OdenenTutar,
                   f.Tutar - (SELECT COALESCE(SUM(o.Tutar),0) FROM tbl_odeme o WHERE o.FaturaId = f.Id AND o.Sil=0) as Kalan
            FROM tbl_fatura f
            WHERE f.Sil = 0 AND f.MusteriId = :MId 
            ORDER BY f.Tarih DESC, f.Id DESC
        ";
        $result = $this->paginatedQuery($Sql, ['MId' => $MusteriId], $page, $limit);
        $this->logSelect(['Sil' => 0, 'MusteriId' => $MusteriId, 'page' => $page], $result['data']);
        return $result;
    }

    public function tumAktifler(): array
    {
        $Sql = "
            SELECT f.*, 
                   m.Unvan as MusteriUnvan,
                   (SELECT COALESCE(SUM(o.Tutar),0) FROM tbl_odeme o WHERE o.FaturaId = f.Id AND o.Sil=0) as OdenenTutar,
                   f.Tutar - (SELECT COALESCE(SUM(o.Tutar),0) FROM tbl_odeme o WHERE o.FaturaId = f.Id AND o.Sil=0) as Kalan
            FROM tbl_fatura f
            LEFT JOIN tbl_musteri m ON f.MusteriId = m.Id
            WHERE f.Sil = 0
            ORDER BY f.Tarih DESC, f.Id DESC
        ";
        $Stmt = $this->Db->query($Sql);
        $Sonuclar = $Stmt->fetchAll();
        $this->logSelect(['Sil' => 0], $Sonuclar);
        return $Sonuclar;
    }

    public function tumAktiflerPaginated(int $page = 1, int $limit = 10): array
    {
        $Sql = "
            SELECT f.*, 
                   m.Unvan as MusteriUnvan,
                   (SELECT COALESCE(SUM(o.Tutar),0) FROM tbl_odeme o WHERE o.FaturaId = f.Id AND o.Sil=0) as OdenenTutar,
                   f.Tutar - (SELECT COALESCE(SUM(o.Tutar),0) FROM tbl_odeme o WHERE o.FaturaId = f.Id AND o.Sil=0) as Kalan
            FROM tbl_fatura f
            LEFT JOIN tbl_musteri m ON f.MusteriId = m.Id
            WHERE f.Sil = 0
            ORDER BY f.Tarih DESC, f.Id DESC
        ";
        $result = $this->paginatedQuery($Sql, [], $page, $limit);
        $this->logSelect(['Sil' => 0, 'page' => $page], $result['data']);
        return $result;
    }

    /*
     * Vadesi geçmiş (ödenmemiş) faturaları, ilerideki takvim/alarm logic'inde kullanmak üzere çekebileceğimiz
     * ek metod.
    */
    public function odenmemisFaturalar(): array
    {
        $Sql = "
            SELECT f.*, 
                   (SELECT COALESCE(SUM(o.Tutar),0) FROM tbl_odeme o WHERE o.FaturaId = f.Id AND o.Sil=0) as OdenenTutar
            FROM tbl_fatura f
            WHERE f.Sil = 0
            AND f.Tutar > (SELECT COALESCE(SUM(o.Tutar),0) FROM tbl_odeme o WHERE o.FaturaId = f.Id AND o.Sil=0)
            ORDER BY f.Tarih ASC
        ";
        
        $Stmt = $this->Db->query($Sql);
        $Sonuclar = $Stmt->fetchAll();
        $this->logSelect(['Odenmemis' => true], $Sonuclar);
        return $Sonuclar;
    }
}
