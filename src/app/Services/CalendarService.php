<?php
/**
 * Calendar Service iş kurallarını uygular.
 * Servis seviyesinde işlem akışlarını sağlar.
 */

namespace App\Services;

use App\Core\Database;

class CalendarService
{

    public static function getDefaultTakvimDurum(): int
    {
        $Db = Database::getInstance();
        $Sonuc = $Db->fetchOne(
            "SELECT TOP 1 Kod FROM tbl_parametre WHERE Grup = 'durum_takvim' AND Sil = 0 AND Aktif = 1 AND Varsayilan = 1 ORDER BY Sira"
        );
        return $Sonuc ? (int)$Sonuc['Kod'] : 1;
    }

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

    private static function getParameter(string $ParametreAdi, $Varsayilan = null)
    {
        $Db = Database::getInstance();

        $Sonuc = $Db->fetchOne(
            "SELECT Deger FROM tbl_parametre WHERE Kod = :kod AND Grup = 'genel' AND Sil = 0",
            ['kod' => $ParametreAdi]
        );

        return $Sonuc ? $Sonuc['Deger'] : $Varsayilan;
    }

    public static function isReminderActive(string $KaynakTuru): bool
    {
        if (!isset(self::$KaynakTurleri[$KaynakTuru])) {
            return false;
        }

        $Konfig = self::$KaynakTurleri[$KaynakTuru];
        $Aktif = self::getParameter($Konfig['aktifParam'], '1');
        return $Aktif === '1' || $Aktif === 1 || $Aktif === true;
    }

    public static function getReminderDays(string $KaynakTuru): int
    {
        if (!isset(self::$KaynakTurleri[$KaynakTuru])) {
            return 0;
        }

        $Konfig = self::$KaynakTurleri[$KaynakTuru];
        $Gun = self::getParameter($Konfig['gunParam'], $Konfig['defaultGun']);
        return (int)$Gun;
    }

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

    public static function createOrUpdateReminder(
        int $MusteriId,
        string $KaynakTuru,
        int $KaynakId,
        string $HedefTarih,
        ?string $OzelIcerik = null,
        ?int $KullaniciId = null
    ): ?int {

        if (!self::isReminderActive($KaynakTuru)) {

            self::deleteReminder($KaynakTuru, $KaynakId);
            return null;
        }

        $HatirlatmaTarihi = self::calculateReminderDate($KaynakTuru, $HedefTarih);
        if (!$HatirlatmaTarihi) {
            return null;
        }

        $Konfig = self::$KaynakTurleri[$KaynakTuru] ?? null;
        if (!$Konfig) {
            return null;
        }

        $Icerik = $OzelIcerik ?? $Konfig['icerikPrefix'];
        $KullaniciId = $KullaniciId ?? ($_SESSION['user_id'] ?? 1);

        $Db = Database::getInstance();

        $Mevcut = $Db->fetchOne(
            "SELECT Id, Durum FROM tbl_takvim WHERE KaynakTuru = :turu AND KaynakId = :id AND Sil = 0",
            ['turu' => $KaynakTuru, 'id' => $KaynakId]
        );

        $VarsayilanDurum = self::getDefaultTakvimDurum();

        if ($Mevcut) {

            $Db->execute(
                "UPDATE tbl_takvim SET
                    MusteriId = :musteriId,
                    TerminTarihi = :tarih,
                    Ozet = :ozet,
                    Durum = CASE WHEN Durum IS NULL OR Durum = 0 THEN :durum ELSE Durum END,
                    DegistirenUserId = :userId,
                    DegisiklikZamani = GETDATE()
                WHERE Id = :takvimId",
                [
                    'musteriId' => $MusteriId,
                    'tarih' => $HatirlatmaTarihi,
                    'ozet' => $Icerik,
                    'durum' => $VarsayilanDurum,
                    'userId' => $KullaniciId,
                    'takvimId' => $Mevcut['Id']
                ]
            );
            return $Mevcut['Id'];
        } else {

            $Guid = strtoupper(sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X',
                mt_rand(0, 65535), mt_rand(0, 65535),
                mt_rand(0, 65535),
                mt_rand(16384, 20479),
                mt_rand(32768, 49151),
                mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535)
            ));

            $Db->execute(
                "INSERT INTO tbl_takvim (Guid, MusteriId, TerminTarihi, Ozet, Durum, KaynakTuru, KaynakId, Sil, EkleyenUserId, EklemeZamani)
                VALUES (:guid, :musteriId, :tarih, :ozet, :durum, :kaynakTuru, :kaynakId, 0, :userId, GETDATE())",
                [
                    'guid' => $Guid,
                    'musteriId' => $MusteriId,
                    'tarih' => $HatirlatmaTarihi,
                    'ozet' => $Icerik,
                    'durum' => $VarsayilanDurum,
                    'kaynakTuru' => $KaynakTuru,
                    'kaynakId' => $KaynakId,
                    'userId' => $KullaniciId
                ]
            );

            $SonId = $Db->fetchOne("SELECT SCOPE_IDENTITY() as id");
            return $SonId ? (int)$SonId['id'] : null;
        }
    }

    public static function deleteReminder(string $KaynakTuru, int $KaynakId): bool
    {
        $Db = Database::getInstance();
        return $Db->execute(
            "UPDATE tbl_takvim SET Sil = 1 WHERE KaynakTuru = :turu AND KaynakId = :id",
            ['turu' => $KaynakTuru, 'id' => $KaynakId]
        );
    }

    public static function deleteRemindersByCustomer(int $MusteriId, string $KaynakTuru): bool
    {
        $Db = Database::getInstance();
        return $Db->execute(
            "UPDATE tbl_takvim SET Sil = 1 WHERE MusteriId = :musteriId AND KaynakTuru = :turu",
            ['musteriId' => $MusteriId, 'turu' => $KaynakTuru]
        );
    }

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
