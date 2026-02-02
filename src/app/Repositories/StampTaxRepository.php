<?php
/**
 * Stamp Tax Repository için veri erişim işlemlerini yürütür.
 * Sorgu ve kalıcılık katmanını soyutlar.
 */

namespace App\Repositories;

use App\Core\Transaction;
use App\Services\Logger\ActionLogger;

class StampTaxRepository extends BaseRepository
{
    protected string $Tablo = 'tbl_damgavergisi';

    public function tumAktifler(): array
    {
        $Sql = "SELECT d.*, m.Unvan AS MusteriUnvan, p.ProjeAdi AS ProjeAdi
                FROM {$this->Tablo} d
                LEFT JOIN tbl_musteri m ON d.MusteriId = m.Id
                LEFT JOIN tbl_proje p ON d.ProjeId = p.Id
                WHERE d.Sil = 0
                ORDER BY d.Tarih DESC, d.Id DESC";
        $Stmt = $this->Db->query($Sql);
        $Sonuclar = $Stmt->fetchAll();
        $this->logSelect(['Sil' => 0], $Sonuclar);
        return $Sonuclar;
    }

    public function musteriDamgaVergileri(int $MusteriId): array
    {
        $Sql = "SELECT d.*, m.Unvan AS MusteriUnvan, p.ProjeAdi AS ProjeAdi
                FROM {$this->Tablo} d
                LEFT JOIN tbl_musteri m ON d.MusteriId = m.Id
                LEFT JOIN tbl_proje p ON d.ProjeId = p.Id
                WHERE d.MusteriId = :Mid AND d.Sil = 0
                ORDER BY d.Tarih DESC, d.Id DESC";
        $Stmt = $this->Db->prepare($Sql);
        $Stmt->execute(['Mid' => $MusteriId]);
        $Sonuclar = $Stmt->fetchAll();
        $this->logSelect(['MusteriId' => $MusteriId, 'Sil' => 0], $Sonuclar);
        return $Sonuclar;
    }

    public function musteriDamgaVergileriPaginated(int $MusteriId, int $Sayfa = 1, int $Limit = 10): array
    {
        $Sql = "SELECT d.*, m.Unvan AS MusteriUnvan, p.ProjeAdi AS ProjeAdi
                FROM {$this->Tablo} d
                LEFT JOIN tbl_musteri m ON d.MusteriId = m.Id
                LEFT JOIN tbl_proje p ON d.ProjeId = p.Id
                WHERE d.MusteriId = :Mid AND d.Sil = 0
                ORDER BY d.Tarih DESC, d.Id DESC";
        $Sonuc = $this->paginatedQuery($Sql, ['Mid' => $MusteriId], $Sayfa, $Limit);
        $this->logSelect(['MusteriId' => $MusteriId, 'Sil' => 0, 'page' => $Sayfa], $Sonuc['data']);
        return $Sonuc;
    }
}
