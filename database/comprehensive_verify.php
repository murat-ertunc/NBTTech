<?php
/**
 * Kapsamlı Permission Doğrulama Scripti
 * 
 * Bu script:
 * 1. Seeder'daki expected permissions ile DB'deki permissions'ları karşılaştırır
 * 2. Superadmin'in tüm permission'lara sahip olduğunu doğrular
 * 3. Modül bazlı detaylı rapor çıkarır
 */

require_once __DIR__ . '/../app/Core/bootstrap.php';

use App\Core\Database;

$Db = Database::connection();

echo "╔══════════════════════════════════════════════════════════════════╗\n";
echo "║     KAPSAMLI PERMISSION DOĞRULAMA RAPORU                         ║\n";
echo "╠══════════════════════════════════════════════════════════════════╣\n";
echo "║ Tarih: " . date('Y-m-d H:i:s') . "                                   ║\n";
echo "╚══════════════════════════════════════════════════════════════════╝\n\n";

// =============================================
// 1. EXPECTED PERMISSIONS (Seeder'dan)
// =============================================

$ModulTanimlari = [
    'users' => ['create', 'read', 'read_all', 'update', 'delete'],
    'roles' => ['create', 'read', 'update', 'delete'],
    'customers' => ['create', 'read', 'read_all', 'update', 'delete'],
    'invoices' => ['create', 'read', 'read_all', 'update', 'delete'],
    'payments' => ['create', 'read', 'read_all', 'update', 'delete'],
    'projects' => ['create', 'read', 'read_all', 'update', 'delete'],
    'offers' => ['create', 'read', 'update', 'delete'],
    'contracts' => ['create', 'read', 'update', 'delete'],
    'guarantees' => ['create', 'read', 'update', 'delete'],
    'meetings' => ['create', 'read', 'update', 'delete'],
    'contacts' => ['create', 'read', 'update', 'delete'],
    'files' => ['create', 'read', 'update', 'delete'],
    'calendar' => ['create', 'read', 'update', 'delete'],
    'stamp_taxes' => ['create', 'read', 'update', 'delete'],
    'parameters' => ['create', 'read', 'update', 'delete'],
    'dashboard' => ['read'],
    'logs' => ['read'],
    'alarms' => ['read'],
];

$ExpectedPermissions = [];
foreach ($ModulTanimlari as $Modul => $Aksiyonlar) {
    foreach ($Aksiyonlar as $Aksiyon) {
        $ExpectedPermissions[] = "{$Modul}.{$Aksiyon}";
    }
}

echo "═══════════════════════════════════════════════════════════════════\n";
echo "1. EXPECTED PERMISSIONS (Seeder Tanımları)\n";
echo "═══════════════════════════════════════════════════════════════════\n";
echo "   Toplam Modül: " . count($ModulTanimlari) . "\n";
echo "   Toplam Permission: " . count($ExpectedPermissions) . "\n\n";

echo "   Modül Dağılımı:\n";
foreach ($ModulTanimlari as $Modul => $Aksiyonlar) {
    echo "   - {$Modul}: " . implode(', ', $Aksiyonlar) . "\n";
}

// =============================================
// 2. DATABASE'DEKİ PERMISSIONS
// =============================================

echo "\n═══════════════════════════════════════════════════════════════════\n";
echo "2. DATABASE MEVCUT PERMISSIONS\n";
echo "═══════════════════════════════════════════════════════════════════\n";

$Stmt = $Db->query("SELECT Id, PermissionKodu, ModulAdi, Aksiyon, Aktif FROM tnm_permission WHERE Sil = 0 ORDER BY ModulAdi, Aksiyon");
$DbPermissions = $Stmt->fetchAll(\PDO::FETCH_ASSOC);

echo "   DB'de Toplam Permission: " . count($DbPermissions) . "\n\n";

// Modül bazlı grupla
$DbModulGrup = [];
foreach ($DbPermissions as $P) {
    $DbModulGrup[$P['ModulAdi']][] = $P['Aksiyon'];
}

echo "   DB Modül Dağılımı:\n";
foreach ($DbModulGrup as $Modul => $Aksiyonlar) {
    echo "   - {$Modul}: " . implode(', ', $Aksiyonlar) . "\n";
}

// =============================================
// 3. KARŞILAŞTIRMA: EXPECTED vs DB
// =============================================

echo "\n═══════════════════════════════════════════════════════════════════\n";
echo "3. KARŞILAŞTIRMA: EXPECTED vs DATABASE\n";
echo "═══════════════════════════════════════════════════════════════════\n";

$DbKodlar = array_column($DbPermissions, 'PermissionKodu');

// Expected'da olup DB'de olmayan
$EksikDbde = array_diff($ExpectedPermissions, $DbKodlar);

// DB'de olup Expected'da olmayan
$FazlaDbde = array_diff($DbKodlar, $ExpectedPermissions);

if (empty($EksikDbde)) {
    echo "   ✅ EXPECTED → DB: Tüm beklenen permission'lar DB'de mevcut.\n";
} else {
    echo "   ❌ EXPECTED → DB: DB'de eksik permission'lar:\n";
    foreach ($EksikDbde as $Eksik) {
        echo "      - {$Eksik}\n";
    }
}

if (empty($FazlaDbde)) {
    echo "   ✅ DB → EXPECTED: DB'de fazla permission yok.\n";
} else {
    echo "   ⚠️  DB → EXPECTED: DB'de expected dışı permission'lar:\n";
    foreach ($FazlaDbde as $Fazla) {
        echo "      - {$Fazla}\n";
    }
}

