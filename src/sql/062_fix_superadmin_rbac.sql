-- =============================================
-- Superadmin RBAC Düzeltme Script'i
-- =============================================
-- Bu script:
-- 1. 'superadmin' rolünü oluşturur (yoksa)
-- 2. 'admin' rolünü oluşturur (yoksa)
-- 3. Superadmin rolüne TÜM permission'ları atar
-- 4. Admin rolüne TÜM permission'ları atar
-- 5. 'superadmin' kullanıcısına 'superadmin' rolünü atar

IF OBJECT_ID('tnm_rol', 'U') IS NULL
    OR OBJECT_ID('tnm_permission', 'U') IS NULL
    OR OBJECT_ID('tnm_rol_permission', 'U') IS NULL
    OR OBJECT_ID('tnm_user', 'U') IS NULL
    OR OBJECT_ID('tnm_user_rol', 'U') IS NULL
BEGIN
    PRINT 'RBAC tablolari eksik, superadmin duzeltme atlandi.';
    RETURN;
END

DECLARE @SeedUserId INT = 1;
DECLARE @Simdi DATETIME2(0) = SYSUTCDATETIME();

PRINT '════════════════════════════════════════════════════════════';
PRINT 'Superadmin RBAC Düzeltme Başlıyor...';
PRINT '════════════════════════════════════════════════════════════';

-- ━━━ 1. SUPERADMIN ROLÜ ━━━
PRINT '';
PRINT '━━━ 1. SUPERADMIN ROLÜ ━━━';

DECLARE @SuperAdminRolId INT;
SELECT @SuperAdminRolId = Id FROM tnm_rol WHERE RolKodu = 'superadmin' AND Sil = 0;

IF @SuperAdminRolId IS NULL
BEGIN
    INSERT INTO tnm_rol (Guid, EklemeZamani, EkleyenUserId, DegisiklikZamani, DegistirenUserId, Sil, RolAdi, RolKodu, Aciklama, Seviye, SistemRolu, Aktif)
    VALUES (NEWID(), @Simdi, @SeedUserId, @Simdi, @SeedUserId, 0, 'Super Admin', 'superadmin', 'Tum sistem yetkilerine sahip yonetici', 100, 1, 1);
    
    SELECT @SuperAdminRolId = Id FROM tnm_rol WHERE RolKodu = 'superadmin' AND Sil = 0;
    PRINT '  ✓ Superadmin rolü oluşturuldu (ID: ' + CAST(@SuperAdminRolId AS NVARCHAR) + ')';
END
ELSE
BEGIN
    PRINT '  ✓ Superadmin rolü zaten mevcut (ID: ' + CAST(@SuperAdminRolId AS NVARCHAR) + ')';
END

-- ━━━ 2. ADMIN ROLÜ ━━━
PRINT '';
PRINT '━━━ 2. ADMIN ROLÜ ━━━';

DECLARE @AdminRolId INT;
SELECT @AdminRolId = Id FROM tnm_rol WHERE RolKodu = 'admin' AND Sil = 0;

IF @AdminRolId IS NULL
BEGIN
    INSERT INTO tnm_rol (Guid, EklemeZamani, EkleyenUserId, DegisiklikZamani, DegistirenUserId, Sil, RolAdi, RolKodu, Aciklama, Seviye, SistemRolu, Aktif)
    VALUES (NEWID(), @Simdi, @SeedUserId, @Simdi, @SeedUserId, 0, 'Yönetici', 'admin', 'Genel yonetim yetkileri', 80, 0, 1);
    
    SELECT @AdminRolId = Id FROM tnm_rol WHERE RolKodu = 'admin' AND Sil = 0;
    PRINT '  ✓ Admin rolü oluşturuldu (ID: ' + CAST(@AdminRolId AS NVARCHAR) + ')';
END
ELSE
BEGIN
    PRINT '  ✓ Admin rolü zaten mevcut (ID: ' + CAST(@AdminRolId AS NVARCHAR) + ')';
END

-- ━━━ 3. SUPERADMIN ROLÜNE TÜM PERMISSION'LARI ATA ━━━
PRINT '';
PRINT '━━━ 3. SUPERADMIN ROLÜNE TÜM PERMISSION''LARI ATA ━━━';

-- Önce mevcut permission'ları temizle
DELETE FROM tnm_rol_permission WHERE RolId = @SuperAdminRolId;

-- Tüm aktif permission'ları ekle
INSERT INTO tnm_rol_permission (Guid, EklemeZamani, EkleyenUserId, DegisiklikZamani, DegistirenUserId, Sil, RolId, PermissionId)
SELECT NEWID(), @Simdi, @SeedUserId, @Simdi, @SeedUserId, 0, @SuperAdminRolId, p.Id
FROM tnm_permission p
WHERE p.Sil = 0 AND p.Aktif = 1;

DECLARE @SuperAdminPermCount INT;
SELECT @SuperAdminPermCount = COUNT(*) FROM tnm_rol_permission WHERE RolId = @SuperAdminRolId AND Sil = 0;
PRINT '  ✓ Superadmin rolüne ' + CAST(@SuperAdminPermCount AS NVARCHAR) + ' permission atandı';

-- ━━━ 4. ADMIN ROLÜNE TÜM PERMISSION'LARI ATA ━━━
PRINT '';
PRINT '━━━ 4. ADMIN ROLÜNE TÜM PERMISSION''LARI ATA ━━━';

-- Önce mevcut permission'ları temizle
DELETE FROM tnm_rol_permission WHERE RolId = @AdminRolId;

