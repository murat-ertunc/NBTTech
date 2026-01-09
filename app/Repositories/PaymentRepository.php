<?php

namespace App\Repositories;

class PaymentRepository extends BaseRepository
{
    protected string $Tablo = 'tbl_odeme';

    public function musteriyeGore(int $MusteriId): array
    {
        $Stmt = $this->Db->prepare("SELECT * FROM {$this->Tablo} WHERE Sil = 0 AND MusteriId = :MId ORDER BY Tarih DESC, Id DESC");
        $Stmt->execute(['MId' => $MusteriId]);
        $Sonuclar = $Stmt->fetchAll();
        $this->logSelect(['Sil' => 0, 'MusteriId' => $MusteriId], $Sonuclar);
        return $Sonuclar;
    }
}
