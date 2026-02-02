<?php














require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'bootstrap' . DIRECTORY_SEPARATOR . 'app.php';

use App\Core\Database;

$Db = Database::connection();

echo "════════════════════════════════════════════════════════════\n";
echo "NBT Project Seeder Baslatildi\n";
echo "════════════════════════════════════════════════════════════\n\n";




function generateGuid(): string
{
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}




echo "━━━ 0. PERMISSION GENERATOR (UPSERT) ━━━\n";







$ModulTanimlari = [
    
    'users' => [
        'aksiyonlar' => ['create', 'read', 'read_all', 'update', 'delete'],
        'aciklamalar' => [
            'create' => 'Kullanici olusturma yetkisi',
            'read' => 'Kullanici listeleme yetkisi',
            'read_all' => 'Tum kullanicilari goruntuleme yetkisi',
            'update' => 'Kullanici guncelleme yetkisi',
            'delete' => 'Kullanici silme yetkisi',
        ],
    ],
    'roles' => [
        'aksiyonlar' => ['create', 'read', 'update', 'delete'],
        'aciklamalar' => [
            'create' => 'Rol olusturma yetkisi',
            'read' => 'Rol listeleme yetkisi',
            'update' => 'Rol guncelleme yetkisi',
            'delete' => 'Rol silme yetkisi',
        ],
    ],
    'customers' => [
        'aksiyonlar' => ['create', 'read', 'read_all', 'update', 'delete'],
        'aciklamalar' => [
            'create' => 'Musteri olusturma yetkisi',
            'read' => 'Musteri listeleme yetkisi',
            'read_all' => 'Tum musterileri goruntuleme yetkisi',
            'update' => 'Musteri guncelleme yetkisi',
            'delete' => 'Musteri silme yetkisi',
        ],
    ],
    'invoices' => [
        'aksiyonlar' => ['create', 'read', 'read_all', 'update', 'delete'],
        'aciklamalar' => [
            'create' => 'Fatura olusturma yetkisi',
            'read' => 'Fatura listeleme yetkisi',
            'read_all' => 'Tum faturalari goruntuleme yetkisi',
            'update' => 'Fatura guncelleme yetkisi',
            'delete' => 'Fatura silme yetkisi',
        ],
    ],
    'payments' => [
        'aksiyonlar' => ['create', 'read', 'read_all', 'update', 'delete'],
        'aciklamalar' => [
            'create' => 'Odeme olusturma yetkisi',
            'read' => 'Odeme listeleme yetkisi',
            'read_all' => 'Tum odemeleri goruntuleme yetkisi',
            'update' => 'Odeme guncelleme yetkisi',
            'delete' => 'Odeme silme yetkisi',
        ],
    ],
    'projects' => [
        'aksiyonlar' => ['create', 'read', 'read_all', 'update', 'delete'],
        'aciklamalar' => [
            'create' => 'Proje olusturma yetkisi',
            'read' => 'Proje listeleme yetkisi',
            'read_all' => 'Tum projeleri goruntuleme yetkisi',
            'update' => 'Proje guncelleme yetkisi',
            'delete' => 'Proje silme yetkisi',
        ],
    ],
    'offers' => [
        'aksiyonlar' => ['create', 'read', 'update', 'delete'],
        'aciklamalar' => [
            'create' => 'Teklif olusturma yetkisi',
            'read' => 'Teklif listeleme yetkisi',
            'update' => 'Teklif guncelleme yetkisi',
            'delete' => 'Teklif silme yetkisi',
        ],
    ],
    'contracts' => [
        'aksiyonlar' => ['create', 'read', 'update', 'delete'],
        'aciklamalar' => [
            'create' => 'Sozlesme olusturma yetkisi',
            'read' => 'Sozlesme listeleme yetkisi',
            'update' => 'Sozlesme guncelleme yetkisi',
            'delete' => 'Sozlesme silme yetkisi',
        ],
    ],
    'guarantees' => [
        'aksiyonlar' => ['create', 'read', 'update', 'delete'],
        'aciklamalar' => [
            'create' => 'Teminat olusturma yetkisi',
            'read' => 'Teminat listeleme yetkisi',
            'update' => 'Teminat guncelleme yetkisi',
            'delete' => 'Teminat silme yetkisi',
        ],
    ],
    'meetings' => [
        'aksiyonlar' => ['create', 'read', 'update', 'delete'],
        'aciklamalar' => [
            'create' => 'Gorusme olusturma yetkisi',
            'read' => 'Gorusme listeleme yetkisi',
            'update' => 'Gorusme guncelleme yetkisi',
            'delete' => 'Gorusme silme yetkisi',
        ],
    ],
    'contacts' => [
        'aksiyonlar' => ['create', 'read', 'update', 'delete'],
        'aciklamalar' => [
            'create' => 'Kisi olusturma yetkisi',
            'read' => 'Kisi listeleme yetkisi',
            'update' => 'Kisi guncelleme yetkisi',
            'delete' => 'Kisi silme yetkisi',
        ],
    ],
    'files' => [
        'aksiyonlar' => ['create', 'read', 'update', 'delete'],
        'aciklamalar' => [
            'create' => 'Dosya yukleme yetkisi',
            'read' => 'Dosya listeleme yetkisi',
            'update' => 'Dosya guncelleme yetkisi',
            'delete' => 'Dosya silme yetkisi',
        ],
    ],
    'calendar' => [
        'aksiyonlar' => ['create', 'read', 'update', 'delete'],
        'aciklamalar' => [
            'create' => 'Takvim kaydi olusturma yetkisi',
            'read' => 'Takvim listeleme yetkisi',
            'update' => 'Takvim guncelleme yetkisi',
            'delete' => 'Takvim silme yetkisi',
        ],
    ],
    'stamp_taxes' => [
        'aksiyonlar' => ['create', 'read', 'update', 'delete'],
        'aciklamalar' => [
            'create' => 'Damga vergisi olusturma yetkisi',
            'read' => 'Damga vergisi listeleme yetkisi',
            'update' => 'Damga vergisi guncelleme yetkisi',
            'delete' => 'Damga vergisi silme yetkisi',
        ],
    ],
    'parameters' => [
        'aksiyonlar' => ['create', 'read', 'update', 'delete'],
        'aciklamalar' => [
            'create' => 'Parametre olusturma yetkisi',
            'read' => 'Parametre listeleme yetkisi',
            'update' => 'Parametre guncelleme yetkisi',
            'delete' => 'Parametre silme yetkisi',
        ],
    ],
    
    'dashboard' => [
        'aksiyonlar' => ['read'],
        'aciklamalar' => [
            'read' => 'Dashboard goruntuleme yetkisi',
        ],
    ],
    'logs' => [
        'aksiyonlar' => ['read'],
        'aciklamalar' => [
            'read' => 'Islem kayitlarini goruntuleme yetkisi',
        ],
    ],
    'alarms' => [
        'aksiyonlar' => ['read'],
        'aciklamalar' => [
            'read' => 'Alarm listeleme yetkisi',
        ],
    ],
];