-- Tüm aktif permission'ları ekle
INSERT INTO tnm_rol_permission (Guid, EklemeZamani, EkleyenUserId, DegisiklikZamani, DegistirenUserId, Sil, RolId, PermissionId)
SELECT NEWID(), @Simdi, @SeedUserId, @Simdi, @SeedUserId, 0, @AdminRolId, p.Id
FROM tnm_permission p
WHERE p.Sil = 0 AND p.Aktif = 1;

DECLARE @AdminPermCount INT;
SELECT @AdminPermCount = COUNT(*) FROM tnm_rol_permission WHERE RolId = @AdminRolId AND Sil = 0;
PRINT '  ✓ Admin rolüne ' + CAST(@AdminPermCount AS NVARCHAR) + ' permission atandı';

-- ━━━ 5. SUPERADMIN KULLANICISINA SUPERADMIN ROLÜ ATA ━━━
PRINT '';
PRINT '━━━ 5. SUPERADMIN KULLANICISINA ROL ATA ━━━';

DECLARE @SuperAdminUserId INT;
SELECT @SuperAdminUserId = Id FROM tnm_user WHERE KullaniciAdi = 'superadmin' AND Sil = 0;

IF @SuperAdminUserId IS NULL
BEGIN
    PRINT '  ✗ HATA: superadmin kullanıcısı bulunamadı!';
END
ELSE
BEGIN
    -- Mevcut rol atamasını kontrol et
    DECLARE @MevcutAtama INT;
    SELECT @MevcutAtama = Id FROM tnm_user_rol 
    WHERE UserId = @SuperAdminUserId AND RolId = @SuperAdminRolId AND Sil = 0;
    
    IF @MevcutAtama IS NULL
    BEGIN
        -- Yeni atama yap
        INSERT INTO tnm_user_rol (Guid, EklemeZamani, EkleyenUserId, DegisiklikZamani, DegistirenUserId, Sil, UserId, RolId)
        VALUES (NEWID(), @Simdi, @SeedUserId, @Simdi, @SeedUserId, 0, @SuperAdminUserId, @SuperAdminRolId);
        
        PRINT '  ✓ Superadmin kullanıcısına (ID:' + CAST(@SuperAdminUserId AS NVARCHAR) + ') superadmin rolü atandı';
    END
    ELSE
    BEGIN
        PRINT '  ✓ Superadmin kullanıcısında (ID:' + CAST(@SuperAdminUserId AS NVARCHAR) + ') zaten superadmin rolü var';
    END
END

-- ━━━ 6. ÖZET RAPOR ━━━
PRINT '';
PRINT '════════════════════════════════════════════════════════════';
PRINT '                      ÖZET RAPOR                            ';
PRINT '════════════════════════════════════════════════════════════';

-- Toplam permission sayısı
DECLARE @ToplamPermission INT;
SELECT @ToplamPermission = COUNT(*) FROM tnm_permission WHERE Sil = 0 AND Aktif = 1;
PRINT 'Toplam Aktif Permission: ' + CAST(@ToplamPermission AS NVARCHAR);

-- Superadmin rolü durumu
PRINT '';
PRINT 'Superadmin Rolü:';
PRINT '  - Rol ID: ' + CAST(@SuperAdminRolId AS NVARCHAR);
PRINT '  - Permission Sayısı: ' + CAST(@SuperAdminPermCount AS NVARCHAR) + '/' + CAST(@ToplamPermission AS NVARCHAR);

IF @SuperAdminPermCount = @ToplamPermission
    PRINT '  - Durum: ✓ TÜM PERMISSION''LAR MEVCUT';
ELSE
    PRINT '  - Durum: ✗ EKSİK PERMISSION VAR!';

-- Admin rolü durumu
PRINT '';
PRINT 'Admin Rolü:';
PRINT '  - Rol ID: ' + CAST(@AdminRolId AS NVARCHAR);
PRINT '  - Permission Sayısı: ' + CAST(@AdminPermCount AS NVARCHAR) + '/' + CAST(@ToplamPermission AS NVARCHAR);

IF @AdminPermCount = @ToplamPermission
    PRINT '  - Durum: ✓ TÜM PERMISSION''LAR MEVCUT';
ELSE
    PRINT '  - Durum: ✗ EKSİK PERMISSION VAR!';

-- Superadmin kullanıcısı durumu
PRINT '';
PRINT 'Superadmin Kullanıcısı:';
IF @SuperAdminUserId IS NOT NULL
BEGIN
    PRINT '  - Kullanıcı ID: ' + CAST(@SuperAdminUserId AS NVARCHAR);
    
    DECLARE @SuperAdminKullaniciRolSayisi INT;
    SELECT @SuperAdminKullaniciRolSayisi = COUNT(*) 
    FROM tnm_user_rol 
    WHERE UserId = @SuperAdminUserId AND Sil = 0;
    
    PRINT '  - Atanmış Rol Sayısı: ' + CAST(@SuperAdminKullaniciRolSayisi AS NVARCHAR);
    
    IF @SuperAdminKullaniciRolSayisi > 0
        PRINT '  - Durum: ✓ ROL ATAMASI MEVCUT';
    ELSE
        PRINT '  - Durum: ✗ ROL ATAMASI YOK!';
END
ELSE
BEGIN
    PRINT '  - Durum: ✗ KULLANICI BULUNAMADI!';
END

PRINT '';
PRINT '════════════════════════════════════════════════════════════';
PRINT '✅ Superadmin RBAC düzeltmesi tamamlandı!';
PRINT '════════════════════════════════════════════════════════════';
GO
