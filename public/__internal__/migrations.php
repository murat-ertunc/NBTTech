<?php
// Basit migration calistirma sayfasi (Basic Auth)
require dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'bootstrap' . DIRECTORY_SEPARATOR . 'app.php';

use App\Core\Database;

$AuthUser = env('MIGRATION_BASIC_USER', 'migrate');
$AuthPass = env('MIGRATION_BASIC_PASS', 'change-me');

$GivenUser = $_SERVER['PHP_AUTH_USER'] ?? '';
$GivenPass = $_SERVER['PHP_AUTH_PW'] ?? '';

if ($GivenUser !== $AuthUser || $GivenPass !== $AuthPass) {
    header('WWW-Authenticate: Basic realm="Migrations"');
    header('HTTP/1.0 401 Unauthorized');
    echo 'Yetkisiz';
    exit;
}

$SqlDir = SRC_PATH . 'sql';
$Files = glob($SqlDir . '/*.sql') ?: [];
sort($Files);
$FileNames = array_map('basename', $Files);

$Message = null;
$MessageType = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $Requested = basename((string)($_POST['file'] ?? ''));
    if ($Requested === '' || !in_array($Requested, $FileNames, true)) {
        $Message = 'Gecersiz migration secimi.';
        $MessageType = 'error';
    } else {
        $Path = $SqlDir . DIRECTORY_SEPARATOR . $Requested;
        try {
            $Db = Database::connection();
            $Sql = file_get_contents($Path);
            $Parts = preg_split('/^\s*GO\s*$/mi', $Sql);
            foreach ($Parts as $Part) {
                $Part = trim($Part);
                if ($Part !== '') {
                    $Db->exec($Part);
                }
            }
            $Message = $Requested . ' basariyla calistirildi.';
            $MessageType = 'success';
        } catch (Throwable $e) {
            $Message = 'Hata: ' . $e->getMessage();
            $MessageType = 'error';
        }
    }
}
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Migration Calistir</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 24px; background: #f7f7f7; }
        .card { background: #fff; border: 1px solid #ddd; border-radius: 6px; padding: 16px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #e0e0e0; padding: 8px; text-align: left; }
        th { background: #f1f1f1; }
        .btn { padding: 6px 12px; border: 1px solid #333; background: #fff; cursor: pointer; }
        .msg { margin-bottom: 12px; padding: 8px 10px; border-radius: 4px; }
        .msg.success { background: #e7f6ea; border: 1px solid #b7e2c0; }
        .msg.error { background: #fdeaea; border: 1px solid #f5b6b6; }
        .msg.info { background: #eef5ff; border: 1px solid #c6dbff; }
    </style>
</head>
<body>
    <div class="card">
        <h3>Migration Listesi</h3>
        <?php if ($Message): ?>
            <div class="msg <?= htmlspecialchars($MessageType, ENT_QUOTES, 'UTF-8') ?>">
                <?= htmlspecialchars($Message, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>
        <table>
            <thead>
                <tr>
                    <th>Migration Adı</th>
                    <th>Çalıştır</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($FileNames)): ?>
                    <tr>
                        <td colspan="2">Migration bulunamadı.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($FileNames as $File): ?>
                        <tr>
                            <td><?= htmlspecialchars($File, ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <form method="post" style="margin:0">
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
</body>
</html>