$ExpectedPermissions = [];
foreach ($ModulTanimlari as $Modul => $Tanim) {
    foreach ($Tanim['aksiyonlar'] as $Aksiyon) {
        $ExpectedPermissions[] = [
            'kod' => "{$Modul}.{$Aksiyon}",
            'modul' => $Modul,
            'aksiyon' => $Aksiyon,
            'aciklama' => $Tanim['aciklamalar'][$Aksiyon] ?? "{$Modul} {$Aksiyon} yetkisi",
        ];
    }
}

echo "  Beklenen permission sayisi: " . count($ExpectedPermissions) . "\n";


$Stmt = $Db->query("SELECT PermissionKodu FROM tnm_permission WHERE Sil = 0 AND Aktif = 1");
$MevcutKodlar = $Stmt->fetchAll(\PDO::FETCH_COLUMN);


$EklenenSayi = 0;
$Simdi = date('Y-m-d H:i:s');

foreach ($ExpectedPermissions as $Perm) {
    if (!in_array($Perm['kod'], $MevcutKodlar)) {
        $Guid = generateGuid();
        
        $InsertStmt = $Db->prepare("
            INSERT INTO tnm_permission (Guid, EklemeZamani, EkleyenUserId, DegisiklikZamani, DegistirenUserId, Sil, PermissionKodu, ModulAdi, Aksiyon, Aciklama, Aktif)
            VALUES (:Guid, :Simdi, 1, :Simdi2, 1, 0, :Kod, :Modul, :Aksiyon, :Aciklama, 1)
        ");
        $InsertStmt->execute([
            'Guid' => $Guid,
            'Simdi' => $Simdi,
            'Simdi2' => $Simdi,
            'Kod' => $Perm['kod'],
            'Modul' => $Perm['modul'],
            'Aksiyon' => $Perm['aksiyon'],
            'Aciklama' => $Perm['aciklama'],
        ]);
        
        echo "  + {$Perm['kod']} eklendi\n";
        $EklenenSayi++;
    }
}

if ($EklenenSayi > 0) {
    echo "  ✓ {$EklenenSayi} yeni permission eklendi\n";
} else {
    echo "  ✓ Tum expected permissions zaten mevcut\n";
}




echo "\n━━━ 1. KULLANICI SEED ━━━\n";





$Kullanicilar = [
    [
        'KullaniciAdi' => 'superadmin',
        'Parola' => password_hash('Super123!', PASSWORD_BCRYPT),
        'AdSoyad' => 'Sistem Yoneticisi',
        'Aktif' => 1,
        'Rol' => 'superadmin',
    ],
];

foreach ($Kullanicilar as $Kullanici) {
    $Stmt = $Db->prepare("SELECT TOP 1 Id, Sil FROM tnm_user WHERE KullaniciAdi = :KullaniciAdi");
    $Stmt->execute(['KullaniciAdi' => $Kullanici['KullaniciAdi']]);
    $Mevcut = $Stmt->fetch();

    if (!$Mevcut) {
        $Simdi = date('Y-m-d H:i:s');
        $Guid = generateGuid();
        
        $Sql = "INSERT INTO tnm_user (Guid, EklemeZamani, DegisiklikZamani, KullaniciAdi, Parola, AdSoyad, Aktif, Rol, Sil) 
                VALUES (:Guid, :EklemeZamani, :DegisiklikZamani, :KullaniciAdi, :Parola, :AdSoyad, :Aktif, :Rol, 0)";
        
        $Stmt = $Db->prepare($Sql);
        $Stmt->execute([
            'Guid' => $Guid,
            'EklemeZamani' => $Simdi,
            'DegisiklikZamani' => $Simdi,
            'KullaniciAdi' => $Kullanici['KullaniciAdi'],
            'Parola' => $Kullanici['Parola'],
            'AdSoyad' => $Kullanici['AdSoyad'],
            'Aktif' => $Kullanici['Aktif'],
            'Rol' => $Kullanici['Rol'],
        ]);
        
        echo "  ✓ {$Kullanici['Rol']} kullanici olusturuldu: {$Kullanici['KullaniciAdi']}\n";
    } elseif ((int) $Mevcut['Sil'] === 1) {
        $Simdi = date('Y-m-d H:i:s');
        $Sql = "UPDATE tnm_user SET Sil = 0, Aktif = :Aktif, Parola = :Parola, AdSoyad = :AdSoyad, DegisiklikZamani = :DegisiklikZamani WHERE Id = :Id";
        $Stmt = $Db->prepare($Sql);
        $Stmt->execute([
            'Id' => $Mevcut['Id'],
            'Aktif' => $Kullanici['Aktif'],
            'Parola' => $Kullanici['Parola'],
            'AdSoyad' => $Kullanici['AdSoyad'],
            'DegisiklikZamani' => $Simdi,
        ]);
        echo "  ↻ {$Kullanici['Rol']} kullanici geri yuklendi: {$Kullanici['KullaniciAdi']}\n";
    } else {
        echo "  • {$Kullanici['KullaniciAdi']} zaten mevcut.\n";
    }
}




echo "\n━━━ 2. SUPERADMIN PERMISSION SYNC ━━━\n";


$Stmt = $Db->prepare("SELECT Id FROM tnm_rol WHERE RolKodu = 'superadmin' AND Sil = 0");
$Stmt->execute();
$SuperAdminRol = $Stmt->fetch();

if ($SuperAdminRol) {
    $SuperAdminRolId = (int) $SuperAdminRol['Id'];
    
    
    $Stmt = $Db->prepare("SELECT COUNT(*) as cnt FROM tnm_permission WHERE Sil = 0 AND Aktif = 1");
    $Stmt->execute();
    $TotalPerms = (int) $Stmt->fetch()['cnt'];
    
    
    $Stmt = $Db->prepare("SELECT COUNT(*) as cnt FROM tnm_rol_permission WHERE RolId = :RolId AND Sil = 0");
    $Stmt->execute(['RolId' => $SuperAdminRolId]);
    $CurrentPerms = (int) $Stmt->fetch()['cnt'];
    
    echo "  Toplam Permission: {$TotalPerms}\n";
    echo "  Superadmin Mevcut: {$CurrentPerms}\n";
    
    
    if ($CurrentPerms < $TotalPerms) {
        echo "  ! Eksik permission tespit edildi, sync yapiliyor...\n";
        
        
        $Stmt = $Db->prepare("DELETE FROM tnm_rol_permission WHERE RolId = :RolId");
        $Stmt->execute(['RolId' => $SuperAdminRolId]);
        
        
        $Simdi = date('Y-m-d H:i:s');
        $Stmt = $Db->prepare("SELECT Id FROM tnm_permission WHERE Sil = 0 AND Aktif = 1");
        $Stmt->execute();
        $Permissions = $Stmt->fetchAll();
        
        $EklenenSayi = 0;
        foreach ($Permissions as $Perm) {
            $Guid = generateGuid();
            
            $InsertStmt = $Db->prepare("
                INSERT INTO tnm_rol_permission (Guid, EklemeZamani, EkleyenUserId, DegisiklikZamani, DegistirenUserId, Sil, RolId, PermissionId)
                VALUES (:Guid, :Simdi, 1, :Simdi2, 1, 0, :RolId, :PermissionId)
            ");
            $InsertStmt->execute([
                'Guid' => $Guid,
                'Simdi' => $Simdi,
                'Simdi2' => $Simdi,
                'RolId' => $SuperAdminRolId,
                'PermissionId' => $Perm['Id'],
            ]);
            $EklenenSayi++;
        }
        
        echo "  ✓ Superadmin rolune {$EklenenSayi} permission atandi\n";
    } else {
        echo "  ✓ Superadmin zaten tum permission'lara sahip\n";
    }
} else {
    echo "  ✗ Superadmin rolu bulunamadi! Once SQL migration'lari calistirin.\n";
}




echo "\n━━━ 3. SUPERADMIN KULLANICI-ROL ESLEMESI ━━━\n";


$Stmt = $Db->prepare("SELECT Id FROM tnm_user WHERE KullaniciAdi = 'superadmin' AND Sil = 0");
$Stmt->execute();
$SuperAdminUser = $Stmt->fetch();

if ($SuperAdminUser && $SuperAdminRol) {
    $SuperAdminUserId = (int) $SuperAdminUser['Id'];
    $SuperAdminRolId = (int) $SuperAdminRol['Id'];
    
    
    $Stmt = $Db->prepare("SELECT Id FROM tnm_user_rol WHERE UserId = :UserId AND RolId = :RolId AND Sil = 0");
    $Stmt->execute(['UserId' => $SuperAdminUserId, 'RolId' => $SuperAdminRolId]);
    $MevcutEsleme = $Stmt->fetch();
    
    if (!$MevcutEsleme) {
        $Simdi = date('Y-m-d H:i:s');
        $Guid = generateGuid();
        
        $Stmt = $Db->prepare("
            INSERT INTO tnm_user_rol (Guid, EklemeZamani, EkleyenUserId, DegisiklikZamani, DegistirenUserId, Sil, UserId, RolId)
            VALUES (:Guid, :Simdi, 1, :Simdi2, 1, 0, :UserId, :RolId)
        ");
        $Stmt->execute([
            'Guid' => $Guid,
            'Simdi' => $Simdi,
            'Simdi2' => $Simdi,
            'UserId' => $SuperAdminUserId,
            'RolId' => $SuperAdminRolId,
        ]);
        echo "  ✓ Superadmin kullanicisina superadmin rolu atandi\n";
    } else {
        echo "  ✓ Superadmin kullanici-rol eslemesi zaten mevcut\n";
    }
} else {
    echo "  ✗ Superadmin kullanici veya rol bulunamadi!\n";
}




echo "\n━━━ 4. DOGRULAMA (FAIL-FAST) ━━━\n";


echo "  4A. Expected Permissions Kontrolu:\n";
$Stmt = $Db->query("SELECT PermissionKodu FROM tnm_permission WHERE Sil = 0 AND Aktif = 1");
$DbPermKodlari = $Stmt->fetchAll(\PDO::FETCH_COLUMN);

$ExpectedKodlar = array_column($ExpectedPermissions, 'kod');
$ExpectedMissingInDb = array_diff($ExpectedKodlar, $DbPermKodlari);

echo "      Beklenen: " . count($ExpectedKodlar) . "\n";
echo "      DB'de var: " . count($DbPermKodlari) . "\n";
echo "      Expected missing in DB: " . count($ExpectedMissingInDb) . "\n";

if (count($ExpectedMissingInDb) > 0) {
    echo "\n  ✗ HATA: Beklenen permissionlar DB'de yok!\n";
    foreach ($ExpectedMissingInDb as $Kod) {
        echo "    - {$Kod}\n";
    }
    exit(1);
} else {
    echo "      ✓ Tum expected permissions DB'de mevcut\n";
}


echo "\n  4B. Superadmin Permission Kontrolu:\n";
$Stmt = $Db->prepare("SELECT COUNT(*) as cnt FROM tnm_permission WHERE Sil = 0 AND Aktif = 1");
$Stmt->execute();
$TotalPerms = (int) $Stmt->fetch()['cnt'];

$Stmt = $Db->prepare("
    SELECT COUNT(*) as cnt 
    FROM tnm_rol_permission rp 
    INNER JOIN tnm_rol r ON rp.RolId = r.Id 
    WHERE r.RolKodu = 'superadmin' AND r.Sil = 0 AND rp.Sil = 0
");
$Stmt->execute();
$SuperAdminPerms = (int) $Stmt->fetch()['cnt'];

$MissingSayi = $TotalPerms - $SuperAdminPerms;

echo "      Toplam Permission     : {$TotalPerms}\n";
echo "      Superadmin Permission : {$SuperAdminPerms}\n";
echo "      Superadmin Missing    : {$MissingSayi}\n";

if ($MissingSayi === 0) {
    echo "      ✓ Superadmin tum permission'lara sahip!\n";
} else {
    
    echo "\n  ✗ HATA: Superadmin'de {$MissingSayi} eksik permission var!\n";
    echo "\n  Eksik Permissionlar:\n";
    
    $MissingStmt = $Db->prepare("
        SELECT p.PermissionKodu 
        FROM tnm_permission p 
        LEFT JOIN tnm_rol_permission rp ON rp.PermissionId = p.Id 
            AND rp.RolId = (SELECT Id FROM tnm_rol WHERE RolKodu = 'superadmin' AND Sil = 0)
            AND rp.Sil = 0
        WHERE p.Sil = 0 AND p.Aktif = 1 AND rp.PermissionId IS NULL
        ORDER BY p.PermissionKodu
    ");
    $MissingStmt->execute();
    $MissingPerms = $MissingStmt->fetchAll(\PDO::FETCH_COLUMN);
    
    foreach ($MissingPerms as $Perm) {
        echo "    - {$Perm}\n";
    }
    
    
    $MissingFile = __DIR__ . '/missing_permissions.txt';
    file_put_contents($MissingFile, implode("\n", $MissingPerms));
    echo "\n  Eksik permissionlar dosyaya yazildi: {$MissingFile}\n";
    
    echo "\n════════════════════════════════════════════════════════════\n";
    echo "  ⛔ SEEDER BASARISIZ: Eksik permission var!\n";
    echo "════════════════════════════════════════════════════════════\n";
    exit(1);
}




echo "\n━━━ 5. EFFECTIVE PERMISSIONS KONTROLU ━━━\n";

$EffectiveStmt = $Db->prepare("
    SELECT COUNT(DISTINCT rp.PermissionId) as cnt
    FROM tnm_user u
    JOIN tnm_user_rol ur ON ur.UserId = u.Id AND ur.Sil = 0
    JOIN tnm_rol r ON r.Id = ur.RolId AND r.Sil = 0
    JOIN tnm_rol_permission rp ON rp.RolId = r.Id AND rp.Sil = 0
    WHERE u.KullaniciAdi = 'superadmin' AND u.Sil = 0
");
$EffectiveStmt->execute();
$EffectivePerms = (int) $EffectiveStmt->fetch()['cnt'];

echo "  User->Role->Permission Zinciri: {$EffectivePerms} permission\n";

if ($EffectivePerms === $TotalPerms) {
    echo "  ✓ Superadmin kullanicisi TUM permission'lara erisebilir!\n";
} else {
    echo "  ✗ HATA: Superadmin kullanicisinin effective permission'lari eksik!\n";
    echo "    Beklenen: {$TotalPerms}, Gercek: {$EffectivePerms}\n";
    exit(1);
}

echo "\n════════════════════════════════════════════════════════════\n";
echo "=== Varsayilan Kullanicilar ===\n";
echo "  Super Admin: superadmin / Super123!\n";
echo "\n✅ Seeder BASARIYLA tamamlandi.\n";
echo "════════════════════════════════════════════════════════════\n";
