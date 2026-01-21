<?php
/**
 * Veritabani Reset ve Migration Script
 * 
 * UYARI: Bu script mevcut veritabanini tamamen siler ve yeniden olusturur!
 * 
 * Kullanim: php database/reset_and_migrate.php
 * 
 * Guvenlik: Bu script sadece development ortaminda calisir.
 * Production ortaminda calistirilmaya calisildiginda hata verir.
 */

require_once __DIR__ . '/../app/Core/bootstrap.php';

use App\Core\Database;

// Guvenlik kontrolu - Production'da calistirma
$OrtamGuvenli = env('APP_ENV', 'production');
if ($OrtamGuvenli === 'production') {
    echo "\n";
    echo "╔════════════════════════════════════════════════════════════════╗\n";
    echo "║  ⛔ HATA: Bu script PRODUCTION ortaminda calistirilamaz!       ║\n";
    echo "║                                                                ║\n";
    echo "║  .env dosyasinda APP_ENV=development olarak ayarlayin.         ║\n";
    echo "╚════════════════════════════════════════════════════════════════╝\n";
    echo "\n";
    exit(1);
}

// Ek guvenlik - Kullanici onay alsın
echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║  ⚠️  UYARI: Veritabani TAMAMEN silinecek ve yeniden            ║\n";
echo "║            olusturulacak!                                      ║\n";
echo "║                                                                ║\n";
echo "║  Tum tablolar DROP edilecek                                    ║\n";
echo "║  Tum veriler kaybolacak                                        ║\n";
echo "║  sql/*.sql dosyalari sirayla calistirilacak                    ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
echo "\n";
echo "Devam etmek istiyor musunuz? (yes/no): ";

$Cevap = trim(fgets(STDIN));
if (strtolower($Cevap) !== 'yes') {
    echo "\n✗ Islem iptal edildi.\n\n";
    exit(0);
}

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "🔄 Veritabani reset ve migration basliyor...\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

try {
    $Db = Database::connection();
    
    // Aşama 1: Tüm tabloları DROP et
    echo "📦 Aşama 1: Mevcut tablolar siliniyor...\n";
    
    // Tüm foreign key constraint'leri getir ve DROP et
    $ForeignKeys = $Db->query("
        SELECT 
            OBJECT_NAME(f.parent_object_id) AS TableName,
            f.name AS ForeignKeyName
        FROM sys.foreign_keys AS f
        ORDER BY TableName
    ")->fetchAll(\PDO::FETCH_ASSOC);
    
    foreach ($ForeignKeys as $Fk) {
        try {
            $Db->exec("ALTER TABLE [{$Fk['TableName']}] DROP CONSTRAINT [{$Fk['ForeignKeyName']}]");
            echo "   ⊟ FK constraint silindi: {$Fk['TableName']}.{$Fk['ForeignKeyName']}\n";
        } catch (\Exception $e) {
            // Constraint yoksa devam et
        }
    }
    
    echo "\n";
    
    // Tüm tabloları getir ve DROP et
    $Tablolar = $Db->query("
        SELECT TABLE_NAME 
        FROM INFORMATION_SCHEMA.TABLES 
        WHERE TABLE_TYPE = 'BASE TABLE' 
        AND TABLE_CATALOG = DB_NAME()
        ORDER BY TABLE_NAME
    ")->fetchAll(\PDO::FETCH_COLUMN);
    
    foreach ($Tablolar as $Tablo) {
        try {
            $Db->exec("DROP TABLE IF EXISTS [{$Tablo}]");
            echo "   ✓ {$Tablo} silindi\n";
        } catch (\Exception $e) {
            echo "   ✗ {$Tablo} - Hata: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n📦 Aşama 2: SQL dosyalari calistiriliyor...\n\n";
    
    // SQL dosyalarini sirali olarak calistir
    $SqlDizini = __DIR__ . '/../sql';
    $Dosyalar = glob($SqlDizini . '/*.sql');
    sort($Dosyalar); // Sirayla calistir (000_, 001_, 002_, ...)
    
    $BasariliSayisi = 0;
    $HataliSayisi = 0;
    
    foreach ($Dosyalar as $Dosya) {
        $DosyaAdi = basename($Dosya);
        $SqlIcerik = file_get_contents($Dosya);
        
        if (empty(trim($SqlIcerik))) {
            echo "   ⊘ {$DosyaAdi} - Bos dosya, atlandi\n";
            continue;
        }
        
        try {
            // GO ifadelerini ayir ve her birini ayri calistir
            $Parcalar = preg_split('/^\s*GO\s*$/mi', $SqlIcerik);
            
            foreach ($Parcalar as $Parca) {
                $Parca = trim($Parca);
                if (!empty($Parca)) {
                    $Db->exec($Parca);
                }
            }
            
            echo "   ✓ {$DosyaAdi}\n";
            $BasariliSayisi++;
        } catch (\PDOException $e) {
            echo "   ✗ {$DosyaAdi} - HATA: " . $e->getMessage() . "\n";
            $HataliSayisi++;
        }
    }
    
    echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "📊 Sonuç:\n";
    echo "   Basarili: {$BasariliSayisi} dosya\n";
    echo "   Hatali:   {$HataliSayisi} dosya\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    
    if ($HataliSayisi > 0) {
        echo "⚠️  Bazi dosyalar calistirilamadi. Hata mesajlarini kontrol edin.\n\n";
        exit(1);
    }
    
    // Aşama 3: Seeder'i calistir
    echo "📦 Aşama 3: Seeder calistiriliyor...\n\n";
    include __DIR__ . '/seeder.php';
    
    echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "✅ Veritabani reset ve migration tamamlandi!\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    
} catch (\Exception $e) {
    echo "\n";
    echo "╔════════════════════════════════════════════════════════════════╗\n";
    echo "║  ⛔ KRITIK HATA                                                ║\n";
    echo "╚════════════════════════════════════════════════════════════════╝\n";
    echo "\n" . $e->getMessage() . "\n\n";
    exit(1);
}
