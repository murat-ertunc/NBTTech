<?php

namespace App\Repositories;

class InvoiceRepository extends BaseRepository
{
    protected string $Tablo = 'tbl_fatura';

    public function musteriyeGore(int $MusteriId): array
    {
        $Sql = "
            SELECT f.*, 
                   (SELECT COALESCE(SUM(o.Tutar),0) FROM tbl_odeme o WHERE o.FaturaId = f.Id AND o.Sil=0) as OdenenTutar
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

    /*
     * Vadesi geçmiş (ödenmemiş) faturaları, ilerideki takvim/alarm logic'inde kullanmak üzere çekebileceğimiz
     * ek metod.
    */
    public function odenmemisFaturalar(): array
    {
        // Fatura tutari > Odemeler toplami
        // tbl_odeme tablosunda FaturaId ile iliski kurulmasi gerekir.
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
