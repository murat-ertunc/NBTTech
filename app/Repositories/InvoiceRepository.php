<?php

namespace App\Repositories;

class InvoiceRepository extends BaseRepository
{
    protected string $Tablo = 'tbl_fatura';

    /**
     * Tek fatura getir (MusteriUnvan dahil)
     */
    public function bul(int $Id): ?array
    {
        $Sql = "
            SELECT f.*, 
                   m.Unvan as MusteriUnvan,
                   p.ProjeAdi,
                   (SELECT COALESCE(SUM(o.Tutar),0) FROM tbl_odeme o WHERE o.FaturaId = f.Id AND o.Sil=0) as OdenenTutar,
                   f.Tutar - (SELECT COALESCE(SUM(o.Tutar),0) FROM tbl_odeme o WHERE o.FaturaId = f.Id AND o.Sil=0) as Kalan
            FROM tbl_fatura f
            LEFT JOIN tbl_musteri m ON f.MusteriId = m.Id
            LEFT JOIN tbl_proje p ON f.ProjeId = p.Id
            WHERE f.Id = :Id AND f.Sil = 0
        ";
        $Stmt = $this->Db->prepare($Sql);
        $Stmt->execute(['Id' => $Id]);
        $Sonuc = $Stmt->fetch();
        $this->logSelect(['Id' => $Id, 'Sil' => 0], $Sonuc ? [$Sonuc] : []);
        return $Sonuc ?: null;
    }

