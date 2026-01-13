<?php

namespace App\Repositories;

class PaymentRepository extends BaseRepository
{
    protected string $Tablo = 'tbl_odeme';

    public function musteriyeGore(int $MusteriId): array
    {
        $Sql = "
            SELECT o.*, 
                   f.Tarih as FaturaTarihi,
                   f.Tutar as FaturaTutari,
                   f.DovizCinsi as FaturaDovizi,
                   f.Aciklama as FaturaAciklama
            FROM tbl_odeme o
            LEFT JOIN tbl_fatura f ON o.FaturaId = f.Id
            WHERE o.Sil = 0 AND o.MusteriId = :MId 
            ORDER BY o.Tarih DESC, o.Id DESC
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
            SELECT o.*, 
                   f.Tarih as FaturaTarihi,
                   f.Tutar as FaturaTutari,
                   f.DovizCinsi as FaturaDovizi,
                   f.Aciklama as FaturaAciklama
            FROM tbl_odeme o
            LEFT JOIN tbl_fatura f ON o.FaturaId = f.Id
            WHERE o.Sil = 0 AND o.MusteriId = :MId 
            ORDER BY o.Tarih DESC, o.Id DESC
        ";
        $result = $this->paginatedQuery($Sql, ['MId' => $MusteriId], $page, $limit);
        $this->logSelect(['Sil' => 0, 'MusteriId' => $MusteriId, 'page' => $page], $result['data']);
        return $result;
    }

    public function tumAktifler(): array
    {
        $Sql = "
            SELECT o.*, 
                   m.Unvan as MusteriUnvan,
                   f.Tarih as FaturaTarihi,
                   f.Tutar as FaturaTutari,
                   f.DovizCinsi as FaturaDovizi,
                   f.Aciklama as FaturaAciklama
            FROM tbl_odeme o
            LEFT JOIN tbl_musteri m ON o.MusteriId = m.Id
            LEFT JOIN tbl_fatura f ON o.FaturaId = f.Id
            WHERE o.Sil = 0
            ORDER BY o.Tarih DESC, o.Id DESC
        ";
        $Stmt = $this->Db->query($Sql);
        $Sonuclar = $Stmt->fetchAll();
        $this->logSelect(['Sil' => 0], $Sonuclar);
        return $Sonuclar;
    }

    public function tumAktiflerPaginated(int $page = 1, int $limit = 10): array
    {
        $Sql = "
            SELECT o.*, 
                   m.Unvan as MusteriUnvan,
                   f.Tarih as FaturaTarihi,
                   f.Tutar as FaturaTutari,
                   f.DovizCinsi as FaturaDovizi,
                   f.Aciklama as FaturaAciklama
            FROM tbl_odeme o
            LEFT JOIN tbl_musteri m ON o.MusteriId = m.Id
            LEFT JOIN tbl_fatura f ON o.FaturaId = f.Id
            WHERE o.Sil = 0
            ORDER BY o.Tarih DESC, o.Id DESC
        ";
        $result = $this->paginatedQuery($Sql, [], $page, $limit);
        $this->logSelect(['Sil' => 0, 'page' => $page], $result['data']);
        return $result;
    }
}
