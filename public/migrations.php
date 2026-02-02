<?php
require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'bootstrap' . DIRECTORY_SEPARATOR . 'app.php';

use App\Core\Database;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

@set_time_limit(0);
@ini_set('max_execution_time', '0');

header('Content-Type: text/html; charset=utf-8');

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

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

function ensureAuth(): void
{
    $AuthUser = 'superadmin';
    $AuthPass = 'Super123!';

    if (!isset($_SERVER['PHP_AUTH_USER']) && !isset($_SERVER['HTTP_AUTHORIZATION']) && !isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION']) && !isset($_SERVER['Authorization'])) {
        header('WWW-Authenticate: Basic realm="Migrations"');
        header('HTTP/1.0 401 Unauthorized');
        echo 'Yetkisiz';
        exit;
    }

    [$GivenUser, $GivenPass] = getBasicAuthCredentials();

    if ($GivenUser !== $AuthUser || $GivenPass !== $AuthPass) {
        header('WWW-Authenticate: Basic realm="Migrations"');
        header('HTTP/1.0 401 Unauthorized');
        echo 'Yetkisiz';
        exit;
    }
}

function sanitizeErrorMessage(string $Message): string
{
    $Message = strip_tags($Message);
    $Message = preg_replace('/[^\P{C}\n\t]+/u', '', $Message);
    $Message = preg_replace('/\s+/', ' ', $Message);
    return mb_substr($Message, 0, 1000);
}

function logException(string $Context, Throwable $Error): void
{
    $Message = sprintf('[%s] %s: %s in %s:%s', date('c'), $Context, $Error->getMessage(), $Error->getFile(), $Error->getLine());
    error_log($Message . "\n" . $Error->getTraceAsString());
}

function runSeeder(): array
{
    $SeederPath = SRC_PATH . 'database' . DIRECTORY_SEPARATOR . 'seeder.php';

    if (!file_exists($SeederPath)) {
        return ['ok' => false, 'message' => 'Seeder dosyası bulunamadı: ' . $SeederPath, 'output' => ''];
    }

    $DisabledFunctions = array_map('trim', explode(',', (string)ini_get('disable_functions')));
    if (!function_exists('exec') || in_array('exec', $DisabledFunctions, true)) {
        return ['ok' => false, 'message' => 'Seeder calistirilamadi: exec fonksiyonu devre disi.', 'output' => ''];
    }

    $PhpBinary = resolvePhpBinary();
    if ($PhpBinary === '') {
        return ['ok' => false, 'message' => 'Seeder calistirilamadi: PHP CLI binary bulunamadi.', 'output' => ''];
    }

    $Command = escapeshellcmd($PhpBinary) . ' ' . escapeshellarg($SeederPath) . ' 2>&1';

    $Output = [];
    $ReturnCode = 0;
    exec($Command, $Output, $ReturnCode);

    $OutputStr = implode("\n", $Output);

    if ($ReturnCode !== 0) {
        return [
            'ok' => false,
            'message' => 'Seeder hata ile sonuçlandı (exit code: ' . $ReturnCode . ')',
            'output' => $OutputStr
        ];
    }

    return [
        'ok' => true,
        'message' => 'Seeder başarıyla çalıştırıldı.',
        'output' => $OutputStr
    ];
}

