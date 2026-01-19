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
    private static $sourceTypes = [
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
    private static function getParameter(string $paramName, $default = null)
    {
        $db = Database::getInstance();
        
        // Kod alanina bak
        $result = $db->fetchOne(
            "SELECT Deger FROM tbl_parametre WHERE Kod = :kod AND Grup = 'genel' AND Sil = 0",
            ['kod' => $paramName]
        );
        
        return $result ? $result['Deger'] : $default;
    }

    /**
     * Hatırlatma aktif mi kontrol et
     */
    public static function isReminderActive(string $sourceType): bool
    {
        if (!isset(self::$sourceTypes[$sourceType])) {
            return false;
        }

        $config = self::$sourceTypes[$sourceType];
        $aktif = self::getParameter($config['aktifParam'], '1');
        return $aktif === '1' || $aktif === 1 || $aktif === true;
    }

    /**
     * Hatırlatma gün sayısını al
     */
    public static function getReminderDays(string $sourceType): int
    {
        if (!isset(self::$sourceTypes[$sourceType])) {
            return 0;
        }

        $config = self::$sourceTypes[$sourceType];
        $gun = self::getParameter($config['gunParam'], $config['defaultGun']);
        return (int)$gun;
    }

    /**
     * Hatırlatma tarihini hesapla
     * 
     * @param string $sourceType Kaynak türü
     * @param string $targetDate Hedef tarih (YYYY-MM-DD formatında)
     * @return string|null Hatırlatma tarihi veya null
     */
    public static function calculateReminderDate(string $sourceType, string $targetDate): ?string
    {
        if (!self::isReminderActive($sourceType)) {
            return null;
        }

        $days = self::getReminderDays($sourceType);
        if ($days <= 0) {
            return $targetDate;
        }

        $date = new \DateTime($targetDate);
        $date->modify("-{$days} days");
        return $date->format('Y-m-d');
    }

    /**
     * Takvim kaydı oluştur veya güncelle
     * 
     * @param int $musteriId Müşteri ID
     * @param string $sourceType Kaynak türü (gorusme, teklif, sozlesme, vb.)
     * @param int $sourceId Kaynak ID
     * @param string $targetDate Hedef tarih (YYYY-MM-DD formatında)
     * @param string|null $customContent Özel içerik (null ise varsayılan kullanılır)
     * @param int|null $userId Kullanıcı ID (null ise mevcut kullanıcı)
     * @return int|null Takvim kayıt ID veya null
     */
    public static function createOrUpdateReminder(
        int $musteriId,
        string $sourceType,
        int $sourceId,
        string $targetDate,
        ?string $customContent = null,
        ?int $userId = null
    ): ?int {
        // Hatırlatma aktif mi kontrol et
        if (!self::isReminderActive($sourceType)) {
            // Pasifse mevcut kaydı sil
            self::deleteReminder($sourceType, $sourceId);
            return null;
        }

        // Hatırlatma tarihini hesapla
        $reminderDate = self::calculateReminderDate($sourceType, $targetDate);
        if (!$reminderDate) {
            return null;
        }

        // İçerik belirle
        $config = self::$sourceTypes[$sourceType] ?? null;
        if (!$config) {
            return null;
        }

        $content = $customContent ?? $config['icerikPrefix'];
        $userId = $userId ?? ($_SESSION['user_id'] ?? 1);

        $db = Database::getInstance();

        // Mevcut kayıt var mı kontrol et
        $existing = $db->fetchOne(
            "SELECT Id FROM tbl_takvim WHERE KaynakTuru = :turu AND KaynakId = :id AND Sil = 0",
            ['turu' => $sourceType, 'id' => $sourceId]
        );

        if ($existing) {
            // Güncelle
            $db->execute(
                "UPDATE tbl_takvim SET 
                    MusteriId = :musteriId,
                    TerminTarihi = :tarih,
                    Ozet = :ozet,
                    DegistirenUserId = :userId,
                    DegisiklikZamani = GETDATE()
                WHERE Id = :takvimId",
                [
                    'musteriId' => $musteriId,
                    'tarih' => $reminderDate,
                    'ozet' => $content,
                    'userId' => $userId,
                    'takvimId' => $existing['Id']
                ]
            );
            return $existing['Id'];
        } else {
            // Yeni kayıt oluştur - Guid oluştur
            $guid = strtoupper(sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X',
                mt_rand(0, 65535), mt_rand(0, 65535),
                mt_rand(0, 65535),
                mt_rand(16384, 20479),
                mt_rand(32768, 49151),
                mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535)
            ));
            
            $db->execute(
                "INSERT INTO tbl_takvim (Guid, MusteriId, TerminTarihi, Ozet, KaynakTuru, KaynakId, Sil, EkleyenUserId, EklemeZamani)
                VALUES (:guid, :musteriId, :tarih, :ozet, :kaynakTuru, :kaynakId, 0, :userId, GETDATE())",
                [
                    'guid' => $guid,
                    'musteriId' => $musteriId,
                    'tarih' => $reminderDate,
                    'ozet' => $content,
                    'kaynakTuru' => $sourceType,
                    'kaynakId' => $sourceId,
                    'userId' => $userId
                ]
            );
            
            // Son eklenen ID'yi al
            $lastId = $db->fetchOne("SELECT SCOPE_IDENTITY() as id");
            return $lastId ? (int)$lastId['id'] : null;
        }
    }

    /**
     * Takvim kaydını sil (soft delete)
     * 
     * @param string $sourceType Kaynak türü
     * @param int $sourceId Kaynak ID
     * @return bool
     */
    public static function deleteReminder(string $sourceType, int $sourceId): bool
    {
        $db = Database::getInstance();
        return $db->execute(
            "UPDATE tbl_takvim SET Sil = 1 WHERE KaynakTuru = :turu AND KaynakId = :id",
            ['turu' => $sourceType, 'id' => $sourceId]
        );
    }

    /**
     * Müşterinin belirli türdeki tüm hatırlatmalarını sil
     * 
     * @param int $musteriId Müşteri ID
     * @param string $sourceType Kaynak türü
     * @return bool
     */
    public static function deleteRemindersByCustomer(int $musteriId, string $sourceType): bool
    {
        $db = Database::getInstance();
        return $db->execute(
            "UPDATE tbl_takvim SET Sil = 1 WHERE MusteriId = :musteriId AND KaynakTuru = :turu",
            ['musteriId' => $musteriId, 'turu' => $sourceType]
        );
    }

    /**
     * Tüm hatırlatma parametrelerini getir
     * 
     * @return array
     */
    public static function getAllReminderSettings(): array
    {
        $settings = [];
        foreach (self::$sourceTypes as $type => $config) {
            $settings[$type] = [
                'gun' => self::getReminderDays($type),
                'aktif' => self::isReminderActive($type),
                'label' => $config['icerikPrefix']
            ];
        }
        return $settings;
    }
}
