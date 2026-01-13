<?php

namespace App\Repositories;

use App\Core\Transaction;
use App\Services\Logger\ActionLogger;

class StampTaxRepository extends BaseRepository
{
    protected string $Tablo = 'tbl_damgavergisi';

    // Tablo artık standart alan adlarına sahip (EklemeZamani, EkleyenUserId, Guid, BIT Sil)
    // Bu yüzden BaseRepository'nin ekle() metodunu kullanıyoruz - özel override gerekmiyor

    /**
     * Tüm aktif damga vergilerini müşteri adı ile birlikte getir
     */
    public function tumAktifler(): array
    {
        $Sql = "SELECT d.*, m.Unvan AS MusteriUnvan 
                FROM {$this->Tablo} d 
                LEFT JOIN tbl_musteri m ON d.MusteriId = m.Id 
                WHERE d.Sil = 0 
                ORDER BY d.Tarih DESC, d.Id DESC";
        $Stmt = $this->Db->query($Sql);
        $Sonuclar = $Stmt->fetchAll();
        $this->logSelect(['Sil' => 0], $Sonuclar);
        return $Sonuclar;
    }

    public function musteriDamgaVergileri(int $MusteriId): array
    {
        $Sql = "SELECT d.*, m.Unvan AS MusteriUnvan 
                FROM {$this->Tablo} d 
                LEFT JOIN tbl_musteri m ON d.MusteriId = m.Id 
                WHERE d.MusteriId = :Mid AND d.Sil = 0 
                ORDER BY d.Tarih DESC, d.Id DESC";
        $Stmt = $this->Db->prepare($Sql);
        $Stmt->execute(['Mid' => $MusteriId]);
        $Sonuclar = $Stmt->fetchAll();
        $this->logSelect(['MusteriId' => $MusteriId, 'Sil' => 0], $Sonuclar);
        return $Sonuclar;
    }

    public function musteriDamgaVergileriPaginated(int $MusteriId, int $page = 1, int $limit = 10): array
    {
        $Sql = "SELECT d.*, m.Unvan AS MusteriUnvan 
                FROM {$this->Tablo} d 
                LEFT JOIN tbl_musteri m ON d.MusteriId = m.Id 
                WHERE d.MusteriId = :Mid AND d.Sil = 0 
                ORDER BY d.Tarih DESC, d.Id DESC";
        $result = $this->paginatedQuery($Sql, ['Mid' => $MusteriId], $page, $limit);
        $this->logSelect(['MusteriId' => $MusteriId, 'Sil' => 0, 'page' => $page], $result['data']);
        return $result;
    }
}
