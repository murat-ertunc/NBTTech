<?php

require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'bootstrap' . DIRECTORY_SEPARATOR . 'app.php';

use App\Core\Database;

echo "════════════════════════════════════════════════════════════\n";
echo "Permission Analiz Raporu\n";
echo "════════════════════════════════════════════════════════════\n\n";

try {
    $Db = Database::connection();

    echo "━━━ A1: PERMISSIONS TABLOSU ━━━\n";
    $Stmt = $Db->query("SELECT PermissionKodu FROM tnm_permission WHERE Sil = 0 AND Aktif = 1 ORDER BY PermissionKodu");
    $DbPermissions = $Stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Toplam: " . count($DbPermissions) . " adet\n";
    foreach ($DbPermissions as $P) {
        echo "  - {$P}\n";
    }

    echo "\n━━━ A2: SUPERADMIN PERMISSIONLARI ━━━\n";
    $Stmt2 = $Db->query("
        SELECT p.PermissionKodu
        FROM tnm_rol_permission rp
        JOIN tnm_rol r ON rp.RolId = r.Id AND r.RolKodu = 'superadmin' AND r.Sil = 0
        JOIN tnm_permission p ON rp.PermissionId = p.Id AND p.Sil = 0
        WHERE rp.Sil = 0
        ORDER BY p.PermissionKodu
    ");
    $SuperadminPerms = $Stmt2->fetchAll(PDO::FETCH_COLUMN);
    echo "Toplam: " . count($SuperadminPerms) . " adet\n";
    foreach ($SuperadminPerms as $P) {
        echo "  - {$P}\n";
    }

    echo "\n━━━ A3: SUPERADMIN MISSING CODES ━━━\n";
    $SuperadminMissing = array_diff($DbPermissions, $SuperadminPerms);
    echo "Eksik: " . count($SuperadminMissing) . " adet\n";
    foreach ($SuperadminMissing as $P) {
        echo "  ✗ {$P}\n";
    }

    echo "\n━━━ B: BEKLENEN PERMISSION SETİ ━━━\n";

    $CrudModuller = [
        'users',
        'roles',
        'customers',
        'invoices',
        'payments',
        'projects',
        'offers',
        'contracts',
        'guarantees',
        'meetings',
        'contacts',
        'files',
        'calendar',
        'stamp_taxes',
        'parameters',
    ];

    $ReadOnlyModuller = [
        'dashboard',
        'logs',
        'alarms',
    ];

    $CrudAksiyonlar = ['create', 'read', 'update', 'delete'];

    $ExpectedPermissions = [];

    foreach ($CrudModuller as $Modul) {
        foreach ($CrudAksiyonlar as $Aksiyon) {
            $ExpectedPermissions[] = "{$Modul}.{$Aksiyon}";
        }
    }

    foreach ($ReadOnlyModuller as $Modul) {
        $ExpectedPermissions[] = "{$Modul}.read";
    }

    sort($ExpectedPermissions);
    echo "Beklenen toplam: " . count($ExpectedPermissions) . " adet\n";
    foreach ($ExpectedPermissions as $P) {
        echo "  - {$P}\n";
    }

    echo "\n━━━ C: EXPECTED MISSING IN PERMISSIONS TABLE ━━━\n";
    $ExpectedMissingInDb = array_diff($ExpectedPermissions, $DbPermissions);
    echo "Eksik: " . count($ExpectedMissingInDb) . " adet\n";
    foreach ($ExpectedMissingInDb as $P) {
        echo "  ✗ {$P} (DB'de YOK!)\n";
    }

    echo "\n════════════════════════════════════════════════════════════\n";
    echo "ÖZET RAPOR\n";
    echo "════════════════════════════════════════════════════════════\n";
    echo "  DB'deki permission sayısı     : " . count($DbPermissions) . "\n";
    echo "  Beklenen permission sayısı    : " . count($ExpectedPermissions) . "\n";
    echo "  Superadmin permission sayısı  : " . count($SuperadminPerms) . "\n";
    echo "────────────────────────────────────────────────────────────\n";
    echo "  Expected missing in DB        : " . count($ExpectedMissingInDb) . "\n";
    echo "  Superadmin missing codes      : " . count($SuperadminMissing) . "\n";
    echo "════════════════════════════════════════════════════════════\n";

    echo "\n━━━ E: ÖZEL KANIT: calendar.create ━━━\n";

    $CalendarCreateInDb = in_array('calendar.create', $DbPermissions);
    $CalendarCreateInSuperadmin = in_array('calendar.create', $SuperadminPerms);

    echo "  calendar.create permissions tablosunda: " . ($CalendarCreateInDb ? "✓ VAR" : "✗ YOK") . "\n";
    echo "  calendar.create superadmin'de: " . ($CalendarCreateInSuperadmin ? "✓ VAR" : "✗ YOK") . "\n";

    if (count($ExpectedMissingInDb) === 0 && count($SuperadminMissing) === 0) {
        echo "\n✅ TÜM KONTROLLER BAŞARILI!\n";
        exit(0);
    } else {
        echo "\n⛔ EKSİKLER TESPİT EDİLDİ!\n";
        exit(1);
    }

} catch (Exception $e) {
    echo "HATA: " . $e->getMessage() . "\n";
    exit(1);
}
