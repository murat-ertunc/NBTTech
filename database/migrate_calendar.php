<?php
/**
 * SQL Migration Script - Takvim Kaynak Alanlari ve Hatirlatma Parametreleri
 */

require __DIR__ . '/../app/Core/bootstrap.php';

use App\Core\Database;

try {
    $db = Database::connection();
    echo "Veritabani baglantisi basarili.\n\n";

    // =============================================
    // 1. TAKVIM TABLOSU KAYNAK ALANLARI
    // =============================================
    echo "=== TAKVIM TABLOSU GUNCELLEME ===\n";
    
    // KaynakTuru kolonu
    $check = $db->query("SELECT COL_LENGTH('tbl_takvim', 'KaynakTuru') as len");
    $result = $check->fetch();
    
    if ($result['len'] === null) {
        echo "KaynakTuru kolonu ekleniyor...\n";
        $db->exec('ALTER TABLE tbl_takvim ADD KaynakTuru NVARCHAR(50) NULL');
        echo "KaynakTuru eklendi.\n";
    } else {
        echo "KaynakTuru zaten mevcut.\n";
    }
    
    // KaynakId kolonu
    $check = $db->query("SELECT COL_LENGTH('tbl_takvim', 'KaynakId') as len");
    $result = $check->fetch();
    
    if ($result['len'] === null) {
        echo "KaynakId kolonu ekleniyor...\n";
        $db->exec('ALTER TABLE tbl_takvim ADD KaynakId INT NULL');
        echo "KaynakId eklendi.\n";
    } else {
        echo "KaynakId zaten mevcut.\n";
    }
    
    // Index
    $check = $db->query("SELECT name FROM sys.indexes WHERE object_id = OBJECT_ID('tbl_takvim') AND name = 'IX_tbl_takvim_Kaynak'");
    $result = $check->fetch();
    
    if (!$result) {
        echo "IX_tbl_takvim_Kaynak index ekleniyor...\n";
        $db->exec('CREATE INDEX IX_tbl_takvim_Kaynak ON tbl_takvim(KaynakTuru, KaynakId)');
        echo "Index eklendi.\n";
    } else {
        echo "IX_tbl_takvim_Kaynak zaten mevcut.\n";
    }
    
    echo "\n=== HATIRLATMA PARAMETRELERI ===\n";
    
    // =============================================
    // 2. HATIRLATMA GUN PARAMETRELERI
    // =============================================
    $gunParametreleri = [
        ['gorusme_hatirlatma_gun', '0', 'Görüşme Tarihi Öncesi Hatırlatma Günü'],
        ['teklif_gecerlilik_hatirlatma_gun', '3', 'Teklif Geçerlilik Tarihi Öncesi Hatırlatma Günü'],
        ['sozlesme_hatirlatma_gun', '0', 'Sözleşme Tarihi Öncesi Hatırlatma Günü'],
        ['damgavergisi_hatirlatma_gun', '0', 'Damga Vergisi Tarihi Öncesi Hatırlatma Günü'],
        ['teminat_termin_hatirlatma_gun', '7', 'Teminat Termin Tarihi Öncesi Hatırlatma Günü'],
        ['fatura_hatirlatma_gun', '0', 'Fatura Tarihi Öncesi Hatırlatma Günü'],
        ['odeme_hatirlatma_gun', '0', 'Ödeme Tarihi Öncesi Hatırlatma Günü'],
    ];
    
    $sira = 3;
    foreach ($gunParametreleri as $param) {
        $kod = $param[0];
        $deger = $param[1];
        $etiket = $param[2];
        
        $check = $db->prepare("SELECT Id FROM tbl_parametre WHERE Grup = 'genel' AND Kod = :kod AND Sil = 0");
        $check->execute(['kod' => $kod]);
        $result = $check->fetch();
        
        if (!$result) {
            echo "Ekleniyor: $kod...\n";
            $stmt = $db->prepare("INSERT INTO tbl_parametre (Grup, Kod, Deger, Etiket, Sira, Aktif, Varsayilan, Sil, EklemeZamani, EkleyenUserId) 
                                  VALUES ('genel', :kod, :deger, :etiket, :sira, 1, 0, 0, GETDATE(), 1)");
            $stmt->execute(['kod' => $kod, 'deger' => $deger, 'etiket' => $etiket, 'sira' => $sira]);
            echo "$kod eklendi.\n";
        } else {
            echo "$kod zaten mevcut.\n";
        }
        $sira++;
    }
    
    // =============================================
    // 3. HATIRLATMA AKTIF/PASIF PARAMETRELERI
    // =============================================
    echo "\n=== HATIRLATMA AKTIF/PASIF PARAMETRELERI ===\n";
    
    $aktifParametreleri = [
        ['gorusme_hatirlatma_aktif', '1', 'Görüşme Hatırlatma Aktif'],
        ['teklif_gecerlilik_hatirlatma_aktif', '1', 'Teklif Geçerlilik Hatırlatma Aktif'],
        ['sozlesme_hatirlatma_aktif', '1', 'Sözleşme Hatırlatma Aktif'],
        ['damgavergisi_hatirlatma_aktif', '1', 'Damga Vergisi Hatırlatma Aktif'],
        ['teminat_termin_hatirlatma_aktif', '1', 'Teminat Termin Hatırlatma Aktif'],
        ['fatura_hatirlatma_aktif', '1', 'Fatura Hatırlatma Aktif'],
        ['odeme_hatirlatma_aktif', '1', 'Ödeme Hatırlatma Aktif'],
    ];
    
    $sira = 13;
    foreach ($aktifParametreleri as $param) {
        $kod = $param[0];
        $deger = $param[1];
        $etiket = $param[2];
        
        $check = $db->prepare("SELECT Id FROM tbl_parametre WHERE Grup = 'genel' AND Kod = :kod AND Sil = 0");
        $check->execute(['kod' => $kod]);
        $result = $check->fetch();
        
        if (!$result) {
            echo "Ekleniyor: $kod...\n";
            $stmt = $db->prepare("INSERT INTO tbl_parametre (Grup, Kod, Deger, Etiket, Sira, Aktif, Varsayilan, Sil, EklemeZamani, EkleyenUserId) 
                                  VALUES ('genel', :kod, :deger, :etiket, :sira, 1, 0, 0, GETDATE(), 1)");
            $stmt->execute(['kod' => $kod, 'deger' => $deger, 'etiket' => $etiket, 'sira' => $sira]);
            echo "$kod eklendi.\n";
        } else {
            echo "$kod zaten mevcut.\n";
        }
        $sira++;
    }
    
    echo "\n=== MIGRATION TAMAMLANDI ===\n";
    
} catch (Exception $e) {
    echo "HATA: " . $e->getMessage() . "\n";
    exit(1);
}
