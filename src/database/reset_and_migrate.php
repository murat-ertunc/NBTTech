<?php











require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'bootstrap' . DIRECTORY_SEPARATOR . 'app.php';

use App\Core\Database;


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
    
    
    echo "📦 Aşama 1: Mevcut tablolar siliniyor...\n";
    
    
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
            
        }
    }
    
    echo "\n";
    
    
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
    
    
    $SqlDizini = SRC_PATH . 'sql';
    $Dosyalar = glob($SqlDizini . '/*.sql');
    sort($Dosyalar); 
    
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
