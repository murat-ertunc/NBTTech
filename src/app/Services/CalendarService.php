<?php
/**
 * Calendar Service iş kurallarını uygular.
 * Servis seviyesinde işlem akışlarını sağlar.
 */

namespace App\Services;

use App\Core\Context;
use App\Core\Database;
use App\Core\Transaction;
use App\Services\Logger\ActionLogger;

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

    private static function guvenliGuid(): string
    {
        $Veri = random_bytes(16);
        $Veri[6] = chr((ord($Veri[6]) & 0x0f) | 0x40);
        $Veri[8] = chr((ord($Veri[8]) & 0x3f) | 0x80);
        return strtoupper(vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($Veri), 4)));
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

            self::deleteReminder($KaynakTuru, $KaynakId, $KullaniciId);
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
        $KullaniciId = $KullaniciId ?? Context::kullaniciId() ?? 1;

        $Db = Database::getInstance();

        $Mevcut = $Db->fetchOne(
            "SELECT Id, Durum FROM tbl_takvim WHERE KaynakTuru = :turu AND KaynakId = :id AND Sil = 0",
            ['turu' => $KaynakTuru, 'id' => $KaynakId]
        );

        $VarsayilanDurum = self::getDefaultTakvimDurum();

        if ($Mevcut) {
            return Transaction::wrap(function () use ($Db, $Mevcut, $MusteriId, $HatirlatmaTarihi, $Icerik, $VarsayilanDurum, $KullaniciId, $KaynakTuru, $KaynakId) {
                $Db->execute(
                    "UPDATE tbl_takvim SET
                        MusteriId = :musteriId,
                        TerminTarihi = :tarih,
                        Ozet = :ozet,
                        Durum = CASE WHEN Durum IS NULL OR Durum = 0 THEN :durum ELSE Durum END,
                        DegistirenUserId = :userId,
                        DegisiklikZamani = SYSUTCDATETIME()
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
                ActionLogger::update('tbl_takvim', ['Id' => $Mevcut['Id'], 'KaynakTuru' => $KaynakTuru, 'KaynakId' => $KaynakId], ['Ozet' => $Icerik, 'TerminTarihi' => $HatirlatmaTarihi]);
                return $Mevcut['Id'];
            });
        } else {
            return Transaction::wrap(function () use ($Db, $MusteriId, $HatirlatmaTarihi, $Icerik, $VarsayilanDurum, $KaynakTuru, $KaynakId, $KullaniciId) {
                $Guid = self::guvenliGuid();
                $Simdi = date('Y-m-d H:i:s');

                $Db->execute(
                    "INSERT INTO tbl_takvim (Guid, MusteriId, TerminTarihi, Ozet, Durum, KaynakTuru, KaynakId, Sil, EkleyenUserId, EklemeZamani, DegistirenUserId, DegisiklikZamani)
                    VALUES (:guid, :musteriId, :tarih, :ozet, :durum, :kaynakTuru, :kaynakId, 0, :userId, :simdi, :degistirenUserId, :degisiklikZamani)",
                    [
                        'guid' => $Guid,
                        'musteriId' => $MusteriId,
                        'tarih' => $HatirlatmaTarihi,
                        'ozet' => $Icerik,
                        'durum' => $VarsayilanDurum,
                        'kaynakTuru' => $KaynakTuru,
                        'kaynakId' => $KaynakId,
                        'userId' => $KullaniciId,
                        'simdi' => $Simdi,
                        'degistirenUserId' => $KullaniciId,
                        'degisiklikZamani' => $Simdi
                    ]
                );

                $SonId = $Db->fetchOne("SELECT SCOPE_IDENTITY() as id");
                $Id = $SonId ? (int)$SonId['id'] : null;
                if ($Id) {
                    ActionLogger::insert('tbl_takvim', ['Id' => $Id], ['KaynakTuru' => $KaynakTuru, 'KaynakId' => $KaynakId, 'Ozet' => $Icerik]);
                }
                return $Id;
            });
        }
    }

    public static function deleteReminder(string $KaynakTuru, int $KaynakId, ?int $KullaniciId = null): bool
    {
        $KullaniciId = $KullaniciId ?? Context::kullaniciId() ?? 1;
        $Db = Database::getInstance();
        return Transaction::wrap(function () use ($Db, $KaynakTuru, $KaynakId, $KullaniciId) {
            $Sonuc = $Db->execute(
                "UPDATE tbl_takvim SET Sil = 1, DegistirenUserId = :userId, DegisiklikZamani = SYSUTCDATETIME() WHERE KaynakTuru = :turu AND KaynakId = :id AND Sil = 0",
                ['userId' => $KullaniciId, 'turu' => $KaynakTuru, 'id' => $KaynakId]
            );
            ActionLogger::delete('tbl_takvim', ['KaynakTuru' => $KaynakTuru, 'KaynakId' => $KaynakId]);
            return $Sonuc;
        });
    }

    public static function deleteRemindersByCustomer(int $MusteriId, string $KaynakTuru, ?int $KullaniciId = null): bool
    {
        $KullaniciId = $KullaniciId ?? Context::kullaniciId() ?? 1;
        $Db = Database::getInstance();
        return Transaction::wrap(function () use ($Db, $MusteriId, $KaynakTuru, $KullaniciId) {
            $Sonuc = $Db->execute(
                "UPDATE tbl_takvim SET Sil = 1, DegistirenUserId = :userId, DegisiklikZamani = SYSUTCDATETIME() WHERE MusteriId = :musteriId AND KaynakTuru = :turu AND Sil = 0",
                ['userId' => $KullaniciId, 'musteriId' => $MusteriId, 'turu' => $KaynakTuru]
            );
            ActionLogger::delete('tbl_takvim', ['MusteriId' => $MusteriId, 'KaynakTuru' => $KaynakTuru]);
            return $Sonuc;
        });
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