function resolvePhpBinary(): string
{
    $Candidates = [];

    if (PHP_BINARY) {
        $Candidates[] = PHP_BINARY;
    }

    if (PHP_BINDIR) {
        $Candidates[] = rtrim(PHP_BINDIR, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'php';
    }

    $Env = getenv('PHP_CLI');
    if ($Env) {
        $Candidates[] = $Env;
    }

    foreach ($Candidates as $Candidate) {
        $Base = basename($Candidate);
        if (stripos($Base, 'php-fpm') !== false || stripos($Base, 'php-cgi') !== false) {
            continue;
        }
        if (is_file($Candidate) && is_executable($Candidate)) {
            return $Candidate;
        }
    }

    return '';
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

function ensureSchemaMigrationLogs(PDO $Db): void
{
    $Db->exec("IF NOT EXISTS (SELECT 1 FROM sys.tables WHERE name = 'schema_migration_logs')
BEGIN
    CREATE TABLE schema_migration_logs (
        id INT IDENTITY(1,1) PRIMARY KEY,
        action NVARCHAR(100) NOT NULL,
        detail NVARCHAR(2000) NULL,
        status NVARCHAR(20) NOT NULL,
        created_at DATETIME2(0) NOT NULL DEFAULT SYSUTCDATETIME(),
        error_message NVARCHAR(4000) NULL
    );
END");
}

function logMigrationEvent(PDO $Db, string $Action, string $Detail, string $Status, ?string $ErrorMessage = null): void
{
    if (!tableExists($Db, 'schema_migration_logs')) {
        return;
    }
    $Stmt = $Db->prepare('INSERT INTO schema_migration_logs (action, detail, status, error_message) VALUES (:action, :detail, :status, :error_message)');
    $Stmt->execute([
        ':action' => $Action,
        ':detail' => $Detail,
        ':status' => $Status,
        ':error_message' => $ErrorMessage,
    ]);
}

function tableExists(PDO $Db, string $TableName): bool
{
    $Stmt = $Db->prepare('SELECT 1 FROM sys.tables WHERE name = :name');
    $Stmt->execute([':name' => $TableName]);
    return (bool) $Stmt->fetchColumn();
}

function isResetAllowed(): bool
{
    return env('MIG_ALLOW_RESET', '0') === '1' && (string)env('MIG_RESET_TOKEN', '') !== '';
}

function isSystemDatabase(string $DatabaseName): bool
{
    $Lower = mb_strtolower($DatabaseName);
    return in_array($Lower, ['master', 'msdb', 'tempdb', 'model'], true);
}

function fetchDropStatements(PDO $Db, string $Sql): array
{
    $Stmt = $Db->query($Sql);
    if ($Stmt === false) {
        return [];
    }
    return $Stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
}

function executeDropStep(PDO $Db, string $Action, string $Sql, array &$Summary, array &$Logs): void
{
    $Statements = fetchDropStatements($Db, $Sql);
    $Found = count($Statements);
    $Dropped = 0;

    foreach ($Statements as $Statement) {
        $Db->exec($Statement);
        $Dropped++;
    }

    $Summary[$Action] = ['found' => $Found, 'dropped' => $Dropped];
    $Logs[] = [$Action, "{$Action}: found {$Found}, dropped {$Dropped}", 'success', null];
}

function resetDatabaseObjects(PDO $Db): array
{
    $Summary = [];
    $Logs = [];

    $Steps = [
        ['drop_foreign_keys', "SELECT 'ALTER TABLE ' + QUOTENAME(s.name) + '.' + QUOTENAME(t.name) + ' DROP CONSTRAINT ' + QUOTENAME(fk.name)
            FROM sys.foreign_keys fk
            INNER JOIN sys.objects o ON fk.object_id = o.object_id
            INNER JOIN sys.tables t ON fk.parent_object_id = t.object_id
            INNER JOIN sys.schemas s ON t.schema_id = s.schema_id
            WHERE o.is_ms_shipped = 0"],
        ['drop_views', "SELECT 'DROP VIEW ' + QUOTENAME(s.name) + '.' + QUOTENAME(v.name)
            FROM sys.views v
            INNER JOIN sys.objects o ON v.object_id = o.object_id
            INNER JOIN sys.schemas s ON v.schema_id = s.schema_id
            WHERE o.is_ms_shipped = 0"],
        ['drop_procedures', "SELECT 'DROP PROCEDURE ' + QUOTENAME(s.name) + '.' + QUOTENAME(p.name)
            FROM sys.procedures p
            INNER JOIN sys.objects o ON p.object_id = o.object_id
            INNER JOIN sys.schemas s ON p.schema_id = s.schema_id
            WHERE o.is_ms_shipped = 0"],
        ['drop_functions', "SELECT 'DROP FUNCTION ' + QUOTENAME(s.name) + '.' + QUOTENAME(o.name)
            FROM sys.objects o
            INNER JOIN sys.schemas s ON o.schema_id = s.schema_id
            WHERE o.type IN ('FN','IF','TF') AND o.is_ms_shipped = 0"],
        ['drop_triggers', "SELECT 'DROP TRIGGER ' + QUOTENAME(OBJECT_SCHEMA_NAME(tr.object_id)) + '.' + QUOTENAME(tr.name)
            FROM sys.triggers tr
            INNER JOIN sys.objects o ON tr.object_id = o.object_id
            WHERE tr.parent_class = 1 AND o.is_ms_shipped = 0"],
        ['drop_db_triggers', "SELECT 'DROP TRIGGER ' + QUOTENAME(tr.name) + ' ON DATABASE'
            FROM sys.triggers tr
            INNER JOIN sys.objects o ON tr.object_id = o.object_id
            WHERE tr.parent_class = 0 AND o.is_ms_shipped = 0"],
        ['drop_synonyms', "SELECT 'DROP SYNONYM ' + QUOTENAME(s.name) + '.' + QUOTENAME(sn.name)
            FROM sys.synonyms sn
            INNER JOIN sys.objects o ON sn.object_id = o.object_id
            INNER JOIN sys.schemas s ON sn.schema_id = s.schema_id
            WHERE o.is_ms_shipped = 0"],
        ['drop_sequences', "SELECT 'DROP SEQUENCE ' + QUOTENAME(s.name) + '.' + QUOTENAME(seq.name)
            FROM sys.sequences seq
            INNER JOIN sys.objects o ON seq.object_id = o.object_id
            INNER JOIN sys.schemas s ON seq.schema_id = s.schema_id
            WHERE o.is_ms_shipped = 0"],
        ['drop_tables', "SELECT 'DROP TABLE ' + QUOTENAME(s.name) + '.' + QUOTENAME(t.name)
            FROM sys.tables t
            INNER JOIN sys.schemas s ON t.schema_id = s.schema_id
            WHERE t.is_ms_shipped = 0"],
        ['drop_user_types', "SELECT 'DROP TYPE ' + QUOTENAME(s.name) + '.' + QUOTENAME(t.name)
            FROM sys.types t
            INNER JOIN sys.schemas s ON t.schema_id = s.schema_id
            WHERE t.is_user_defined = 1"],
        ['drop_xml_schema_collections', "SELECT 'DROP XML SCHEMA COLLECTION ' + QUOTENAME(s.name) + '.' + QUOTENAME(x.name)
            FROM sys.xml_schema_collections x
            INNER JOIN sys.schemas s ON x.schema_id = s.schema_id
            WHERE x.schema_id > 4"],
        ['drop_partition_schemes', "SELECT 'DROP PARTITION SCHEME ' + QUOTENAME(ps.name)
            FROM sys.partition_schemes ps"],
        ['drop_partition_functions', "SELECT 'DROP PARTITION FUNCTION ' + QUOTENAME(pf.name)
            FROM sys.partition_functions pf"],
        ['drop_schemas', "SELECT 'DROP SCHEMA ' + QUOTENAME(s.name)
            FROM sys.schemas s
            WHERE s.name NOT IN ('dbo','guest','sys','INFORMATION_SCHEMA')
              AND s.schema_id > 4
              AND NOT EXISTS (
                  SELECT 1 FROM sys.objects o
                  WHERE o.schema_id = s.schema_id AND o.is_ms_shipped = 0
              )"],
    ];

    foreach ($Steps as [$Action, $Sql]) {
        executeDropStep($Db, $Action, $Sql, $Summary, $Logs);
    }

    return ['summary' => $Summary, 'logs' => $Logs];
}

function splitSqlBatches(string $Sql): array
{
    $Sql = str_replace("\r\n", "\n", $Sql);
    $Lines = explode("\n", $Sql);
    $Batches = [];
    $Current = [];

    foreach ($Lines as $Line) {
        if (preg_match('/^\s*GO\s*$/i', $Line)) {
            $Batch = trim(implode("\n", $Current));
            if ($Batch !== '') {
                $Batches[] = $Batch;
            }
            $Current = [];
            continue;
        }
        $Current[] = $Line;
    }

    $Batch = trim(implode("\n", $Current));
    if ($Batch !== '') {
        $Batches[] = $Batch;
    }

    return $Batches;
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

function runMigration(PDO $Db, string $Path, string $Filename, string $Checksum): array
{
    try {
        $Sql = file_get_contents($Path);
        if ($Sql === false) {
            throw new RuntimeException('SQL dosyasi okunamadi: ' . $Path);
        }

        $Batches = splitSqlBatches($Sql);
        if (empty($Batches)) {
            upsertMigration($Db, $Filename, $Checksum, 'applied', null);
            return ['ok' => true, 'message' => $Filename . ' bos bulundu, atlandi.'];
        }

        $Db->beginTransaction();
        foreach ($Batches as $Batch) {
            $Db->exec($Batch);
        }
        $Db->commit();
        upsertMigration($Db, $Filename, $Checksum, 'applied', null);
        return ['ok' => true, 'message' => $Filename . ' basariyla calistirildi.'];
    } catch (Throwable $e) {
        if ($Db->inTransaction()) {
            $Db->rollBack();
        }
        logException('migration:' . $Filename, $e);
        $Safe = sanitizeErrorMessage($e->getMessage());
        upsertMigration($Db, $Filename, $Checksum, 'failed', $Safe);
        return ['ok' => false, 'message' => $Filename . ' hata verdi: ' . $Safe];
    }
}

ensureAuth();

$Db = Database::connection();

$SqlDir = SRC_PATH . 'sql';
$Files = glob($SqlDir . DIRECTORY_SEPARATOR . '*.sql') ?: [];
sort($Files);
$FileNames = array_map('basename', $Files);

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$CsrfToken = $_SESSION['csrf_token'];

$Message = null;
$MessageType = 'info';
$ResetSummary = null;

$Migrations = [];
if (tableExists($Db, 'schema_migrations')) {
    $Rows = $Db->query("SELECT filename, checksum, status, error_message, applied_at FROM schema_migrations")
        ->fetchAll(PDO::FETCH_ASSOC);
    foreach ($Rows as $Row) {
        $Migrations[$Row['filename']] = $Row;
    }
}

$Statuses = [];
foreach ($Files as $FilePath) {
    $FileName = basename($FilePath);
    $Checksum = hash('sha256', (string)file_get_contents($FilePath));
    $Row = $Migrations[$FileName] ?? null;

    if ($Row === null) {
        $Statuses[$FileName] = ['status' => 'pending', 'note' => '', 'checksum' => $Checksum];
        continue;
    }

    if ($Row['status'] === 'failed') {
        $Statuses[$FileName] = ['status' => 'failed', 'note' => '', 'checksum' => $Checksum];
        continue;
    }

    if ($Row['checksum'] !== null && $Row['checksum'] !== $Checksum) {
        $Statuses[$FileName] = ['status' => 'pending', 'note' => 'checksum degisti', 'checksum' => $Checksum];
        continue;
    }

    $Statuses[$FileName] = ['status' => 'applied', 'note' => '', 'checksum' => $Checksum];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $Action = (string)($_POST['action'] ?? '');

    if ($Action === 'reset_db') {
        if (!isResetAllowed()) {
            header('HTTP/1.0 403 Forbidden');
            echo 'Forbidden';
            exit;
        }

        $DbName = (string)config('db.database');
        if (isSystemDatabase($DbName)) {
            header('HTTP/1.0 403 Forbidden');
            echo 'Forbidden';
            exit;
        }

        $ResetToken = (string)env('MIG_RESET_TOKEN', '');
        $ProvidedToken = (string)($_POST['reset_token'] ?? '');
        if ($ResetToken === '' || !hash_equals($ResetToken, $ProvidedToken)) {
            header('HTTP/1.0 403 Forbidden');
            echo 'Forbidden';
            exit;
        }
    }

    $Token = (string)($_POST['csrf_token'] ?? '');
    if (!hash_equals($CsrfToken, $Token)) {
        $Message = 'CSRF dogrulamasi basarisiz.';
        $MessageType = 'error';
    } else {
        if ($Action === 'run') {
            ensureSchemaMigrationLogs($Db);
            ensureSchemaMigrations($Db);
            $Requested = basename((string)($_POST['file'] ?? ''));
            if ($Requested === '' || !in_array($Requested, $FileNames, true)) {
                $Message = 'Gecersiz migration secimi.';
                $MessageType = 'error';
            } else {
                $Path = $SqlDir . DIRECTORY_SEPARATOR . $Requested;
                $Checksum = hash('sha256', (string)file_get_contents($Path));
                $Result = runMigration($Db, $Path, $Requested, $Checksum);
                $Message = $Result['message'];
                $MessageType = $Result['ok'] ? 'success' : 'error';
            }
        } elseif ($Action === 'run_all') {
            ensureSchemaMigrationLogs($Db);
            ensureSchemaMigrations($Db);
            $Pending = array_filter($Statuses, fn ($S) => $S['status'] !== 'applied');
            if (empty($Pending)) {

                $SeederResult = runSeeder();
                if ($SeederResult['ok']) {
                    $Message = 'Bekleyen migration yok. Seeder başarıyla çalıştırıldı.';
                    $MessageType = 'success';
                } else {
                    $Message = 'Bekleyen migration yok. Seeder hatası: ' . $SeederResult['message'];
                    $MessageType = 'warning';
                }
            } else {
                $Errors = [];
                foreach (array_keys($Pending) as $FileName) {
                    $Path = $SqlDir . DIRECTORY_SEPARATOR . $FileName;
                    $Checksum = hash('sha256', (string)file_get_contents($Path));
                    $Result = runMigration($Db, $Path, $FileName, $Checksum);
                    if (!$Result['ok']) {
                        $Errors[] = $Result['message'];
                    }
                }
                if (!empty($Errors)) {
                    $Message = implode(' | ', $Errors);
                    $MessageType = 'error';
                } else {

                    $SeederResult = runSeeder();
                    if ($SeederResult['ok']) {
                        $Message = 'Tüm migrationlar ve seeder başarıyla çalıştırıldı.';
                        $MessageType = 'success';
                    } else {
                        $Message = 'Migrationlar başarılı, seeder hatası: ' . $SeederResult['message'];
                        $MessageType = 'warning';
                    }
                }
            }
        } elseif ($Action === 'reset_db') {
            ensureSchemaMigrationLogs($Db);
            $ConfirmText = (string)($_POST['reset_confirm_text'] ?? '');
            $ConfirmCheck = (string)($_POST['reset_confirm_check'] ?? '');

            if ($ConfirmText !== 'RESET' || $ConfirmCheck !== '1') {
                $Message = 'Reset icin "RESET" yazin ve onay kutusunu isaretleyin.';
                $MessageType = 'error';
            } else {
                $Db->beginTransaction();
                try {
                    $Result = resetDatabaseObjects($Db);
                    $Db->commit();

                    $TotalDropped = 0;
                    foreach ($Result['summary'] as $Item) {
                        $TotalDropped += (int)$Item['dropped'];
                    }

                    $Result['logs'][] = ['reset_summary', 'DB sifirlama tamamlandi. Toplam silinen obje: ' . $TotalDropped, 'success', null];

                    $_SESSION['reset_logs'] = $Result['logs'];

                    $ResetSummary = 'DB sifirlama tamamlandi. Toplam silinen obje: ' . $TotalDropped;
                    $Message = $ResetSummary;
                    $MessageType = 'success';
                } catch (Throwable $e) {
                    if ($Db->inTransaction()) {
                        $Db->rollBack();
                    }
                    logException('reset_db', $e);
                    $Safe = sanitizeErrorMessage($e->getMessage());
                    logMigrationEvent($Db, 'reset_failed', 'DB sifirlama hata verdi.', 'failed', $Safe);
                    $Message = 'Reset islemi basarisiz: ' . $Safe;
                    $MessageType = 'error';
                }
            }
        }
    }

    $Migrations = [];
    if (tableExists($Db, 'schema_migrations')) {
        $Rows = $Db->query("SELECT filename, checksum, status, error_message, applied_at FROM schema_migrations")
            ->fetchAll(PDO::FETCH_ASSOC);
        foreach ($Rows as $Row) {
            $Migrations[$Row['filename']] = $Row;
        }
    }
    $Statuses = [];
    foreach ($Files as $FilePath) {
        $FileName = basename($FilePath);
        $Checksum = hash('sha256', (string)file_get_contents($FilePath));
        $Row = $Migrations[$FileName] ?? null;

        if ($Row === null) {
            $Statuses[$FileName] = ['status' => 'pending', 'note' => '', 'checksum' => $Checksum];
            continue;
        }

        if ($Row['status'] === 'failed') {
            $Statuses[$FileName] = ['status' => 'failed', 'note' => '', 'checksum' => $Checksum];
            continue;
        }

        if ($Row['checksum'] !== null && $Row['checksum'] !== $Checksum) {
            $Statuses[$FileName] = ['status' => 'pending', 'note' => 'checksum degisti', 'checksum' => $Checksum];
            continue;
        }

        $Statuses[$FileName] = ['status' => 'applied', 'note' => '', 'checksum' => $Checksum];
    }
}

$Logs = [];
if (tableExists($Db, 'schema_migrations')) {
    $Logs = $Db->query("SELECT TOP 50 id, filename, status, applied_at, error_message FROM schema_migrations ORDER BY id DESC")
        ->fetchAll(PDO::FETCH_ASSOC);
}

$ResetLogs = [];
if (tableExists($Db, 'schema_migration_logs')) {
    $ResetLogs = $Db->query("SELECT TOP 50 id, action, detail, status, created_at, error_message FROM schema_migration_logs ORDER BY id DESC")
        ->fetchAll(PDO::FETCH_ASSOC);
} elseif (!empty($_SESSION['reset_logs'])) {
    $ResetLogs = array_map(function ($Item, $Index) {
        return [
            'id' => $Index + 1,
            'action' => $Item[0] ?? '',
            'detail' => $Item[1] ?? '',
            'status' => $Item[2] ?? '',
            'created_at' => date('Y-m-d H:i:s'),
            'error_message' => $Item[3] ?? '',
        ];
    }, $_SESSION['reset_logs'], array_keys($_SESSION['reset_logs']));
}

function statusLabel(string $Status): string
{
    switch ($Status) {
        case 'applied':
            return 'Applied';
        case 'failed':
            return 'Failed';
        default:
            return 'Pending';
    }
}

function statusClass(string $Status): string
{
    switch ($Status) {
        case 'applied':
            return 'status applied';
        case 'failed':
            return 'status failed';
        default:
            return 'status pending';
    }
}
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Migration Runner</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 24px; background: #f7f7f7; }
        .card { background: #fff; border: 1px solid #ddd; border-radius: 6px; padding: 16px; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #e0e0e0; padding: 8px; text-align: left; }
        th { background: #f1f1f1; }
        .btn { padding: 6px 12px; border: 1px solid #333; background: #fff; cursor: pointer; }
        .btn-primary { background: #1f6feb; color: #fff; border-color: #1f6feb; }
        .msg { margin-bottom: 12px; padding: 8px 10px; border-radius: 4px; }
        .msg.success { background: #e7f6ea; border: 1px solid #b7e2c0; }
        .msg.error { background: #fdeaea; border: 1px solid #f5b6b6; }
        .msg.info { background: #eef5ff; border: 1px solid #c6dbff; }
        .status { display: inline-block; padding: 2px 8px; border-radius: 12px; font-size: 12px; }
        .status.applied { background: #e7f6ea; border: 1px solid #b7e2c0; }
        .status.pending { background: #fff6e6; border: 1px solid #f0d49a; }
        .status.failed { background: #fdeaea; border: 1px solid #f5b6b6; }
        .note { font-size: 12px; color: #555; margin-left: 6px; }
    </style>
</head>
<body>
    <?php if (isResetAllowed() && !isSystemDatabase((string)config('db.database'))): ?>
        <div class="card" style="border-color:#f5b6b6;background:#fff5f5;">
            <h3>Danger Zone</h3>
            <form method="post" id="reset-form">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($CsrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="action" value="reset_db">
                <div style="margin-bottom:8px;">
                    <label for="reset-token">Reset Token</label><br>
                    <input type="password" id="reset-token" name="reset_token" style="width:100%;max-width:360px;">
                </div>
                <div style="margin-bottom:8px;">
                    <label for="reset-confirm">RESET yazin</label><br>
                    <input type="text" id="reset-confirm" name="reset_confirm_text" style="width:100%;max-width:200px;">
                </div>
                <div style="margin-bottom:12px;">
                    <label>
                        <input type="checkbox" id="reset-check" name="reset_confirm_check" value="1">
                        TÜM VERİ SİLİNECEK, ANLADIM
                    </label>
                </div>
                <button type="submit" class="btn" id="reset-button" disabled>DB'yi Sıfırla</button>
            </form>
            <script>
                (function () {
                    var token = document.getElementById('reset-token');
                    var confirmText = document.getElementById('reset-confirm');
                    var check = document.getElementById('reset-check');
                    var button = document.getElementById('reset-button');
                    function toggle() {
                        var ok = token.value.trim().length > 0 && confirmText.value.trim() === 'RESET' && check.checked;
                        button.disabled = !ok;
                    }
                    token.addEventListener('input', toggle);
                    confirmText.addEventListener('input', toggle);
                    check.addEventListener('change', toggle);
                })();
            </script>
        </div>
    <?php endif; ?>

    <div class="card">
        <h3>Migration Runner</h3>
        <?php if ($Message): ?>
            <div class="msg <?= htmlspecialchars($MessageType, ENT_QUOTES, 'UTF-8') ?>">
                <?= htmlspecialchars($Message, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>
        <form method="post" style="margin-bottom: 12px;">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($CsrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="action" value="run_all">
            <button type="submit" class="btn btn-primary">Run All Pending</button>
        </form>
        <table>
            <thead>
                <tr>
                    <th>Migration</th>
                    <th>Durum</th>
                    <th>Çalıştır</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($FileNames)): ?>
                    <tr>
                        <td colspan="3">Migration bulunamadı.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($FileNames as $File): ?>
                        <?php $State = $Statuses[$File] ?? ['status' => 'pending', 'note' => '', 'checksum' => null]; ?>
                        <tr>
                            <td><?= htmlspecialchars($File, ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <span class="<?= statusClass($State['status']) ?>">
                                    <?= htmlspecialchars(statusLabel($State['status']), ENT_QUOTES, 'UTF-8') ?>
                                </span>
                                <?php if ($State['note']): ?>
                                    <span class="note">(<?= htmlspecialchars($State['note'], ENT_QUOTES, 'UTF-8') ?>)</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <form method="post" style="margin:0">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($CsrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="action" value="run">
                                    <input type="hidden" name="file" value="<?= htmlspecialchars($File, ENT_QUOTES, 'UTF-8') ?>">
                                    <button type="submit" class="btn">Çalıştır</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="card">
        <h3>Son 50 Migration Log</h3>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Dosya</th>
                    <th>Durum</th>
                    <th>Tarih</th>
                    <th>Hata</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($Logs)): ?>
                    <tr>
                        <td colspan="5">Log bulunamadı.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($Logs as $Log): ?>
                        <tr>
                            <td><?= htmlspecialchars((string)$Log['id'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string)$Log['filename'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars(statusLabel((string)$Log['status']), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string)$Log['applied_at'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string)$Log['error_message'], ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="card">
        <h3>Son 50 Reset Log</h3>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Adım</th>
                    <th>Detay</th>
                    <th>Durum</th>
                    <th>Tarih</th>
                    <th>Hata</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($ResetLogs)): ?>
                    <tr>
                        <td colspan="6">Log bulunamadı.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($ResetLogs as $Log): ?>
                        <tr>
                            <td><?= htmlspecialchars((string)$Log['id'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string)$Log['action'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string)$Log['detail'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string)$Log['status'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string)$Log['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string)$Log['error_message'], ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
