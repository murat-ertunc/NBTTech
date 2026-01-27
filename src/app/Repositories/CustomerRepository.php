<?php

namespace App\Repositories;

use App\Core\Transaction;
use App\Models\BaseModel;

class CustomerRepository extends BaseRepository
{
    protected string $Tablo = 'tbl_musteri';
    
    /**
     * tbl_musteri tablosunda var olan kolonlar
     * Bu whitelist, update/insert sirasinda olmayan kolonlarin SQL'e eklenmesini engeller
     * NOT: Il ve Ilce kolonlari 066_add_musteri_il_ilce.sql migration'i calistirilinca eklenecek
     */
    protected array $IzinVerilenKolonlar = [
        'Id', 'Guid', 'EklemeZamani', 'EkleyenUserId', 'DegisiklikZamani', 'DegistirenUserId', 'Sil',
        'MusteriKodu', 'Unvan', 'Aciklama', 'VergiDairesi', 'VergiNo', 'MersisNo',
        'Adres', 'Telefon', 'Faks', 'Web'
        // 'Il', 'Ilce' - Migration 066 calistirilinca bu satiri aktif et
    ];

    /**
     * Override: Sadece izin verilen kolonlari ekle
     */
    public function ekle(array $Veri, ?int $KullaniciId = null): int
    {
        $Veri = $this->kolonlariFiltrele($Veri);
        return parent::ekle($Veri, $KullaniciId);
    }

    /**
     * Override: Sadece izin verilen kolonlari guncelle
     */
    public function guncelle(int $Id, array $Veri, ?int $KullaniciId = null, array $EkKosul = []): void
    {
        $Veri = $this->kolonlariFiltrele($Veri);
        parent::guncelle($Id, $Veri, $KullaniciId, $EkKosul);
    }

    /**
     * Izin verilmeyen kolonlari filtrele
     */
    protected function kolonlariFiltrele(array $Veri): array
    {
        return array_intersect_key($Veri, array_flip($this->IzinVerilenKolonlar));
    }

    public function kullaniciyaGoreAktifler(int $KullaniciId): array
    {
        $Stmt = $this->Db->prepare("SELECT * FROM {$this->Tablo} WHERE Sil = 0 AND EkleyenUserId = :Uid ORDER BY Id DESC");
        $Stmt->execute(['Uid' => $KullaniciId]);
        $Sonuclar = $Stmt->fetchAll();
        $this->logSelect(['Sil' => 0, 'EkleyenUserId' => $KullaniciId], $Sonuclar);
        return $Sonuclar;
    }

    public function tumAktiflerSiraliKullaniciBilgisiIle(): array
    {
        $Sql = "SELECT m.*, u.AdSoyad AS EkleyenAdSoyad, u.KullaniciAdi AS EkleyenKullaniciAdi 
                FROM {$this->Tablo} m 
                LEFT JOIN tnm_user u ON m.EkleyenUserId = u.Id 
                WHERE m.Sil = 0 
                ORDER BY m.Id DESC";
        $Stmt = $this->Db->query($Sql);
        $Sonuclar = $Stmt->fetchAll();
        $this->logSelect(['Sil' => 0], $Sonuclar);
        return $Sonuclar;
    }

    public function tumAktiflerSirali(): array
    {
        $Stmt = $this->Db->query("SELECT * FROM {$this->Tablo} WHERE Sil = 0 ORDER BY Id DESC");
        $Sonuclar = $Stmt->fetchAll();
        $this->logSelect(['Sil' => 0], $Sonuclar);
        return $Sonuclar;
    }

    public function sahipliKayitBul(int $Id, int $KullaniciId): ?array
    {
        $Stmt = $this->Db->prepare("SELECT * FROM {$this->Tablo} WHERE Id = :Id AND Sil = 0 AND EkleyenUserId = :Uid");
        $Stmt->execute(['Id' => $Id, 'Uid' => $KullaniciId]);
        $Kayit = $Stmt->fetch();
        $this->logSelect(['Id' => $Id, 'Sil' => 0, 'EkleyenUserId' => $KullaniciId], $Kayit ? [$Kayit] : []);
        return $Kayit ?: null;
    }

    /**
     * Kullaniciya ait tum musterileri soft delete yapar
     * Kullanici silindiginde cagrilir
     */
    public function kullanicininMusterileriniSil(int $KullaniciId, int $SilenKullaniciId): int
    {
        $Musteriler = $this->kullaniciyaGoreAktifler($KullaniciId);
        foreach ($Musteriler as $Musteri) {
            $this->yedekle((int) $Musteri['Id'], 'bck_tbl_musteri', $SilenKullaniciId);
        }
        
        // Toplu soft delete
        $StandartAlanlar = BaseModel::softDeleteIcinStandartAlanlar($SilenKullaniciId);
        $SetParcalari = [];
        $Yukleme = ['EkleyenUserId' => $KullaniciId];
        foreach ($StandartAlanlar as $Anahtar => $Deger) {
            $SetParcalari[] = "$Anahtar = :$Anahtar";
            $Yukleme[$Anahtar] = $Deger;
        }
        $Sql = "UPDATE {$this->Tablo} SET " . implode(', ', $SetParcalari) . " WHERE EkleyenUserId = :EkleyenUserId AND Sil = 0";
        
        $Etkilenen = 0;
        Transaction::wrap(function () use ($Sql, $Yukleme, &$Etkilenen) {
            $Stmt = $this->Db->prepare($Sql);
            $Stmt->execute($Yukleme);
            $Etkilenen = $Stmt->rowCount();
        });
        
        return $Etkilenen;
    }

