<?php

namespace App\Repositories;

use App\Services\Logger\ActionLogger;

class ProjectRepository extends BaseRepository
{
    protected string $Tablo = 'tbl_proje';

    /**
     * Tüm aktif projeleri müşteri adı ile birlikte getir
     */
    public function tumAktifler(): array
    {
        $Sql = "SELECT p.*, m.Unvan AS MusteriUnvan 
                FROM {$this->Tablo} p 
                LEFT JOIN tbl_musteri m ON p.MusteriId = m.Id 
                WHERE p.Sil = 0 
                ORDER BY p.Id DESC";
        $Stmt = $this->Db->query($Sql);
        $Sonuclar = $Stmt->fetchAll();
        $this->logSelect(['Sil' => 0], $Sonuclar);
        return $Sonuclar;
    }

    public function musteriProjeleri(int $MusteriId): array
    {
        $Stmt = $this->Db->prepare("SELECT * FROM {$this->Tablo} WHERE MusteriId = :Mid AND Sil = 0 ORDER BY Id DESC");
        $Stmt->execute(['Mid' => $MusteriId]);
        $Sonuclar = $Stmt->fetchAll();
        $this->logSelect(['MusteriId' => $MusteriId, 'Sil' => 0], $Sonuclar);
        return $Sonuclar;
    }

    public function musteriProjeleriPaginated(int $MusteriId, int $page = 1, int $limit = 10): array
    {
        $Sql = "SELECT * FROM {$this->Tablo} WHERE MusteriId = :Mid AND Sil = 0 ORDER BY Id DESC";
        $result = $this->paginatedQuery($Sql, ['Mid' => $MusteriId], $page, $limit);
        $this->logSelect(['MusteriId' => $MusteriId, 'Sil' => 0, 'page' => $page], $result['data']);
        return $result;
    }

    /**
     * Tüm aktif projeleri sayfalı olarak getir
     */
    public function tumAktiflerPaginated(int $page = 1, int $limit = 10): array
    {
        $Sql = "SELECT p.*, m.Unvan AS MusteriUnvan 
                FROM {$this->Tablo} p 
                LEFT JOIN tbl_musteri m ON p.MusteriId = m.Id 
                WHERE p.Sil = 0 
                ORDER BY p.Id DESC";
        $result = $this->paginatedQuery($Sql, [], $page, $limit);
        $this->logSelect(['Sil' => 0, 'page' => $page], $result['data']);
        return $result;
    }

    /**
     * Proje silme ile birlikte ilişkili kayıtları da soft delete yapar
     * Kurallar.txt: Bağlı kayıtlar da Sil=1 yapılır
     * 
     * İlişkili tablolar: tbl_fatura, tbl_odeme, tbl_teklif, tbl_sozlesme, 
     * tbl_teminat, tbl_gorusme, tbl_kisi, tbl_damgavergisi, tbl_dosya
     */
    public function cascadeSoftSil(int $Id, int $KullaniciId): void
    {
        // Önce projeyi soft delete
        $this->softSil($Id, $KullaniciId);

        // İlişkili tabloları soft delete (ProjeId foreign key ile bağlı)
        $IliskiliTablolar = [
            'tbl_fatura',
            'tbl_odeme', 
            'tbl_teklif',
            'tbl_sozlesme',
            'tbl_teminat',
            'tbl_gorusme',
            'tbl_kisi',
            'tbl_damgavergisi',
            'tbl_dosya'
        ];

        foreach ($IliskiliTablolar as $Tablo) {
            $Sql = "UPDATE {$Tablo} SET 
                    Sil = 1, 
                    DegisiklikZamani = GETDATE(), 
                    DegistirenUserId = :UserId 
                    WHERE ProjeId = :ProjeId AND Sil = 0";
            $Stmt = $this->Db->prepare($Sql);
            $Stmt->execute(['ProjeId' => $Id, 'UserId' => $KullaniciId]);
            
            // Silinen kayıt sayısını logla
            $SilinenSayisi = $Stmt->rowCount();
            if ($SilinenSayisi > 0) {
                ActionLogger::logla('CASCADE_DELETE', $Tablo, null, [
                    'ProjeId' => $Id,
                    'SilinenKayitSayisi' => $SilinenSayisi
                ]);
            }
        }
    }
}
