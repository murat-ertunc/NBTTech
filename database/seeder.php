<?php
/**
 * Veritabani Seeder
 * 
 * Kullanim: php database/seeder.php
 */

require_once __DIR__ . '/../app/Core/bootstrap.php';

use App\Core\Database;

$Db = Database::connection();

// Kullanici listesi (Production: Sadece superadmin)
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
    // Silinmis kayitlari da dahil et (Sil kosulu yok)
    $Stmt = $Db->prepare("SELECT TOP 1 Id, Sil FROM tnm_user WHERE KullaniciAdi = :KullaniciAdi");
    $Stmt->execute(['KullaniciAdi' => $Kullanici['KullaniciAdi']]);
    $Mevcut = $Stmt->fetch();

    if (!$Mevcut) {
        $Simdi = date('Y-m-d H:i:s');
        $Guid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
        
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
        
        echo "✓ {$Kullanici['Rol']} kullanıcı oluşturuldu: {$Kullanici['KullaniciAdi']}\n";
    } elseif ((int) $Mevcut['Sil'] === 1) {
        // Silinmis kullaniciyi geri yukle
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
        echo "↻ {$Kullanici['Rol']} kullanıcı geri yüklendi: {$Kullanici['KullaniciAdi']}\n";
    } else {
        echo "• {$Kullanici['KullaniciAdi']} zaten mevcut.\n";
    }
}

echo "\n=== Varsayilan Kullanicilar ===";
echo "\n  Super Admin: superadmin / Super123!";
echo "\n\nSeeder tamamlandi.\n";