    public function musteriyeGore(int $MusteriId): array
    {
        $Sql = "
            SELECT f.*, 
                   m.Unvan as MusteriUnvan,
                   (SELECT COALESCE(SUM(o.Tutar),0) FROM tbl_odeme o WHERE o.FaturaId = f.Id AND o.Sil=0) as OdenenTutar,
                   f.Tutar - (SELECT COALESCE(SUM(o.Tutar),0) FROM tbl_odeme o WHERE o.FaturaId = f.Id AND o.Sil=0) as Kalan
            FROM tbl_fatura f
            LEFT JOIN tbl_musteri m ON f.MusteriId = m.Id
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
                   m.Unvan as MusteriUnvan,
                   (SELECT COALESCE(SUM(o.Tutar),0) FROM tbl_odeme o WHERE o.FaturaId = f.Id AND o.Sil=0) as OdenenTutar,
                   f.Tutar - (SELECT COALESCE(SUM(o.Tutar),0) FROM tbl_odeme o WHERE o.FaturaId = f.Id AND o.Sil=0) as Kalan
            FROM tbl_fatura f
            LEFT JOIN tbl_musteri m ON f.MusteriId = m.Id
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

    /**
     * Fatura kalemlerini kaydet (önce mevcut kalemleri sil, sonra yenilerini ekle)
     */
    public function kaydetKalemler(int $FaturaId, array $Kalemler, int $KullaniciId): void
    {
        // Mevcut kalemleri soft delete yap
        $SqlSil = "UPDATE tbl_fatura_kalem SET Sil = 1, DegisiklikZamani = GETDATE(), DegistirenUserId = :UserId WHERE FaturaId = :FaturaId";
        $StmtSil = $this->Db->prepare($SqlSil);
        $StmtSil->execute(['FaturaId' => $FaturaId, 'UserId' => $KullaniciId]);
        
        // Yeni kalemleri ekle
        foreach ($Kalemler as $Kalem) {
            $Miktar = isset($Kalem['Miktar']) ? (float)$Kalem['Miktar'] : 0;
            $Aciklama = isset($Kalem['Aciklama']) ? trim((string)$Kalem['Aciklama']) : null;
            $KdvOran = isset($Kalem['KdvOran']) ? (float)$Kalem['KdvOran'] : 0;
            $BirimFiyat = isset($Kalem['BirimFiyat']) ? (float)$Kalem['BirimFiyat'] : 0;
            $Tutar = isset($Kalem['Tutar']) ? (float)$Kalem['Tutar'] : 0;
            $Sira = isset($Kalem['Sira']) ? (int)$Kalem['Sira'] : 1;
            
            // Boş kalemleri atla
            if ($Miktar <= 0 && empty($Aciklama) && $BirimFiyat <= 0) {
                continue;
            }
            
            $SqlEkle = "INSERT INTO tbl_fatura_kalem (FaturaId, Sira, Miktar, Aciklama, KdvOran, BirimFiyat, Tutar, OlusturmaZamani, OlusturanUserId, Sil)
                        VALUES (:FaturaId, :Sira, :Miktar, :Aciklama, :KdvOran, :BirimFiyat, :Tutar, GETDATE(), :UserId, 0)";
            $StmtEkle = $this->Db->prepare($SqlEkle);
            $StmtEkle->execute([
                'FaturaId' => $FaturaId,
                'Sira' => $Sira,
                'Miktar' => $Miktar,
                'Aciklama' => $Aciklama,
                'KdvOran' => $KdvOran,
                'BirimFiyat' => $BirimFiyat,
                'Tutar' => $Tutar,
                'UserId' => $KullaniciId
            ]);
        }
    }

    /**
     * Fatura kalemlerini getir
     */
    public function getKalemler(int $FaturaId): array
    {
        $Sql = "SELECT * FROM tbl_fatura_kalem WHERE FaturaId = :FaturaId AND Sil = 0 ORDER BY Sira";
        $Stmt = $this->Db->prepare($Sql);
        $Stmt->execute(['FaturaId' => $FaturaId]);
        return $Stmt->fetchAll();
    }

    /**
     * Fatura ile ilişkili dosyaları getir
     */
    public function getDosyalar(int $FaturaId): array
    {
        $Sql = "SELECT * FROM tbl_dosya WHERE FaturaId = :FaturaId AND Sil = 0 ORDER BY OlusturmaZamani DESC";
        $Stmt = $this->Db->prepare($Sql);
        $Stmt->execute(['FaturaId' => $FaturaId]);
        return $Stmt->fetchAll();
    }

    /**
     * Fatura için takvim hatırlatması oluştur
     */
    public function takvimHatirlatmaOlustur(int $FaturaId, int $MusteriId, ?int $ProjeId, string $Tarih, int $Sure, string $SureTipi, int $KullaniciId): void
    {
        // Takvim tarihini hesapla
        $BaslangicTarih = new \DateTime($Tarih);
        switch ($SureTipi) {
            case 'gun':
                $BaslangicTarih->modify("+{$Sure} days");
                break;
            case 'hafta':
                $BaslangicTarih->modify("+{$Sure} weeks");
                break;
            case 'ay':
                $BaslangicTarih->modify("+{$Sure} months");
                break;
            case 'yil':
                $BaslangicTarih->modify("+{$Sure} years");
                break;
        }
        
        $HatirlatmaTarihi = $BaslangicTarih->format('Y-m-d');
        $Ozet = "Fatura #{$FaturaId} hatırlatması - Tarih: {$Tarih}";
        
        // tbl_takvim'e kaydet
        $Sql = "INSERT INTO tbl_takvim (MusteriId, ProjeId, BaslangicTarihi, BitisTarihi, Ozet, OlusturmaZamani, OlusturanUserId, Sil)
                VALUES (:MusteriId, :ProjeId, :BaslangicTarihi, :BitisTarihi, :Ozet, GETDATE(), :UserId, 0)";
        $Stmt = $this->Db->prepare($Sql);
        $Stmt->execute([
            'MusteriId' => $MusteriId,
            'ProjeId' => $ProjeId,
            'BaslangicTarihi' => $HatirlatmaTarihi,
            'BitisTarihi' => $HatirlatmaTarihi,
            'Ozet' => $Ozet,
            'UserId' => $KullaniciId
        ]);
    }

    /**
     * Müşteriye ait yıl ve döviz bazlı cari özet
     * Yıl bazlı fatura toplamlarını döviz cinsine göre gruplandırır
     */
    public function cariOzet(int $MusteriId): array
    {
        $Sql = "
            WITH FaturaOdemeler AS (
                SELECT 
                    f.Id,
                    f.Tarih,
                    f.Tutar,
                    ISNULL(f.DovizCinsi, 'TL') as DovizCinsi,
                    ISNULL((SELECT COALESCE(SUM(o.Tutar), 0) FROM tbl_odeme o WHERE o.FaturaId = f.Id AND o.Sil = 0), 0) as Odenen
                FROM tbl_fatura f
                WHERE f.Sil = 0 AND f.MusteriId = :MId
            )
            SELECT 
                YEAR(Tarih) as Yil,
                DovizCinsi,
                SUM(Tutar) as ToplamTutar,
                SUM(Odenen) as ToplamOdenen,
                SUM(Tutar) - SUM(Odenen) as ToplamKalan,
                COUNT(*) as FaturaAdedi
            FROM FaturaOdemeler
            GROUP BY YEAR(Tarih), DovizCinsi
            ORDER BY YEAR(Tarih) DESC, DovizCinsi
        ";
        $Stmt = $this->Db->prepare($Sql);
        $Stmt->execute(['MId' => $MusteriId]);
        $Sonuclar = $Stmt->fetchAll();
        $this->logSelect(['CariOzet' => true, 'MusteriId' => $MusteriId], $Sonuclar);
        return $Sonuclar;
    }
}
