<?php
/**
 * =============================================
 * DB RESET, MIGRATE & SEED ARACI
 * =============================================
 * 
 * !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
 * !!  UYARI: BU SCRIPT TUM VERITABANI VERILERINI SILER!                     !!
 * !!  PRODUCTION ORTAMINDA KESINLIKLE CALISTIRMAYIN!                        !!
 * !!  CALISTIRMADAN ONCE MUTLAKA YEDEK ALIN!                                !!
 * !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
 * 
 * KULLANIM:
 * ---------
 * Terminal uzerinden calistirin:
 *   php tools/reset_and_migrate.php
 * 
 * ORTAM KONTROLU:
 * ---------------
 * - APP_ENV=local veya APP_ENV=development olmali
 * - APP_ENV=production ise script calismaz
 * 
 * YAPTIĞI ISLEMLER:
 * -----------------
 * 1. Tum tablolari siler (drop)
 * 2. /sql klasorundeki tum migration dosyalarini sirayla calistirir
 * 3. Varsayilan roller, permissionlar ve superadmin kullanici olusturur
 * 
 * GEREKSINIMLER:
 * --------------
 * - PHP 8.0+
 * - SQL Server (sqlsrv PDO driver)
 * - Yazma yetkisi olan DB kullanicisi
 * 
 * @author  NBT Tech
 * @version 1.0.0
 * @date    2026-01-20
 */

// =============================================
// BOOTSTRAP
// =============================================
$BasePath = dirname(__DIR__);

// Autoloader ve config yukle
require_once $BasePath . '/app/Core/helpers.php';
require_once $BasePath . '/app/Core/Config.php';

// Namespace'leri yukle
spl_autoload_register(function ($Sinif) use ($BasePath) {
    $Prefix = 'App\\';
    $BaseDir = $BasePath . '/app/';
    
    $Uzunluk = strlen($Prefix);
    if (strncmp($Prefix, $Sinif, $Uzunluk) !== 0) {
        return;
    }
    
    $RelativeSinif = substr($Sinif, $Uzunluk);
    $Dosya = $BaseDir . str_replace('\\', '/', $RelativeSinif) . '.php';
    
    if (file_exists($Dosya)) {
        require $Dosya;
    }
});

use App\Core\Database;

// =============================================
// ORTAM KONTROLU
// =============================================
$AppEnv = env('APP_ENV', 'production');

if ($AppEnv === 'production' || $AppEnv === 'prod') {
    echo "\n";
    echo "╔══════════════════════════════════════════════════════════════╗\n";
    echo "║  ❌ HATA: PRODUCTION ORTAMINDA CALISTIRILAMAZ!               ║\n";
    echo "║                                                              ║\n";
    echo "║  Bu script tum veritabani verilerini siler.                  ║\n";
    echo "║  Sadece local/development ortamlarinda kullanilabilir.       ║\n";
    echo "║                                                              ║\n";
    echo "║  APP_ENV=local veya APP_ENV=development olarak ayarlayin.    ║\n";
    echo "╚══════════════════════════════════════════════════════════════╝\n";
    echo "\n";
    exit(1);
}

echo "\n";
echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║  ⚠️  DB RESET, MIGRATE & SEED ARACI                          ║\n";
echo "╠══════════════════════════════════════════════════════════════╣\n";
echo "║  Ortam: " . str_pad($AppEnv, 52) . "║\n";
echo "║  Veritabani: " . str_pad(env('DB_DATABASE', 'N/A'), 47) . "║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";
echo "\n";

// Onay iste
echo "⚠️  UYARI: Bu islem tum veritabani verilerini silecek!\n";
echo "   Devam etmek icin 'EVET' yazin: ";

$Handle = fopen("php://stdin", "r");
$Girdi = trim(fgets($Handle));
fclose($Handle);

if ($Girdi !== 'EVET') {
    echo "\n❌ Islem iptal edildi.\n\n";
    exit(0);
}

echo "\n";

// =============================================
// VERITABANI BAGLANTISI
// =============================================
try {
    $Db = Database::connection();
    echo "✅ Veritabani baglantisi basarili.\n";
} catch (\Exception $E) {
    echo "❌ Veritabani baglanti hatasi: " . $E->getMessage() . "\n";
    exit(1);
}

// =============================================
// ADIM 1: TUM TABLOLARI SIL
// =============================================
echo "\n📋 ADIM 1: Tum tablolari silme...\n";
echo str_repeat("-", 60) . "\n";

