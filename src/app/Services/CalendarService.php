<?php

namespace App\Services;

use App\Core\Database;

/**
 * CalendarService - Takvim işlemleri için servis sınıfı
 * 
 * Çeşitli modüllerden takvim aktivitesi oluşturmak için kullanılır.
 * Parametrelerden hatırlatma gün sayısını ve aktif/pasif durumunu okur.
 */
class CalendarService
{
    /**
     * Kaynak türleri ve parametre isimleri eşleştirmesi
     */
    private static $KaynakTurleri = [
        'gorusme' => [
            'gunParam' => 'gorusme_hatirlatma_gun',
            'aktifParam' => 'gorusme_hatirlatma_aktif',
            'defaultGun' => 1,
            'icerikPrefix' => 'Görüşme Hatırlatma'
        ],
        'teklif' => [
            'gunParam' => 'teklif_gecerlilik_hatirlatma_gun',
            'aktifParam' => 'teklif_gecerlilik_hatirlatma_aktif',
            'defaultGun' => 3,
            'icerikPrefix' => 'Teklif Geçerlilik Hatırlatma'
        ],
        'sozlesme' => [
            'gunParam' => 'sozlesme_hatirlatma_gun',
            'aktifParam' => 'sozlesme_hatirlatma_aktif',
            'defaultGun' => 7,
            'icerikPrefix' => 'Sözleşme Hatırlatma'
        ],
        'damgavergisi' => [
            'gunParam' => 'damgavergisi_hatirlatma_gun',
            'aktifParam' => 'damgavergisi_hatirlatma_aktif',
            'defaultGun' => 7,
            'icerikPrefix' => 'Damga Vergisi Hatırlatma'
        ],
        'teminat' => [
            'gunParam' => 'teminat_termin_hatirlatma_gun',
            'aktifParam' => 'teminat_termin_hatirlatma_aktif',
            'defaultGun' => 7,
            'icerikPrefix' => 'Teminat Termin Hatırlatma'
        ],
        'fatura' => [
            'gunParam' => 'fatura_hatirlatma_gun',
            'aktifParam' => 'fatura_hatirlatma_aktif',
            'defaultGun' => 3,
            'icerikPrefix' => 'Fatura Hatırlatma'
        ],
        'odeme' => [
            'gunParam' => 'odeme_hatirlatma_gun',
            'aktifParam' => 'odeme_hatirlatma_aktif',
            'defaultGun' => 3,
            'icerikPrefix' => 'Ödeme Hatırlatma'
        ]
    ];

    /**
     * Parametre değerini al
     */
    private static function getParameter(string $ParametreAdi, $Varsayilan = null)
    {
        $Db = Database::getInstance();
        
        // Kod alanina bak
        $Sonuc = $Db->fetchOne(
            "SELECT Deger FROM tbl_parametre WHERE Kod = :kod AND Grup = 'genel' AND Sil = 0",
            ['kod' => $ParametreAdi]
        );
        
        return $Sonuc ? $Sonuc['Deger'] : $Varsayilan;
    }

    /**
     * Hatırlatma aktif mi kontrol et
     */
    public static function isReminderActive(string $KaynakTuru): bool
    {
        if (!isset(self::$KaynakTurleri[$KaynakTuru])) {
            return false;
        }

        $Konfig = self::$KaynakTurleri[$KaynakTuru];
        $Aktif = self::getParameter($Konfig['aktifParam'], '1');
        return $Aktif === '1' || $Aktif === 1 || $Aktif === true;
    }

    /**
     * Hatırlatma gün sayısını al
     */
    public static function getReminderDays(string $KaynakTuru): int
    {
        if (!isset(self::$KaynakTurleri[$KaynakTuru])) {
            return 0;
        }

        $Konfig = self::$KaynakTurleri[$KaynakTuru];
        $Gun = self::getParameter($Konfig['gunParam'], $Konfig['defaultGun']);
        return (int)$Gun;
    }

    /**
     * Hatırlatma tarihini hesapla
     * 
     * @param string $KaynakTuru Kaynak türü
     * @param string $HedefTarih Hedef tarih (YYYY-MM-DD formatında)
     * @return string|null Hatırlatma tarihi veya null
     */
    public static function calculateReminderDate(string $KaynakTuru, string $HedefTarih): ?string
    {
        if (!self::isReminderActive($KaynakTuru)) {
            return null;
        }

        $Gunler = self::getReminderDays($KaynakTuru);
        if ($Gunler <= 0) {
            return $HedefTarih;
        }

        $Tarih = new \DateTime($HedefTarih);
        $Tarih->modify("-{$Gunler} days");
        return $Tarih->format('Y-m-d');
    }