// =============================================
// 4. SUPERADMIN PERMISSION KONTROLÜ
// =============================================

echo "\n═══════════════════════════════════════════════════════════════════\n";
echo "4. SUPERADMIN PERMISSION DURUMU\n";
echo "═══════════════════════════════════════════════════════════════════\n";

// Superadmin rol ID
$Stmt = $Db->query("SELECT Id FROM tnm_rol WHERE RolKodu = 'superadmin' AND Sil = 0");
$SuperadminRol = $Stmt->fetch();

if (!$SuperadminRol) {
    echo "   ❌ HATA: superadmin rolü bulunamadı!\n";
    exit(1);
}

$SuperadminRolId = $SuperadminRol['Id'];
echo "   Superadmin Rol ID: {$SuperadminRolId}\n";

// Superadmin'in permission'ları
$Stmt = $Db->prepare("
    SELECT p.PermissionKodu 
    FROM tnm_rol_permission rp
    INNER JOIN tnm_permission p ON rp.PermissionId = p.Id
    WHERE rp.RolId = :RolId AND rp.Sil = 0 AND p.Sil = 0 AND p.Aktif = 1
");
$Stmt->execute(['RolId' => $SuperadminRolId]);
$SuperadminPerms = $Stmt->fetchAll(\PDO::FETCH_COLUMN);

echo "   Superadmin Permission Sayısı: " . count($SuperadminPerms) . "\n";
echo "   DB Toplam Permission Sayısı: " . count($DbPermissions) . "\n";

// Superadmin'de eksik olanlar
$SuperadminEksik = array_diff($DbKodlar, $SuperadminPerms);

if (empty($SuperadminEksik)) {
    echo "   ✅ Superadmin TÜM permission'lara sahip.\n";
} else {
    echo "   ❌ Superadmin'de eksik permission'lar:\n";
    foreach ($SuperadminEksik as $Eksik) {
        echo "      - {$Eksik}\n";
    }
}

// =============================================
// 5. TAB PERMISSIONS KONTROLÜ (Customer Detail)
// =============================================

echo "\n═══════════════════════════════════════════════════════════════════\n";
echo "5. CUSTOMER-DETAIL TAB PERMISSIONS KONTROLÜ\n";
echo "═══════════════════════════════════════════════════════════════════\n";

$TabPermissions = [
    'bilgi' => 'customers.read',
    'kisiler' => 'contacts.read',
    'gorusme' => 'meetings.read',
    'projeler' => 'projects.read',
    'teklifler' => 'offers.read',
    'sozlesmeler' => 'contracts.read',
    'takvim' => 'calendar.read',
    'damgavergisi' => 'stamp_taxes.read',
    'teminatlar' => 'guarantees.read',
    'faturalar' => 'invoices.read',
    'odemeler' => 'payments.read',
    'dosyalar' => 'files.read',
];

$TabHatalar = [];
foreach ($TabPermissions as $Tab => $Perm) {
    if (!in_array($Perm, $DbKodlar)) {
        $TabHatalar[$Tab] = $Perm;
    }
}

if (empty($TabHatalar)) {
    echo "   ✅ Tüm tab permission'ları DB'de mevcut.\n";
    foreach ($TabPermissions as $Tab => $Perm) {
        echo "      ✓ {$Tab} → {$Perm}\n";
    }
} else {
    echo "   ❌ Tab permission hataları:\n";
    foreach ($TabHatalar as $Tab => $Perm) {
        echo "      ✗ {$Tab} → {$Perm} (DB'de yok!)\n";
    }
}

// =============================================
// 6. ÖZET RAPOR
// =============================================

echo "\n╔══════════════════════════════════════════════════════════════════╗\n";
echo "║                        ÖZET RAPOR                                ║\n";
echo "╠══════════════════════════════════════════════════════════════════╣\n";

$AllOk = empty($EksikDbde) && empty($SuperadminEksik) && empty($TabHatalar);

if ($AllOk) {
    echo "║ ✅ TÜM KONTROLLER BAŞARILI                                       ║\n";
    echo "║                                                                  ║\n";
    echo "║ • Expected Permissions: " . str_pad(count($ExpectedPermissions), 2, ' ', STR_PAD_LEFT) . "                                        ║\n";
    echo "║ • DB Permissions: " . str_pad(count($DbPermissions), 2, ' ', STR_PAD_LEFT) . "                                             ║\n";
    echo "║ • Superadmin Permissions: " . str_pad(count($SuperadminPerms), 2, ' ', STR_PAD_LEFT) . "                                    ║\n";
    echo "║ • Tab Permissions: " . str_pad(count($TabPermissions), 2, ' ', STR_PAD_LEFT) . " (hepsi mevcut)                           ║\n";
    echo "║ • DB Eksik: 0                                                    ║\n";
    echo "║ • Superadmin Eksik: 0                                            ║\n";
} else {
    echo "║ ❌ HATALAR TESPİT EDİLDİ                                          ║\n";
    echo "║                                                                  ║\n";
    if (!empty($EksikDbde)) {
        echo "║ • DB Eksik Permission: " . count($EksikDbde) . "                                       ║\n";
    }
    if (!empty($SuperadminEksik)) {
        echo "║ • Superadmin Eksik: " . count($SuperadminEksik) . "                                           ║\n";
    }
    if (!empty($TabHatalar)) {
        echo "║ • Tab Hataları: " . count($TabHatalar) . "                                               ║\n";
    }
}

echo "╚══════════════════════════════════════════════════════════════════╝\n";

exit($AllOk ? 0 : 1);
