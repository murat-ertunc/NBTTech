<?php
/**
 * RBAC Analiz Script
 * Rolleri, kullanicilari ve permission'lari analiz eder
 */

require_once __DIR__ . '/../app/Core/bootstrap.php';

use App\Core\Database;

try {
    $Db = Database::connection();
    
    echo "\n╔════════════════════════════════════════════════════════════════╗\n";
    echo "║                    RBAC SISTEM ANALIZI                        ║\n";
    echo "╚════════════════════════════════════════════════════════════════╝\n\n";
    
    // 1. ROLLER
    echo "━━━ 1. ROLLER ━━━\n";
    $Roller = $Db->query("
        SELECT Id, RolAdi, RolKodu, Seviye, SistemRolu, Aktif 
        FROM tnm_rol 
        WHERE Sil = 0 
        ORDER BY Seviye DESC
    ")->fetchAll();
    
    foreach ($Roller as $Rol) {
        $SistemStr = $Rol['SistemRolu'] ? '[SİSTEM]' : '';
        $AktifStr = $Rol['Aktif'] ? '✓' : '✗';
        echo sprintf("  %s ID:%d | %s (%s) | Seviye:%d %s\n", 
            $AktifStr, $Rol['Id'], $Rol['RolAdi'], $Rol['RolKodu'], $Rol['Seviye'], $SistemStr);
    }
    
    // 2. KULLANICILAR
    echo "\n━━━ 2. KULLANICILAR ━━━\n";
    $Kullanicilar = $Db->query("
        SELECT Id, KullaniciAdi, AdSoyad, Rol, Aktif 
        FROM tnm_user 
        WHERE Sil = 0
    ")->fetchAll();
    
    foreach ($Kullanicilar as $K) {
        $AktifStr = $K['Aktif'] ? '✓' : '✗';
        echo sprintf("  %s ID:%d | %s (%s) | Eski Rol Alan: %s\n", 
            $AktifStr, $K['Id'], $K['AdSoyad'], $K['KullaniciAdi'], $K['Rol']);
    }
    
    // 3. KULLANICI-ROL İLİŞKİLERİ
    echo "\n━━━ 3. KULLANICI-ROL İLİŞKİLERİ (tnm_user_rol) ━━━\n";
    $UserRoller = $Db->query("
        SELECT u.Id as UserId, u.KullaniciAdi, u.AdSoyad, r.RolKodu, r.RolAdi 
        FROM tnm_user_rol ur
        JOIN tnm_user u ON ur.UserId = u.Id
        JOIN tnm_rol r ON ur.RolId = r.Id
        WHERE ur.Sil = 0
        ORDER BY u.KullaniciAdi
    ")->fetchAll();
    
    if (count($UserRoller) > 0) {
        foreach ($UserRoller as $UR) {
            echo sprintf("  • %s (ID:%d) ══> %s (%s)\n", 
                $UR['KullaniciAdi'], $UR['UserId'], $UR['RolKodu'], $UR['RolAdi']);
        }
    } else {
        echo "  ⚠️  UYARI: Hiç kullanıcı-rol ilişkisi bulunamadı!\n";
        echo "  💡 Kullanıcılar tnm_user_rol tablosuna eklenmelidir.\n";
    }
    
    // 4. PERMISSION'LAR
    echo "\n━━━ 4. PERMISSION'LAR ━━━\n";
    $PermCount = $Db->query("
        SELECT COUNT(*) as Toplam 
        FROM tnm_permission 
        WHERE Sil = 0 AND Aktif = 1
    ")->fetch();
    echo "  Toplam Aktif Permission: " . $PermCount['Toplam'] . "\n";
    
    // Modül bazında
    $PermModuller = $Db->query("
        SELECT ModulAdi, COUNT(*) as Adet
        FROM tnm_permission 
        WHERE Sil = 0 AND Aktif = 1
        GROUP BY ModulAdi
        ORDER BY COUNT(*) DESC
    ")->fetchAll();
    
    echo "\n  Modül Bazında:\n";
    foreach ($PermModuller as $PM) {
        echo sprintf("    - %s: %d permission\n", $PM['ModulAdi'], $PM['Adet']);
    }
    
    // 5. ROL-PERMISSION İLİŞKİLERİ
    echo "\n━━━ 5. ROL-PERMISSION İLİŞKİLERİ ━━━\n";
    $RolPerms = $Db->query("
        SELECT r.RolKodu, r.RolAdi, COUNT(rp.Id) as PermissionSayisi
        FROM tnm_rol r
        LEFT JOIN tnm_rol_permission rp ON r.Id = rp.RolId AND rp.Sil = 0
        WHERE r.Sil = 0
        GROUP BY r.Id, r.RolKodu, r.RolAdi
        ORDER BY COUNT(rp.Id) DESC
    ")->fetchAll();
    
    foreach ($RolPerms as $RP) {
        $Yuzde = $PermCount['Toplam'] > 0 ? round(($RP['PermissionSayisi'] / $PermCount['Toplam']) * 100, 1) : 0;
        echo sprintf("  • %s (%s): %d/%d permission (%s%%)\n", 
            $RP['RolKodu'], $RP['RolAdi'], $RP['PermissionSayisi'], $PermCount['Toplam'], $Yuzde);
    }
    
    // 6. SORUN TESPİTİ
    echo "\n━━━ 6. SORUN TESPİTİ ━━━\n";
    $Sorunlar = [];
    
    // Superadmin kullanıcısı var mı?
    $SuperAdmin = $Db->query("
        SELECT Id FROM tnm_user WHERE KullaniciAdi = 'superadmin' AND Sil = 0
    ")->fetch();
    
    if (!$SuperAdmin) {
        $Sorunlar[] = "✗ 'superadmin' kullanıcısı bulunamadı!";
    } else {
        // Superadmin'e rol atanmış mı?
        $SuperAdminRol = $Db->query("
            SELECT r.RolKodu, r.RolAdi
            FROM tnm_user_rol ur
            JOIN tnm_rol r ON ur.RolId = r.Id
            WHERE ur.UserId = {$SuperAdmin['Id']} AND ur.Sil = 0
        ")->fetch();
        
        if (!$SuperAdminRol) {
            $Sorunlar[] = "✗ 'superadmin' kullanıcısına hiç rol atanmamış! (tnm_user_rol boş)";
        } else {
            echo "  ✓ 'superadmin' kullanıcısı mevcut ve rolü: {$SuperAdminRol['RolKodu']}\n";
            
            // Superadmin rolünün tüm permission'ları var mı?
            $SuperAdminPermCount = $Db->query("
                SELECT COUNT(*) as Toplam
                FROM tnm_rol_permission rp
                JOIN tnm_rol r ON rp.RolId = r.Id
                WHERE r.RolKodu = 'superadmin' AND rp.Sil = 0
            ")->fetch();
            
            if ($SuperAdminPermCount['Toplam'] < $PermCount['Toplam']) {
                $Sorunlar[] = sprintf(
                    "✗ 'superadmin' rolünde sadece %d/%d permission var! Tüm permission'lar olmalı.", 
                    $SuperAdminPermCount['Toplam'], $PermCount['Toplam']
                );
            } else {
                echo "  ✓ 'superadmin' rolünde TÜM permission'lar mevcut ({$SuperAdminPermCount['Toplam']}/{$PermCount['Toplam']})\n";
            }
        }
    }
    
    // Admin rolü var mı?
    $AdminRol = $Db->query("
        SELECT Id FROM tnm_rol WHERE RolKodu = 'admin' AND Sil = 0
    ")->fetch();
    
    if (!$AdminRol) {
        $Sorunlar[] = "⚠️  'admin' rolü bulunamadı (opsiyonel)";
    }
    
    if (count($Sorunlar) > 0) {
        echo "\n  🔴 SORUNLAR:\n";
        foreach ($Sorunlar as $Sorun) {
            echo "    " . $Sorun . "\n";
        }
    } else {
        echo "  ✓ Hiçbir sorun tespit edilmedi!\n";
    }
    
    echo "\n╔════════════════════════════════════════════════════════════════╗\n";
    echo "║                     ANALİZ TAMAMLANDI                         ║\n";
    echo "╚════════════════════════════════════════════════════════════════╝\n\n";
    
} catch (\Exception $e) {
    echo "\n❌ HATA: " . $e->getMessage() . "\n\n";
    exit(1);
}
