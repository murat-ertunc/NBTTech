<?php
/**
 * Contact Repository için veri erişim işlemlerini yürütür.
 * Sorgu ve kalıcılık katmanını soyutlar.
 */

namespace App\Repositories;

class ContactRepository extends BaseRepository
{
    protected string $Tablo = 'tbl_kisi';

    public function tumAktifler(): array
    {
        $Sql = "SELECT k.*, m.Unvan AS MusteriUnvan
                FROM {$this->Tablo} k
                LEFT JOIN tbl_musteri m ON k.MusteriId = m.Id
                WHERE k.Sil = 0
                ORDER BY k.Id DESC";
        $Stmt = $this->Db->query($Sql);
        $Sonuclar = $Stmt->fetchAll();
        $this->logSelect(['Sil' => 0], $Sonuclar);
        return $Sonuclar;
    }

    public function musteriKisileri(int $MusteriId): array
    {
        $Sql = "SELECT k.*, m.Unvan AS MusteriUnvan
                FROM {$this->Tablo} k
                LEFT JOIN tbl_musteri m ON k.MusteriId = m.Id
                WHERE k.MusteriId = :Mid AND k.Sil = 0
                ORDER BY k.Id DESC";
        $Stmt = $this->Db->prepare($Sql);
        $Stmt->execute(['Mid' => $MusteriId]);
        $Sonuclar = $Stmt->fetchAll();
        $this->logSelect(['MusteriId' => $MusteriId, 'Sil' => 0], $Sonuclar);
        return $Sonuclar;
    }

    public function musteriKisileriPaginated(int $MusteriId, int $Sayfa = 1, int $Limit = 10): array
    {
        $Sql = "SELECT k.*, m.Unvan AS MusteriUnvan
                FROM {$this->Tablo} k
                LEFT JOIN tbl_musteri m ON k.MusteriId = m.Id
                WHERE k.MusteriId = :Mid AND k.Sil = 0
                ORDER BY k.Id DESC";
        $Sonuc = $this->paginatedQuery($Sql, ['Mid' => $MusteriId], $Sayfa, $Limit);
        $this->logSelect(['MusteriId' => $MusteriId, 'Sil' => 0, 'page' => $Sayfa], $Sonuc['data']);
        return $Sonuc;
    }
}
