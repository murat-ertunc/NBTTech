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

function getBasicAuthCredentials(): array
{
    $User = $_SERVER['PHP_AUTH_USER'] ?? '';
    $Pass = $_SERVER['PHP_AUTH_PW'] ?? '';

    if ($User !== '' || $Pass !== '') {
        return [$User, $Pass];
    }

    $Header = $_SERVER['HTTP_AUTHORIZATION']
        ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
        ?? $_SERVER['Authorization']
        ?? '';

    if (stripos($Header, 'basic ') === 0) {
        $Decoded = base64_decode(substr($Header, 6));
        if ($Decoded !== false && strpos($Decoded, ':') !== false) {
            [$User, $Pass] = explode(':', $Decoded, 2);
            return [$User, $Pass];
        }
    }

    return ['', ''];
}

// Web erişiminde Basic Auth kontrolü
if (!$IsCli) {
    $AuthUser = env('MIG_USER', env('MIGRATION_BASIC_USER', 'migrate'));
    $AuthPass = env('MIG_PASS', env('MIGRATION_BASIC_PASS', 'change-me'));

    [$GivenUser, $GivenPass] = getBasicAuthCredentials();

    if (!hash_equals((string)$AuthUser, (string)$GivenUser) || !hash_equals((string)$AuthPass, (string)$GivenPass)) {
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

function sanitizeErrorMessage(string $Message): string
{
    $Message = strip_tags($Message);
    $Message = preg_replace('/[^\P{C}\n\t]+/u', '', $Message);
    $Message = preg_replace('/\s+/', ' ', $Message);
    return mb_substr($Message, 0, 1000);
}

function ensureSchemaMigrations(PDO $Db): void
{
    $Db->exec("IF NOT EXISTS (SELECT 1 FROM sys.tables WHERE name = 'schema_migrations')
BEGIN
    CREATE TABLE schema_migrations (
        id INT IDENTITY(1,1) PRIMARY KEY,
        filename NVARCHAR(260) NOT NULL UNIQUE,
        checksum NVARCHAR(64) NULL,
        applied_at DATETIME2(0) NULL,
        status NVARCHAR(20) NOT NULL,
        error_message NVARCHAR(4000) NULL
    );
END");
}

function buildWrappedSql(string $Sql): string
{
    $Sql = preg_replace('/^\s*GO\s*$/mi', '', $Sql);
    return "BEGIN TRY\nBEGIN TRANSACTION;\n" . $Sql . "\nCOMMIT;\nEND TRY\nBEGIN CATCH\nIF @@TRANCOUNT > 0 ROLLBACK;\nDECLARE @ErrorMessage NVARCHAR(4000) = ERROR_MESSAGE();\nRAISERROR(@ErrorMessage, 16, 1);\nEND CATCH";
}

function upsertMigration(PDO $Db, string $Filename, ?string $Checksum, string $Status, ?string $ErrorMessage): void
{
    $ExistsStmt = $Db->prepare('SELECT 1 FROM schema_migrations WHERE filename = :filename');
    $ExistsStmt->execute([':filename' => $Filename]);
    $Exists = (bool)$ExistsStmt->fetchColumn();

    if ($Exists) {
        $Stmt = $Db->prepare('UPDATE schema_migrations SET checksum = :checksum, applied_at = SYSUTCDATETIME(), status = :status, error_message = :error_message WHERE filename = :filename');
        $Stmt->execute([
            ':checksum' => $Checksum,
            ':status' => $Status,
            ':error_message' => $ErrorMessage,
            ':filename' => $Filename,
        ]);
        return;
    }

    $Stmt = $Db->prepare('INSERT INTO schema_migrations (filename, checksum, applied_at, status, error_message) VALUES (:filename, :checksum, SYSUTCDATETIME(), :status, :error_message)');
    $Stmt->execute([
        ':filename' => $Filename,
        ':checksum' => $Checksum,
        ':status' => $Status,
        ':error_message' => $ErrorMessage,
    ]);
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
    ensureSchemaMigrations($Db);
} catch (Throwable $e) {
    output("HATA: Veritabani baglantisi kurulamadi: " . $e->getMessage(), $IsCli);
    exit(1);
}

$Basarili = 0;
$Hatali = 0;
$Hatalar = [];

foreach ($Files as $DosyaYolu) {
    $DosyaAdi = basename($DosyaYolu);
    $Checksum = hash('sha256', (string)file_get_contents($DosyaYolu));
    output("▶ Calistiriliyor: $DosyaAdi", $IsCli);

    try {
        $Sql = file_get_contents($DosyaYolu);
        $Wrapped = buildWrappedSql($Sql);
        $Db->exec($Wrapped);

        upsertMigration($Db, $DosyaAdi, $Checksum, 'applied', null);
        $Basarili++;
        output("  ✓ Basarili", $IsCli);
    } catch (Throwable $e) {
        $Hatali++;
        $HataMesaji = sanitizeErrorMessage($e->getMessage());
        $Hatalar[$DosyaAdi] = $HataMesaji;
        upsertMigration($Db, $DosyaAdi, $Checksum, 'failed', $HataMesaji);
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
