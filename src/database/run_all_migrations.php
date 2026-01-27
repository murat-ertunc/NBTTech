<?php
/**
 * Tüm Migration'ları Sıralı ve Güvenli Çalıştır
 * 
 * Bu script CLI veya Web üzerinden çalıştırılabilir.
 * Her migration dosyasını sırayla çalıştırır ve sonuçları raporlar.
 * 
 * Kullanım:
 *   CLI: php src/database/run_all_migrations.php
 *   Web: Doğrudan erişim önerilmez, InstallController kullanın
 */

// CLI kontrolü
$IsCli = php_sapi_name() === 'cli';

// Bootstrap yükle
require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'bootstrap' . DIRECTORY_SEPARATOR . 'app.php';

use App\Core\Database;

// Web erişiminde Basic Auth kontrolü
if (!$IsCli) {
    $AuthUser = env('MIGRATION_BASIC_USER', 'admin');
    $AuthPass = env('MIGRATION_BASIC_PASS', 'Super123!');
    
    $GivenUser = $_SERVER['PHP_AUTH_USER'] ?? '';
    $GivenPass = $_SERVER['PHP_AUTH_PW'] ?? '';
    
    if ($GivenUser !== $AuthUser || $GivenPass !== $AuthPass) {
        header('WWW-Authenticate: Basic realm="Migrations"');
        header('HTTP/1.0 401 Unauthorized');
        echo 'Yetkisiz erisim';
        exit(1);
    }
    
    header('Content-Type: text/plain; charset=UTF-8');
}

function output(string $message, bool $isCli): void {
    if ($isCli) {
        echo $message . PHP_EOL;
    } else {
        echo $message . "\n";
        flush();
    }
}

output("===========================================", $IsCli);
output("  NbtProject - Migration Runner", $IsCli);
output("  Tarih: " . date('Y-m-d H:i:s'), $IsCli);
output("===========================================", $IsCli);
output("", $IsCli);

// SQL dosyalarını bul
$SqlDir = SRC_PATH . 'sql';
$Files = glob($SqlDir . '/*.sql') ?: [];
sort($Files);

if (empty($Files)) {
    output("HATA: SQL dosyasi bulunamadi: $SqlDir", $IsCli);
    exit(1);
}

output("Toplam " . count($Files) . " migration dosyasi bulundu.", $IsCli);
output("", $IsCli);

try {
    $Db = Database::connection();
} catch (Throwable $e) {
    output("HATA: Veritabani baglantisi kurulamadi: " . $e->getMessage(), $IsCli);
    exit(1);
}

$Basarili = 0;
$Hatali = 0;
$Hatalar = [];

foreach ($Files as $DosyaYolu) {
    $DosyaAdi = basename($DosyaYolu);
    output("▶ Calistiriliyor: $DosyaAdi", $IsCli);
    
    try {
        $Sql = file_get_contents($DosyaYolu);
        
        // GO ifadelerini ayır (MSSQL batch separator)
        $Parcalar = preg_split('/^\s*GO\s*$/mi', $Sql);
        
        foreach ($Parcalar as $Parca) {
            $Parca = trim($Parca);
            if ($Parca !== '') {
                $Db->exec($Parca);
            }
        }
        
        $Basarili++;
        output("  ✓ Basarili", $IsCli);
        
    } catch (Throwable $e) {
        $Hatali++;
        $HataMesaji = $e->getMessage();
        $Hatalar[$DosyaAdi] = $HataMesaji;
        output("  ✗ HATA: $HataMesaji", $IsCli);
    }
}

output("", $IsCli);
output("===========================================", $IsCli);
output("  SONUC", $IsCli);
output("===========================================", $IsCli);
output("  Toplam:   " . count($Files), $IsCli);
output("  Basarili: $Basarili", $IsCli);
output("  Hatali:   $Hatali", $IsCli);

if (!empty($Hatalar)) {
    output("", $IsCli);
    output("HATALI DOSYALAR:", $IsCli);
    foreach ($Hatalar as $Dosya => $Hata) {
        output("  - $Dosya: $Hata", $IsCli);
    }
}

output("", $IsCli);
output("Migration islemi tamamlandi.", $IsCli);

exit($Hatali > 0 ? 1 : 0);