    /**
     * Sayfalama ile tum aktif musterileri getirir (superadmin/admin icin)
     * Arama: MusteriKodu veya Unvan icinde buyuk/kucuk harf ve Turkce karakter duyarsiz arama
     */
    public function tumAktiflerSiraliPaginated(int $Page = 1, int $Limit = 10, string $Arama = ''): array
    {
        $Offset = ($Page - 1) * $Limit;
        $AramaKosulu = '';
        $Parametreler = [];
        
        if ($Arama !== '' && mb_strlen($Arama) >= 2) {
            // SQL Server COLLATE ile Turkce karakter duyarsiz arama
            $AramaKosulu = " AND (MusteriKodu COLLATE Turkish_CI_AI LIKE :arama OR Unvan COLLATE Turkish_CI_AI LIKE :arama2)";
            $Parametreler['arama'] = '%' . $Arama . '%';
            $Parametreler['arama2'] = '%' . $Arama . '%';
        }
        
        // Total count
        $CountSql = "SELECT COUNT(*) FROM {$this->Tablo} WHERE Sil = 0" . $AramaKosulu;
        $CountStmt = $this->Db->prepare($CountSql);
        $CountStmt->execute($Parametreler);
        $Total = (int) $CountStmt->fetchColumn();
        
        // Data with user info
        $Sql = "SELECT m.*, u.AdSoyad AS EkleyenAdSoyad, u.KullaniciAdi AS EkleyenKullaniciAdi 
                FROM {$this->Tablo} m 
                LEFT JOIN tnm_user u ON m.EkleyenUserId = u.Id 
                WHERE m.Sil = 0" . str_replace(['MusteriKodu', 'Unvan'], ['m.MusteriKodu', 'm.Unvan'], $AramaKosulu) . "
                ORDER BY m.Id DESC 
                OFFSET :Offset ROWS FETCH NEXT :Limit ROWS ONLY";
        $Stmt = $this->Db->prepare($Sql);
        foreach ($Parametreler as $key => $val) {
            $Stmt->bindValue(':' . $key, $val);
        }
        $Stmt->bindValue(':Offset', $Offset, \PDO::PARAM_INT);
        $Stmt->bindValue(':Limit', $Limit, \PDO::PARAM_INT);
        $Stmt->execute();
        $Sonuclar = $Stmt->fetchAll();
        
        $this->logSelect(['Sil' => 0, 'page' => $Page, 'limit' => $Limit, 'arama' => $Arama], $Sonuclar);
        
        return [
            'data' => $Sonuclar,
            'pagination' => [
                'page' => $Page,
                'limit' => $Limit,
                'total' => $Total,
                'totalPages' => (int) ceil($Total / $Limit)
            ]
        ];
    }

    /**
     * Sayfalama ile kullaniciya ait aktif musterileri getirir
     * Arama: MusteriKodu veya Unvan icinde buyuk/kucuk harf ve Turkce karakter duyarsiz arama
     */
    public function kullaniciyaGoreAktiflerPaginated(int $KullaniciId, int $Page = 1, int $Limit = 10, string $Arama = ''): array
    {
        $Offset = ($Page - 1) * $Limit;
        $AramaKosulu = '';
        $Parametreler = ['Uid' => $KullaniciId];
        
        if ($Arama !== '' && mb_strlen($Arama) >= 2) {
            // SQL Server COLLATE ile Turkce karakter duyarsiz arama
            $AramaKosulu = " AND (MusteriKodu COLLATE Turkish_CI_AI LIKE :arama OR Unvan COLLATE Turkish_CI_AI LIKE :arama2)";
            $Parametreler['arama'] = '%' . $Arama . '%';
            $Parametreler['arama2'] = '%' . $Arama . '%';
        }
        
        // Total count
        $CountSql = "SELECT COUNT(*) FROM {$this->Tablo} WHERE Sil = 0 AND EkleyenUserId = :Uid" . $AramaKosulu;
        $CountStmt = $this->Db->prepare($CountSql);
        $CountStmt->execute($Parametreler);
        $Total = (int) $CountStmt->fetchColumn();
        
        // Data
        $Sql = "SELECT * FROM {$this->Tablo} 
                WHERE Sil = 0 AND EkleyenUserId = :Uid" . $AramaKosulu . "
                ORDER BY Id DESC 
                OFFSET :Offset ROWS FETCH NEXT :Limit ROWS ONLY";
        $Stmt = $this->Db->prepare($Sql);
        foreach ($Parametreler as $key => $val) {
            $Stmt->bindValue(':' . $key, $val);
        }
        $Stmt->bindValue(':Offset', $Offset, \PDO::PARAM_INT);
        $Stmt->bindValue(':Limit', $Limit, \PDO::PARAM_INT);
        $Stmt->execute();
        $Sonuclar = $Stmt->fetchAll();
        
        $this->logSelect(['Sil' => 0, 'EkleyenUserId' => $KullaniciId, 'page' => $Page, 'limit' => $Limit, 'arama' => $Arama], $Sonuclar);
        
        return [
            'data' => $Sonuclar,
            'pagination' => [
                'page' => $Page,
                'limit' => $Limit,
                'total' => $Total,
                'totalPages' => (int) ceil($Total / $Limit)
            ]
        ];
    }
}
