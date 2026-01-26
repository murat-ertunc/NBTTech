<?php
/**
 * Regression Test - Permission Access Simülasyonu
 * 
 * Bu script 3 kullanıcı tipini simüle eder:
 * 1. superadmin - TÜM permission'lara erişmeli
 * 2. limited_user - Sadece belirli modüllere erişmeli (yoksa oluşturur)
 * 3. no_role_user - Hiçbir permission'a erişmemeli
 */

require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'bootstrap' . DIRECTORY_SEPARATOR . 'app.php';

use App\Core\Database;

$Db = Database::connection();

echo "╔══════════════════════════════════════════════════════════════════╗\n";
echo "║         REGRESSION TEST - Permission Access Simülasyonu         ║\n";
echo "╠══════════════════════════════════════════════════════════════════╣\n";
echo "║ Tarih: " . date('Y-m-d H:i:s') . "                                   ║\n";
echo "╚══════════════════════════════════════════════════════════════════╝\n\n";

// GUID Generator
function generateGuid(): string {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

// =============================================
// HELPER: User'ın effective permissions'larını al
// =============================================
function getUserEffectivePermissions(PDO $Db, int $UserId): array
{
    $Stmt = $Db->prepare("
        SELECT DISTINCT p.PermissionKodu
        FROM tnm_user_rol ur
        INNER JOIN tnm_rol_permission rp ON ur.RolId = rp.RolId AND rp.Sil = 0
        INNER JOIN tnm_permission p ON rp.PermissionId = p.Id AND p.Sil = 0 AND p.Aktif = 1
        WHERE ur.UserId = :UserId AND ur.Sil = 0
    ");
    $Stmt->execute(['UserId' => $UserId]);
    return $Stmt->fetchAll(\PDO::FETCH_COLUMN);
}

// =============================================
// HELPER: Permission check simülasyonu
// =============================================
function canAccess(array $UserPerms, string $PermissionKodu): bool
{
    return in_array($PermissionKodu, $UserPerms);
}

// =============================================
// TEST DATA SETUP
// =============================================

echo "═══════════════════════════════════════════════════════════════════\n";
echo "1. TEST KULLANICILARI HAZIRLANIYOR\n";
echo "═══════════════════════════════════════════════════════════════════\n";

$Simdi = date('Y-m-d H:i:s');

// 1. Superadmin zaten var, ID'sini al
$Stmt = $Db->prepare("SELECT Id FROM tnm_user WHERE KullaniciAdi = 'superadmin' AND Sil = 0");
$Stmt->execute();
$SuperadminUser = $Stmt->fetch();
$SuperadminUserId = $SuperadminUser ? (int)$SuperadminUser['Id'] : 0;
echo "   ✓ superadmin User ID: {$SuperadminUserId}\n";

// 2. limited_user - sadece customers ve invoices erişimi
$Stmt = $Db->prepare("SELECT Id FROM tnm_user WHERE KullaniciAdi = 'limited_user' AND Sil = 0");
$Stmt->execute();
$LimitedUser = $Stmt->fetch();

if (!$LimitedUser) {
    // Kullanıcı oluştur
    $Guid = generateGuid();
    $Stmt = $Db->prepare("INSERT INTO tnm_user (Guid, EklemeZamani, DegisiklikZamani, KullaniciAdi, Parola, AdSoyad, Aktif, Sil)
                          VALUES (:Guid, :Simdi, :Simdi2, 'limited_user', :Parola, 'Limited User', 1, 0)");
    $Stmt->execute(['Guid' => $Guid, 'Simdi' => $Simdi, 'Simdi2' => $Simdi, 'Parola' => password_hash('Limited123!', PASSWORD_BCRYPT)]);
    $LimitedUserId = (int)$Db->lastInsertId();
    
    // limited_role oluştur (yoksa)
    $Stmt = $Db->prepare("SELECT Id FROM tnm_rol WHERE RolKodu = 'limited_role' AND Sil = 0");
    $Stmt->execute();
    $LimitedRol = $Stmt->fetch();
    
    if (!$LimitedRol) {
        $RolGuid = generateGuid();
        $Stmt = $Db->prepare("INSERT INTO tnm_rol (Guid, EklemeZamani, DegisiklikZamani, RolKodu, RolAdi, Aciklama, SistemRolu, Aktif, Sil)
                              VALUES (:Guid, :Simdi, :Simdi2, 'limited_role', 'Limited Role', 'Test icin sinirli yetki', 0, 1, 0)");
        $Stmt->execute(['Guid' => $RolGuid, 'Simdi' => $Simdi, 'Simdi2' => $Simdi]);
        $LimitedRolId = (int)$Db->lastInsertId();
        
        // customers.read, customers.create, invoices.read permission'larını ekle
        $PermKodlari = ['customers.read', 'customers.create', 'invoices.read', 'dashboard.read'];
        foreach ($PermKodlari as $PermKod) {
            $Stmt = $Db->prepare("SELECT Id FROM tnm_permission WHERE PermissionKodu = :Kod AND Sil = 0");
            $Stmt->execute(['Kod' => $PermKod]);
            $Perm = $Stmt->fetch();
            if ($Perm) {
                $RpGuid = generateGuid();
                $Stmt2 = $Db->prepare("INSERT INTO tnm_rol_permission (Guid, EklemeZamani, DegisiklikZamani, RolId, PermissionId, Sil)
                                       VALUES (:Guid, :Simdi, :Simdi2, :RolId, :PermId, 0)");
                $Stmt2->execute(['Guid' => $RpGuid, 'Simdi' => $Simdi, 'Simdi2' => $Simdi, 'RolId' => $LimitedRolId, 'PermId' => $Perm['Id']]);
            }
        }
    } else {
        $LimitedRolId = (int)$LimitedRol['Id'];
    }
    
    // User-role ataması
    $UrGuid = generateGuid();
    $Stmt = $Db->prepare("INSERT INTO tnm_user_rol (Guid, EklemeZamani, DegisiklikZamani, UserId, RolId, Sil)
                          VALUES (:Guid, :Simdi, :Simdi2, :UserId, :RolId, 0)");
    $Stmt->execute(['Guid' => $UrGuid, 'Simdi' => $Simdi, 'Simdi2' => $Simdi, 'UserId' => $LimitedUserId, 'RolId' => $LimitedRolId]);
    
    echo "   ✓ limited_user oluşturuldu, ID: {$LimitedUserId}\n";
} else {
    $LimitedUserId = (int)$LimitedUser['Id'];
    echo "   ✓ limited_user zaten var, ID: {$LimitedUserId}\n";
}

// 3. no_role_user - hiç rol atanmamış
$Stmt = $Db->prepare("SELECT Id FROM tnm_user WHERE KullaniciAdi = 'no_role_user' AND Sil = 0");
$Stmt->execute();
$NoRoleUser = $Stmt->fetch();

if (!$NoRoleUser) {
    $Guid = generateGuid();
    $Stmt = $Db->prepare("INSERT INTO tnm_user (Guid, EklemeZamani, DegisiklikZamani, KullaniciAdi, Parola, AdSoyad, Aktif, Sil)
                          VALUES (:Guid, :Simdi, :Simdi2, 'no_role_user', :Parola, 'No Role User', 1, 0)");
    $Stmt->execute(['Guid' => $Guid, 'Simdi' => $Simdi, 'Simdi2' => $Simdi, 'Parola' => password_hash('NoRole123!', PASSWORD_BCRYPT)]);
    $NoRoleUserId = (int)$Db->lastInsertId();
    echo "   ✓ no_role_user oluşturuldu, ID: {$NoRoleUserId}\n";
} else {
    $NoRoleUserId = (int)$NoRoleUser['Id'];
    echo "   ✓ no_role_user zaten var, ID: {$NoRoleUserId}\n";
}

// =============================================
// EFFECTIVE PERMISSIONS HESAPLAMA
// =============================================

echo "\n═══════════════════════════════════════════════════════════════════\n";
echo "2. EFFECTIVE PERMISSIONS HESAPLANIYOR\n";
echo "═══════════════════════════════════════════════════════════════════\n";

$SuperadminPerms = getUserEffectivePermissions($Db, $SuperadminUserId);
$LimitedPerms = getUserEffectivePermissions($Db, $LimitedUserId);
$NoRolePerms = getUserEffectivePermissions($Db, $NoRoleUserId);

echo "   superadmin  : " . count($SuperadminPerms) . " permission\n";
echo "   limited_user: " . count($LimitedPerms) . " permission\n";
echo "   no_role_user: " . count($NoRolePerms) . " permission\n";

// =============================================
// TEST SENARYOLARI
// =============================================

echo "\n═══════════════════════════════════════════════════════════════════\n";
echo "3. REGRESSION TEST SENARYOLARI\n";
echo "═══════════════════════════════════════════════════════════════════\n";

$TestResults = [];
$AllPassed = true;

// Test Helper
function runTest(string $TestName, bool $Condition, string $Expected, array &$Results, bool &$AllPassed): void
{
    $Status = $Condition ? '✅ PASS' : '❌ FAIL';
    $Results[] = ['name' => $TestName, 'passed' => $Condition, 'expected' => $Expected];
    if (!$Condition) $AllPassed = false;
    echo "   {$Status}: {$TestName} (Expected: {$Expected})\n";
}

// === SENARYO A: Superadmin TÜM modüllere erişmeli ===
echo "\n   ─── Senaryo A: Superadmin Erişimi ───\n";

runTest(
    'superadmin → dashboard.read',
    canAccess($SuperadminPerms, 'dashboard.read'),
    'ALLOW',
    $TestResults,
    $AllPassed
);

runTest(
    'superadmin → customers.read',
    canAccess($SuperadminPerms, 'customers.read'),
    'ALLOW',
    $TestResults,
    $AllPassed
);

runTest(
    'superadmin → calendar.create',
    canAccess($SuperadminPerms, 'calendar.create'),
    'ALLOW',
    $TestResults,
    $AllPassed
);

runTest(
    'superadmin → stamp_taxes.update',
    canAccess($SuperadminPerms, 'stamp_taxes.update'),
    'ALLOW',
    $TestResults,
    $AllPassed
);

runTest(
    'superadmin → logs.read',
    canAccess($SuperadminPerms, 'logs.read'),
    'ALLOW',
    $TestResults,
    $AllPassed
);

runTest(
    'superadmin → roles.delete',
    canAccess($SuperadminPerms, 'roles.delete'),
    'ALLOW',
    $TestResults,
    $AllPassed
);

// === SENARYO B: Limited User sadece atanan modüllere erişmeli ===
echo "\n   ─── Senaryo B: Limited User Erişimi ───\n";

runTest(
    'limited_user → customers.read',
    canAccess($LimitedPerms, 'customers.read'),
    'ALLOW',
    $TestResults,
    $AllPassed
);

runTest(
    'limited_user → customers.create',
    canAccess($LimitedPerms, 'customers.create'),
    'ALLOW',
    $TestResults,
    $AllPassed
);

runTest(
    'limited_user → invoices.read',
    canAccess($LimitedPerms, 'invoices.read'),
    'ALLOW',
    $TestResults,
    $AllPassed
);

runTest(
    'limited_user → calendar.read',
    !canAccess($LimitedPerms, 'calendar.read'), // DENY bekleniyor
    'DENY',
    $TestResults,
    $AllPassed
);

runTest(
    'limited_user → roles.update',
    !canAccess($LimitedPerms, 'roles.update'), // DENY bekleniyor
    'DENY',
    $TestResults,
    $AllPassed
);

runTest(
    'limited_user → stamp_taxes.delete',
    !canAccess($LimitedPerms, 'stamp_taxes.delete'), // DENY bekleniyor
    'DENY',
    $TestResults,
    $AllPassed
);

// === SENARYO C: No Role User hiçbir modüle erişmemeli ===
echo "\n   ─── Senaryo C: No Role User Erişimi ───\n";

runTest(
    'no_role_user → dashboard.read',
    !canAccess($NoRolePerms, 'dashboard.read'), // DENY bekleniyor
    'DENY',
    $TestResults,
    $AllPassed
);

runTest(
    'no_role_user → customers.read',
    !canAccess($NoRolePerms, 'customers.read'), // DENY bekleniyor
    'DENY',
    $TestResults,
    $AllPassed
);

runTest(
    'no_role_user → calendar.create',
    !canAccess($NoRolePerms, 'calendar.create'), // DENY bekleniyor
    'DENY',
    $TestResults,
    $AllPassed
);

// === SENARYO D: Customer-Detail Tab Görünürlüğü ===
echo "\n   ─── Senaryo D: Customer-Detail Tab Visibility ───\n";

$TabPermissions = [
    'bilgi' => 'customers.read',
    'kisiler' => 'contacts.read',
    'gorusme' => 'meetings.read',
    'takvim' => 'calendar.read',
    'damgavergisi' => 'stamp_taxes.read',
];

// Superadmin tüm tab'ları görmeli
$SuperadminVisibleTabs = array_filter($TabPermissions, fn($p) => canAccess($SuperadminPerms, $p));
runTest(
    'superadmin → tüm tab\'lar görünür',
    count($SuperadminVisibleTabs) === count($TabPermissions),
    '5/5 TAB',
    $TestResults,
    $AllPassed
);

// Limited user sadece customers.read tab'ını görmeli
$LimitedVisibleTabs = array_filter($TabPermissions, fn($p) => canAccess($LimitedPerms, $p));
runTest(
    'limited_user → sadece bilgi tab\'ı görünür',
    count($LimitedVisibleTabs) === 1 && isset($LimitedVisibleTabs['bilgi']),
    '1/5 TAB (bilgi)',
    $TestResults,
    $AllPassed
);

// No role user hiç tab görmemeli
$NoRoleVisibleTabs = array_filter($TabPermissions, fn($p) => canAccess($NoRolePerms, $p));
runTest(
    'no_role_user → hiç tab görünmez',
    count($NoRoleVisibleTabs) === 0,
    '0/5 TAB',
    $TestResults,
    $AllPassed
);

// =============================================
// ÖZET RAPOR
// =============================================

$PassedCount = count(array_filter($TestResults, fn($r) => $r['passed']));
$TotalCount = count($TestResults);

echo "\n╔══════════════════════════════════════════════════════════════════╗\n";
echo "║                    REGRESSION TEST ÖZET                          ║\n";
echo "╠══════════════════════════════════════════════════════════════════╣\n";

if ($AllPassed) {
    echo "║ ✅ TÜM TESTLER BAŞARILI ({$PassedCount}/{$TotalCount})                              ║\n";
} else {
    $FailedCount = $TotalCount - $PassedCount;
    echo "║ ❌ BAŞARISIZ TEST VAR ({$PassedCount}/{$TotalCount} geçti, {$FailedCount} başarısız)              ║\n";
}

echo "║                                                                  ║\n";
echo "║ Test Kullanıcıları:                                              ║\n";
echo "║   • superadmin   → " . str_pad(count($SuperadminPerms), 2, ' ', STR_PAD_LEFT) . " permission (tüm erişim)                 ║\n";
echo "║   • limited_user → " . str_pad(count($LimitedPerms), 2, ' ', STR_PAD_LEFT) . " permission (sınırlı erişim)              ║\n";
echo "║   • no_role_user →  " . str_pad(count($NoRolePerms), 1, ' ', STR_PAD_LEFT) . " permission (erişim yok)                  ║\n";
echo "╚══════════════════════════════════════════════════════════════════╝\n";

exit($AllPassed ? 0 : 1);
