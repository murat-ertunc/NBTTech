-- =============================================
-- FINAL: Superadmin Permission Sync
-- =============================================
-- Bu script TUM migration'lardan SONRA calisir ve
-- superadmin rolune TUM permission'lari atar.
-- 
-- ONEMLI: Bu dosya 999_ ile baslar, bu nedenle
-- her zaman en son calisir.
-- =============================================

PRINT '════════════════════════════════════════════════════════════';
PRINT 'FINAL: Superadmin Permission Sync';
PRINT '════════════════════════════════════════════════════════════';

DECLARE @SeedUserId INT = 1;
DECLARE @Simdi DATETIME2(0) = SYSUTCDATETIME();

-- Superadmin rol ID'sini bul
DECLARE @SuperAdminRolId INT;
SELECT @SuperAdminRolId = Id FROM tnm_rol WHERE RolKodu = 'superadmin' AND Sil = 0;

IF @SuperAdminRolId IS NULL
BEGIN
    PRINT '  ✗ HATA: Superadmin rolu bulunamadi!';
END
ELSE
BEGIN
    -- Toplam permission sayisi
    DECLARE @TotalPerms INT;
    SELECT @TotalPerms = COUNT(*) FROM tnm_permission WHERE Sil = 0 AND Aktif = 1;
    
    -- Superadmin mevcut permission sayisi
    DECLARE @CurrentPerms INT;
    SELECT @CurrentPerms = COUNT(*) FROM tnm_rol_permission WHERE RolId = @SuperAdminRolId AND Sil = 0;
    
    PRINT '  Toplam Permission: ' + CAST(@TotalPerms AS NVARCHAR);
    PRINT '  Superadmin Mevcut: ' + CAST(@CurrentPerms AS NVARCHAR);
    
    -- Eksik permission var mi?
    IF @CurrentPerms < @TotalPerms
    BEGIN
        PRINT '  ! Eksik permission tespit edildi, sync yapiliyor...';
        
        -- Mevcut atamalari temizle
        DELETE FROM tnm_rol_permission WHERE RolId = @SuperAdminRolId;
        
        -- TUM permission'lari ekle
        INSERT INTO tnm_rol_permission (Guid, EklemeZamani, EkleyenUserId, DegisiklikZamani, DegistirenUserId, Sil, RolId, PermissionId)
        SELECT NEWID(), @Simdi, @SeedUserId, @Simdi, @SeedUserId, 0, @SuperAdminRolId, p.Id
        FROM tnm_permission p
        WHERE p.Sil = 0 AND p.Aktif = 1;
        
        -- Yeni sayi
        SELECT @CurrentPerms = COUNT(*) FROM tnm_rol_permission WHERE RolId = @SuperAdminRolId AND Sil = 0;
        PRINT '  ✓ Superadmin rolune ' + CAST(@CurrentPerms AS NVARCHAR) + ' permission atandi';
    END
    ELSE
    BEGIN
        PRINT '  ✓ Superadmin zaten tum permission''lara sahip';
    END
END

-- Admin rolu icin de ayni islem
DECLARE @AdminRolId INT;
SELECT @AdminRolId = Id FROM tnm_rol WHERE RolKodu = 'admin' AND Sil = 0;

IF @AdminRolId IS NOT NULL
BEGIN
    DECLARE @AdminCurrentPerms INT;
    SELECT @AdminCurrentPerms = COUNT(*) FROM tnm_rol_permission WHERE RolId = @AdminRolId AND Sil = 0;
    
    DECLARE @AdminTotalPerms INT;
    SELECT @AdminTotalPerms = COUNT(*) FROM tnm_permission WHERE Sil = 0 AND Aktif = 1;
    
    IF @AdminCurrentPerms < @AdminTotalPerms
    BEGIN
        DELETE FROM tnm_rol_permission WHERE RolId = @AdminRolId;
        
        INSERT INTO tnm_rol_permission (Guid, EklemeZamani, EkleyenUserId, DegisiklikZamani, DegistirenUserId, Sil, RolId, PermissionId)
        SELECT NEWID(), @Simdi, @SeedUserId, @Simdi, @SeedUserId, 0, @AdminRolId, p.Id
        FROM tnm_permission p
        WHERE p.Sil = 0 AND p.Aktif = 1;
        
        SELECT @AdminCurrentPerms = COUNT(*) FROM tnm_rol_permission WHERE RolId = @AdminRolId AND Sil = 0;
        PRINT '  ✓ Admin rolune ' + CAST(@AdminCurrentPerms AS NVARCHAR) + ' permission atandi';
    END
    ELSE
    BEGIN
        PRINT '  ✓ Admin zaten tum permission''lara sahip';
    END
END

-- Superadmin kullanici-rol eslemesi
PRINT '';
PRINT '━━━ Superadmin Kullanici-Rol Eslemesi ━━━';

DECLARE @SuperAdminUserId INT;
SELECT @SuperAdminUserId = Id FROM tnm_user WHERE KullaniciAdi = 'superadmin' AND Sil = 0;

IF @SuperAdminUserId IS NOT NULL AND @SuperAdminRolId IS NOT NULL
BEGIN
    IF NOT EXISTS (SELECT 1 FROM tnm_user_rol WHERE UserId = @SuperAdminUserId AND RolId = @SuperAdminRolId AND Sil = 0)
    BEGIN
        INSERT INTO tnm_user_rol (Guid, EklemeZamani, EkleyenUserId, DegisiklikZamani, DegistirenUserId, Sil, UserId, RolId)
        VALUES (NEWID(), @Simdi, @SeedUserId, @Simdi, @SeedUserId, 0, @SuperAdminUserId, @SuperAdminRolId);
        PRINT '  ✓ Superadmin kullanicisina superadmin rolu atandi';
    END
    ELSE
    BEGIN
        PRINT '  ✓ Superadmin kullanici-rol eslemesi zaten mevcut';
    END
END

-- Final dogrulama
PRINT '';
PRINT '━━━ FINAL DOGRULAMA ━━━';

DECLARE @FinalTotal INT, @FinalSuperAdmin INT, @FinalMissing INT;
SELECT @FinalTotal = COUNT(*) FROM tnm_permission WHERE Sil = 0 AND Aktif = 1;
SELECT @FinalSuperAdmin = COUNT(*) FROM tnm_rol_permission rp 
    INNER JOIN tnm_rol r ON rp.RolId = r.Id 
    WHERE r.RolKodu = 'superadmin' AND r.Sil = 0 AND rp.Sil = 0;
SET @FinalMissing = @FinalTotal - @FinalSuperAdmin;

PRINT '  Toplam Permission     : ' + CAST(@FinalTotal AS NVARCHAR);
PRINT '  Superadmin Permission : ' + CAST(@FinalSuperAdmin AS NVARCHAR);
PRINT '  Eksik Permission      : ' + CAST(@FinalMissing AS NVARCHAR);

IF @FinalMissing = 0
BEGIN
    PRINT '';
    PRINT '  ✓ DOGRULAMA BASARILI: Superadmin tum permission''lara sahip!';
END
ELSE
BEGIN
    PRINT '';
    PRINT '  ✗ HATA: Superadmin''de ' + CAST(@FinalMissing AS NVARCHAR) + ' eksik permission var!';
END

PRINT '';
PRINT '════════════════════════════════════════════════════════════';