    /**
     * Takvim kaydı oluştur veya güncelle
     * 
     * @param int $MusteriId Müşteri ID
     * @param string $KaynakTuru Kaynak türü (gorusme, teklif, sozlesme, vb.)
     * @param int $KaynakId Kaynak ID
     * @param string $HedefTarih Hedef tarih (YYYY-MM-DD formatında)
     * @param string|null $OzelIcerik Özel içerik (null ise varsayılan kullanılır)
     * @param int|null $KullaniciId Kullanıcı ID (null ise mevcut kullanıcı)
     * @return int|null Takvim kayıt ID veya null
     */
    public static function createOrUpdateReminder(
        int $MusteriId,
        string $KaynakTuru,
        int $KaynakId,
        string $HedefTarih,
        ?string $OzelIcerik = null,
        ?int $KullaniciId = null
    ): ?int {
        // Hatırlatma aktif mi kontrol et
        if (!self::isReminderActive($KaynakTuru)) {
            // Pasifse mevcut kaydı sil
            self::deleteReminder($KaynakTuru, $KaynakId);
            return null;
        }

        // Hatırlatma tarihini hesapla
        $HatirlatmaTarihi = self::calculateReminderDate($KaynakTuru, $HedefTarih);
        if (!$HatirlatmaTarihi) {
            return null;
        }

        // İçerik belirle
        $Konfig = self::$KaynakTurleri[$KaynakTuru] ?? null;
        if (!$Konfig) {
            return null;
        }

        $Icerik = $OzelIcerik ?? $Konfig['icerikPrefix'];
        $KullaniciId = $KullaniciId ?? ($_SESSION['user_id'] ?? 1);

        $Db = Database::getInstance();

        // Mevcut kayıt var mı kontrol et
        $Mevcut = $Db->fetchOne(
            "SELECT Id FROM tbl_takvim WHERE KaynakTuru = :turu AND KaynakId = :id AND Sil = 0",
            ['turu' => $KaynakTuru, 'id' => $KaynakId]
        );

        if ($Mevcut) {
            // Güncelle
            $Db->execute(
                "UPDATE tbl_takvim SET 
                    MusteriId = :musteriId,
                    TerminTarihi = :tarih,
                    Ozet = :ozet,
                    DegistirenUserId = :userId,
                    DegisiklikZamani = GETDATE()
                WHERE Id = :takvimId",
                [
                    'musteriId' => $MusteriId,
                    'tarih' => $HatirlatmaTarihi,
                    'ozet' => $Icerik,
                    'userId' => $KullaniciId,
                    'takvimId' => $Mevcut['Id']
                ]
            );
            return $Mevcut['Id'];
        } else {
            // Yeni kayıt oluştur - Guid oluştur
            $Guid = strtoupper(sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X',
                mt_rand(0, 65535), mt_rand(0, 65535),
                mt_rand(0, 65535),
                mt_rand(16384, 20479),
                mt_rand(32768, 49151),
                mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535)
            ));
            
            $Db->execute(
                "INSERT INTO tbl_takvim (Guid, MusteriId, TerminTarihi, Ozet, KaynakTuru, KaynakId, Sil, EkleyenUserId, EklemeZamani)
                VALUES (:guid, :musteriId, :tarih, :ozet, :kaynakTuru, :kaynakId, 0, :userId, GETDATE())",
                [
                    'guid' => $Guid,
                    'musteriId' => $MusteriId,
                    'tarih' => $HatirlatmaTarihi,
                    'ozet' => $Icerik,
                    'kaynakTuru' => $KaynakTuru,
                    'kaynakId' => $KaynakId,
                    'userId' => $KullaniciId
                ]
            );
            
            // Son eklenen ID'yi al
            $SonId = $Db->fetchOne("SELECT SCOPE_IDENTITY() as id");
            return $SonId ? (int)$SonId['id'] : null;
        }
    }

    /**
     * Takvim kaydını sil (soft delete)
     * 
     * @param string $KaynakTuru Kaynak türü
     * @param int $KaynakId Kaynak ID
     * @return bool
     */
    public static function deleteReminder(string $KaynakTuru, int $KaynakId): bool
    {
        $Db = Database::getInstance();
        return $Db->execute(
            "UPDATE tbl_takvim SET Sil = 1 WHERE KaynakTuru = :turu AND KaynakId = :id",
            ['turu' => $KaynakTuru, 'id' => $KaynakId]
        );
    }

    /**
     * Müşterinin belirli türdeki tüm hatırlatmalarını sil
     * 
     * @param int $MusteriId Müşteri ID
     * @param string $KaynakTuru Kaynak türü
     * @return bool
     */
    public static function deleteRemindersByCustomer(int $MusteriId, string $KaynakTuru): bool
    {
        $Db = Database::getInstance();
        return $Db->execute(
            "UPDATE tbl_takvim SET Sil = 1 WHERE MusteriId = :musteriId AND KaynakTuru = :turu",
            ['musteriId' => $MusteriId, 'turu' => $KaynakTuru]
        );
    }

    /**
     * Tüm hatırlatma parametrelerini getir
     * 
     * @return array
     */
    public static function getAllReminderSettings(): array
    {
        $Ayarlar = [];
        foreach (self::$KaynakTurleri as $Tur => $Konfig) {
            $Ayarlar[$Tur] = [
                'gun' => self::getReminderDays($Tur),
                'aktif' => self::isReminderActive($Tur),
                'label' => $Konfig['icerikPrefix']
            ];
        }
        return $Ayarlar;
    }
}