try {
    // Foreign key constraint'leri devre disi birak
    $Db->exec("EXEC sp_MSforeachtable 'ALTER TABLE ? NOCHECK CONSTRAINT all'");
    echo "  ✅ Foreign key constraint'ler devre disi birakildi.\n";
    
    // Tum tablolari getir
    $Stmt = $Db->query("
        SELECT TABLE_NAME 
        FROM INFORMATION_SCHEMA.TABLES 
        WHERE TABLE_TYPE = 'BASE TABLE' 
        AND TABLE_CATALOG = DB_NAME()
        ORDER BY TABLE_NAME
    ");
    $Tablolar = $Stmt->fetchAll(\PDO::FETCH_COLUMN);
    
    if (empty($Tablolar)) {
        echo "  ℹ️  Silinecek tablo bulunamadi.\n";
    } else {
        echo "  Bulunan tablo sayisi: " . count($Tablolar) . "\n";
        
        // Her tabloyu sil
        foreach ($Tablolar as $Tablo) {
            try {
                $Db->exec("DROP TABLE IF EXISTS [{$Tablo}]");
                echo "    ✅ {$Tablo} silindi.\n";
            } catch (\PDOException $E) {
                // Bagimliliklari olan tablolar icin tekrar dene
                echo "    ⚠️  {$Tablo} silinemedi, sonra tekrar denenecek.\n";
            }
        }
        
        // Kalan tablolari tekrar dene
        $Stmt = $Db->query("
            SELECT TABLE_NAME 
            FROM INFORMATION_SCHEMA.TABLES 
            WHERE TABLE_TYPE = 'BASE TABLE' 
            AND TABLE_CATALOG = DB_NAME()
        ");
        $KalanTablolar = $Stmt->fetchAll(\PDO::FETCH_COLUMN);
        
        foreach ($KalanTablolar as $Tablo) {
            try {
                $Db->exec("DROP TABLE IF EXISTS [{$Tablo}]");
                echo "    ✅ {$Tablo} silindi (ikinci deneme).\n";
            } catch (\PDOException $E) {
                echo "    ❌ {$Tablo} silinemedi: " . $E->getMessage() . "\n";
            }
        }
    }
    
    echo "  ✅ Tablo silme islemi tamamlandi.\n";
    
} catch (\Exception $E) {
    echo "  ❌ Tablo silme hatasi: " . $E->getMessage() . "\n";
    exit(1);
}

// =============================================
// ADIM 2: MIGRATION DOSYALARINI CALISTIR
// =============================================
echo "\n📋 ADIM 2: Migration dosyalarini calistirma...\n";
echo str_repeat("-", 60) . "\n";

$SqlDizini = $BasePath . '/sql';
$SqlDosyalari = glob($SqlDizini . '/*.sql');
sort($SqlDosyalari); // Numara sirasina gore sirala

if (empty($SqlDosyalari)) {
    echo "  ⚠️  /sql klasorunde dosya bulunamadi.\n";
} else {
    echo "  Bulunan SQL dosya sayisi: " . count($SqlDosyalari) . "\n\n";
    
    $BasariliSayisi = 0;
    $HataliSayisi = 0;
    
    foreach ($SqlDosyalari as $Dosya) {
        $DosyaAdi = basename($Dosya);
        echo "  📄 {$DosyaAdi}... ";
        
        try {
            $SqlIcerik = file_get_contents($Dosya);
            
            // GO ifadelerini ayir (MSSQL batch separator)
            $Batchler = preg_split('/^\s*GO\s*$/mi', $SqlIcerik);
            
            foreach ($Batchler as $Batch) {
                $Batch = trim($Batch);
                if (empty($Batch)) {
                    continue;
                }
                
                // Yorum satiri kontrolu
                if (preg_match('/^--/', $Batch) && !preg_match('/\n/', $Batch)) {
                    continue;
                }
                
                try {
                    $Db->exec($Batch);
                } catch (\PDOException $E) {
                    // Bazi hatalari tolere et (ornegin "already exists")
                    $HataMesaji = $E->getMessage();
                    if (
                        strpos($HataMesaji, 'already exists') === false &&
                        strpos($HataMesaji, 'There is already') === false &&
                        strpos($HataMesaji, 'zaten mevcut') === false
                    ) {
                        throw $E;
                    }
                }
            }
            
            echo "✅\n";
            $BasariliSayisi++;
            
        } catch (\Exception $E) {
            echo "❌\n";
            echo "      Hata: " . $E->getMessage() . "\n";
            $HataliSayisi++;
        }
    }
    
    echo "\n  Sonuc: {$BasariliSayisi} basarili, {$HataliSayisi} hatali\n";
}

// =============================================
// ADIM 3: DOGRULAMA
// =============================================
echo "\n📋 ADIM 3: Dogrulama...\n";
echo str_repeat("-", 60) . "\n";

try {
    // Tablo sayisi
    $Stmt = $Db->query("
        SELECT COUNT(*) as Sayi 
        FROM INFORMATION_SCHEMA.TABLES 
        WHERE TABLE_TYPE = 'BASE TABLE' 
        AND TABLE_CATALOG = DB_NAME()
    ");
    $TabloSayisi = $Stmt->fetch(\PDO::FETCH_ASSOC)['Sayi'];
    echo "  ✅ Toplam tablo sayisi: {$TabloSayisi}\n";
    
    // RBAC tablolari kontrolu
    $RbacTablolari = ['tnm_permission', 'tnm_rol', 'tnm_rol_permission', 'tnm_user_rol'];
    foreach ($RbacTablolari as $Tablo) {
        $Stmt = $Db->query("
            SELECT COUNT(*) as Sayi 
            FROM INFORMATION_SCHEMA.TABLES 
            WHERE TABLE_NAME = '{$Tablo}'
        ");
        $Var = $Stmt->fetch(\PDO::FETCH_ASSOC)['Sayi'] > 0;
        echo "  " . ($Var ? "✅" : "❌") . " {$Tablo}: " . ($Var ? "mevcut" : "YOK!") . "\n";
    }
    
    // Permission sayisi
    $Stmt = $Db->query("SELECT COUNT(*) as Sayi FROM tnm_permission WHERE Sil = 0");
    $PermissionSayisi = $Stmt->fetch(\PDO::FETCH_ASSOC)['Sayi'];
    echo "  ✅ Permission sayisi: {$PermissionSayisi}\n";
    
    // read_all permission kontrolu
    $Stmt = $Db->query("
        SELECT PermissionKodu 
        FROM tnm_permission 
        WHERE PermissionKodu IN ('users.read_all', 'customers.read_all') 
        AND Sil = 0
    ");
    $ReadAllPermissions = $Stmt->fetchAll(\PDO::FETCH_COLUMN);
    
    $UsersReadAll = in_array('users.read_all', $ReadAllPermissions);
    $CustomersReadAll = in_array('customers.read_all', $ReadAllPermissions);
    
    echo "  " . ($UsersReadAll ? "✅" : "❌") . " users.read_all: " . ($UsersReadAll ? "mevcut" : "YOK!") . "\n";
    echo "  " . ($CustomersReadAll ? "✅" : "❌") . " customers.read_all: " . ($CustomersReadAll ? "mevcut" : "YOK!") . "\n";
    
    // Rol sayisi
    $Stmt = $Db->query("SELECT COUNT(*) as Sayi FROM tnm_rol WHERE Sil = 0");
    $RolSayisi = $Stmt->fetch(\PDO::FETCH_ASSOC)['Sayi'];
    echo "  ✅ Rol sayisi: {$RolSayisi}\n";
    
    // Kullanici sayisi
    $Stmt = $Db->query("SELECT COUNT(*) as Sayi FROM tnm_user WHERE Sil = 0");
    $KullaniciSayisi = $Stmt->fetch(\PDO::FETCH_ASSOC)['Sayi'];
    echo "  ✅ Kullanici sayisi: {$KullaniciSayisi}\n";
    
    // Superadmin kullanici kontrolu
    $Stmt = $Db->query("
        SELECT u.KullaniciAdi 
        FROM tnm_user u
        INNER JOIN tnm_user_rol ur ON u.Id = ur.UserId AND ur.Sil = 0
        INNER JOIN tnm_rol r ON ur.RolId = r.Id AND r.Sil = 0
        WHERE r.RolKodu = 'superadmin' AND u.Sil = 0
    ");
    $Superadminler = $Stmt->fetchAll(\PDO::FETCH_COLUMN);
    
    if (!empty($Superadminler)) {
        echo "  ✅ Superadmin kullanicilar: " . implode(', ', $Superadminler) . "\n";
    } else {
        echo "  ⚠️  Superadmin kullanici bulunamadi!\n";
    }
    
} catch (\Exception $E) {
    echo "  ⚠️  Dogrulama sirasinda hata: " . $E->getMessage() . "\n";
}

// =============================================
// OZET
// =============================================
echo "\n";
echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║  ✅ DB RESET, MIGRATE & SEED TAMAMLANDI!                     ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";
echo "\n";
echo "📝 Onemli Notlar:\n";
echo "   - Varsayilan superadmin sifresi: 123456 (hemen degistirin!)\n";
echo "   - Tum permission ve roller seed edildi\n";
echo "   - users.read_all ve customers.read_all permission'lari eklendi\n";
echo "\n";

exit(0);
