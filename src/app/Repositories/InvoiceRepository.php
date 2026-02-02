<?php

namespace App\Repositories;

use App\Core\Transaction;
use App\Services\CalendarService;

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

    public function musteriyeGore(int $MusteriId, bool $SadeceOdenmemis = false): array
    {
        $KalanFiltre = $SadeceOdenmemis 
            ? "AND (f.Tutar - (SELECT COALESCE(SUM(o.Tutar),0) FROM tbl_odeme o WHERE o.FaturaId = f.Id AND o.Sil=0)) > 0"
            : "";
        
        $Sql = "
            SELECT f.*, 
                   m.Unvan as MusteriUnvan,
                   p.ProjeAdi,
                   (SELECT COALESCE(SUM(o.Tutar),0) FROM tbl_odeme o WHERE o.FaturaId = f.Id AND o.Sil=0) as OdenenTutar,
                   f.Tutar - (SELECT COALESCE(SUM(o.Tutar),0) FROM tbl_odeme o WHERE o.FaturaId = f.Id AND o.Sil=0) as Kalan
            FROM tbl_fatura f
            LEFT JOIN tbl_musteri m ON f.MusteriId = m.Id
            LEFT JOIN tbl_proje p ON f.ProjeId = p.Id
            WHERE f.Sil = 0 AND f.MusteriId = :MId {$KalanFiltre}
            ORDER BY f.Tarih DESC, f.Id DESC
        ";
        $Stmt = $this->Db->prepare($Sql);
        $Stmt->execute(['MId' => $MusteriId]);
        $Sonuclar = $Stmt->fetchAll();
        $this->logSelect(['Sil' => 0, 'MusteriId' => $MusteriId, 'SadeceOdenmemis' => $SadeceOdenmemis], $Sonuclar);
        return $Sonuclar;
    }

    public function musteriyeGorePaginated(int $MusteriId, int $Sayfa = 1, int $Limit = 10): array
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
            WHERE f.Sil = 0 AND f.MusteriId = :MId 
            ORDER BY f.Tarih DESC, f.Id DESC
        ";
        $Sonuc = $this->paginatedQuery($Sql, ['MId' => $MusteriId], $Sayfa, $Limit);
        $this->logSelect(['Sil' => 0, 'MusteriId' => $MusteriId, 'page' => $Sayfa], $Sonuc['data']);
        return $Sonuc;
    }

    public function tumAktifler(): array
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
            WHERE f.Sil = 0
            ORDER BY f.Tarih DESC, f.Id DESC
        ";
        $Stmt = $this->Db->query($Sql);
        $Sonuclar = $Stmt->fetchAll();
        $this->logSelect(['Sil' => 0], $Sonuclar);
        return $Sonuclar;
    }

    public function tumAktiflerPaginated(int $Sayfa = 1, int $Limit = 10): array
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
            WHERE f.Sil = 0
            ORDER BY f.Tarih DESC, f.Id DESC
        ";
        $Sonuc = $this->paginatedQuery($Sql, [], $Sayfa, $Limit);
        $this->logSelect(['Sil' => 0, 'page' => $Sayfa], $Sonuc['data']);
        return $Sonuc;
    }

    /*
     * Termin tarihi gecmis (odenmemis) faturalari, ilerideki takvim/alarm logic'inde kullanmak uzere cekebilecegimiz
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
     * Fatura kalemlerini kaydediyoruz - once mevcut kalemleri soft delete yapip sonra yenilerini ekliyoruz
     * Birden fazla INSERT/UPDATE islemi yaptigimiz icin Transaction::wrap() kullaniyoruz
     */
    public function kaydetKalemler(int $FaturaId, array $Kalemler, int $KullaniciId): void
    {
        Transaction::wrap(function() use ($FaturaId, $Kalemler, $KullaniciId) {
            // Mevcut kalemleri soft delete yapiyoruz
            $SqlSil = "UPDATE tbl_fatura_kalem SET Sil = 1, DegisiklikZamani = GETDATE(), DegistirenUserId = :UserId WHERE FaturaId = :FaturaId";
            $StmtSil = $this->Db->prepare($SqlSil);
            $StmtSil->execute(['FaturaId' => $FaturaId, 'UserId' => $KullaniciId]);
            
            // Yeni kalemleri ekliyoruz
            foreach ($Kalemler as $Kalem) {
                $Miktar = isset($Kalem['Miktar']) ? (float)$Kalem['Miktar'] : 0;
                $Aciklama = isset($Kalem['Aciklama']) ? trim((string)$Kalem['Aciklama']) : null;
                $KdvOran = isset($Kalem['KdvOran']) ? (float)$Kalem['KdvOran'] : 0;
                $BirimFiyat = isset($Kalem['BirimFiyat']) ? (float)$Kalem['BirimFiyat'] : 0;
                $Tutar = isset($Kalem['Tutar']) ? (float)$Kalem['Tutar'] : 0;
                $Sira = isset($Kalem['Sira']) ? (int)$Kalem['Sira'] : 1;
                
                // Bos kalemleri atliyoruz
                if ($Miktar <= 0 && empty($Aciklama) && $BirimFiyat <= 0) {
                    continue;
                }
                
                $SqlEkle = "INSERT INTO tbl_fatura_kalem (FaturaId, Sira, Miktar, Aciklama, KdvOran, BirimFiyat, Tutar, EklemeZamani, EkleyenUserId, Sil)
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
        });
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
     * Fatura ile iliskili dosyalari getir
     */
    public function getDosyalar(int $FaturaId): array
    {
        $Sql = "SELECT * FROM tbl_dosya WHERE FaturaId = :FaturaId AND Sil = 0 ORDER BY EklemeZamani DESC";
        $Stmt = $this->Db->prepare($Sql);
        $Stmt->execute(['FaturaId' => $FaturaId]);
        return $Stmt->fetchAll();
    }

    /**
     * Fatura icin takvim kayitlari olusturur
     * - Her zaman fatura gunu icin temel kayit ekler
     * - Hatirlatma aktif ise ek sure kadar ileri tarih icin ikinci kayit ekler
     */
    public function takvimKayitlariniOlustur(int $FaturaId, int $MusteriId, ?int $ProjeId, string $Tarih, ?int $Sure, ?string $SureTipi, bool $HatirlatmaAktif, int $KullaniciId): void
    {
        $FaturaTarihi = (new \DateTime($Tarih))->format('Y-m-d');
        $this->ekleTakvimKaydi($MusteriId, $ProjeId, $FaturaTarihi, "Fatura #{$FaturaId} - Tarih: {$FaturaTarihi}", $KullaniciId);

        if ($HatirlatmaAktif && $Sure !== null && $Sure > 0) {
            $SureTipi = $SureTipi ?: 'gun';
            $HatirlatmaTarih = new \DateTime($Tarih);
            switch ($SureTipi) {
                case 'hafta':
                    $HatirlatmaTarih->modify("+{$Sure} weeks");
                    break;
                case 'ay':
                    $HatirlatmaTarih->modify("+{$Sure} months");
                    break;
                case 'yil':
                    $HatirlatmaTarih->modify("+{$Sure} years");
                    break;
                default:
                    $HatirlatmaTarih->modify("+{$Sure} days");
                    break;
            }
            $HatirlatmaTarihi = $HatirlatmaTarih->format('Y-m-d');
            $Ozet = "Fatura #{$FaturaId} Hatırlatması - Tarih: {$FaturaTarihi}";
            $this->ekleTakvimKaydi($MusteriId, $ProjeId, $HatirlatmaTarihi, $Ozet, $KullaniciId);
        }
    }

    /**
     * Geriye donuk uyumluluk icin eski imza: yalnizca hatirlatma kaydi ekler (temel kaydi eklemez)
     */
    public function takvimHatirlatmaOlustur(int $FaturaId, int $MusteriId, ?int $ProjeId, string $Tarih, int $Sure, string $SureTipi, int $KullaniciId): void
    {
        $this->takvimKayitlariniOlustur($FaturaId, $MusteriId, $ProjeId, $Tarih, $Sure, $SureTipi, true, $KullaniciId);
    }

    /**
     * Fatura ile iliskili takvim kayitlarini soft delete yap
     * Update islemlerinde mevcut takvim kayitlarini silmek icin kullanilir
     */
    public function takvimKayitlariniSil(int $FaturaId, int $KullaniciId): void
    {
        // Fatura ID'si ile iliskili tum takvim kayitlarini bul ve soft delete yap
        // Ozet alaninda "Fatura #ID" iceren kayitlari sil
        $Sql = "UPDATE tbl_takvim 
                SET Sil = 1, DegisiklikZamani = GETDATE(), DegistirenUserId = :UserId 
                WHERE Ozet LIKE :OzetPattern AND Sil = 0";
        $Stmt = $this->Db->prepare($Sql);
        $Stmt->execute([
            'UserId' => $KullaniciId,
            'OzetPattern' => "Fatura #$FaturaId%"
        ]);
    }

    /**
     * Tekil takvim kaydi ekleme helper'i
     */
    private function ekleTakvimKaydi(int $MusteriId, ?int $ProjeId, string $TerminTarihi, string $Ozet, int $KullaniciId): void
    {
        $VarsayilanDurum = CalendarService::getDefaultTakvimDurum();
        // Guid olustur
        $Guid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
        
        $Sql = "INSERT INTO tbl_takvim (Guid, MusteriId, ProjeId, TerminTarihi, Ozet, Durum, EklemeZamani, EkleyenUserId, DegisiklikZamani, DegistirenUserId, Sil)
            VALUES (:Guid, :MusteriId, :ProjeId, :TerminTarihi, :Ozet, :Durum, GETDATE(), :UserId, GETDATE(), :UserId2, 0)";
        $Stmt = $this->Db->prepare($Sql);
        $Stmt->execute([
            'Guid' => $Guid,
            'MusteriId' => $MusteriId,
            'ProjeId' => $ProjeId,
            'TerminTarihi' => $TerminTarihi,
            'Ozet' => $Ozet,
            'Durum' => $VarsayilanDurum,
            'UserId' => $KullaniciId,
            'UserId2' => $KullaniciId
        ]);
    }

    /**
     * Musteriye ait yil ve doviz bazli cari ozet
     * Yil bazli fatura toplamlarini doviz cinsine gore gruplandirir
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
