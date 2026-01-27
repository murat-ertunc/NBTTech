-- =============================================
-- READ_ALL Permission Ekleme
-- =============================================
-- customers.read_all ve users.read_all permission'lari
-- Bu permission'lar tum kayitlari gorebilme yetkisi verir

IF OBJECT_ID('tnm_permission', 'U') IS NULL
    OR OBJECT_ID('tnm_rol', 'U') IS NULL
    OR OBJECT_ID('tnm_rol_permission', 'U') IS NULL
BEGIN
    PRINT 'RBAC tablolari eksik, read_all izinleri atlandi.';
    RETURN;
END

DECLARE @SeedUserId INT = 1;
DECLARE @Simdi DATETIME2(0) = SYSUTCDATETIME();

-- =============================================
-- customers.read_all - Tum musterileri gorebilme
-- =============================================
IF NOT EXISTS (SELECT 1 FROM tnm_permission WHERE PermissionKodu = 'customers.read_all' AND Sil = 0)
BEGIN
    INSERT INTO tnm_permission (Guid, EklemeZamani, EkleyenUserId, DegisiklikZamani, DegistirenUserId, Sil, PermissionKodu, ModulAdi, Aksiyon, Aciklama, Aktif)
    VALUES (NEWID(), @Simdi, @SeedUserId, @Simdi, @SeedUserId, 0, 'customers.read_all', 'customers', 'read_all', 'Tum musterileri gorebilme yetkisi (sadece kendi olusturdugu degil)', 1);
    PRINT 'customers.read_all permission eklendi.';
END
ELSE
BEGIN
    PRINT 'customers.read_all zaten mevcut.';
END

-- =============================================
-- users.read_all - Tum kullanicilari gorebilme
-- =============================================
IF NOT EXISTS (SELECT 1 FROM tnm_permission WHERE PermissionKodu = 'users.read_all' AND Sil = 0)
BEGIN
    INSERT INTO tnm_permission (Guid, EklemeZamani, EkleyenUserId, DegisiklikZamani, DegistirenUserId, Sil, PermissionKodu, ModulAdi, Aksiyon, Aciklama, Aktif)
    VALUES (NEWID(), @Simdi, @SeedUserId, @Simdi, @SeedUserId, 0, 'users.read_all', 'users', 'read_all', 'Tum kullanicilari gorebilme yetkisi (sadece kendi olusturdugu degil)', 1);
    PRINT 'users.read_all permission eklendi.';
END
ELSE
BEGIN
    PRINT 'users.read_all zaten mevcut.';
END

-- =============================================
-- Superadmin ve Admin rollerine bu permissionlari ata
-- =============================================
DECLARE @SuperadminRolId INT;
DECLARE @AdminRolId INT;
DECLARE @CustomersReadAllId INT;
DECLARE @UsersReadAllId INT;

SELECT @SuperadminRolId = Id FROM tnm_rol WHERE RolKodu = 'superadmin' AND Sil = 0;
SELECT @AdminRolId = Id FROM tnm_rol WHERE RolKodu = 'admin' AND Sil = 0;
SELECT @CustomersReadAllId = Id FROM tnm_permission WHERE PermissionKodu = 'customers.read_all' AND Sil = 0;
SELECT @UsersReadAllId = Id FROM tnm_permission WHERE PermissionKodu = 'users.read_all' AND Sil = 0;

-- Superadmin'e customers.read_all ata
IF @SuperadminRolId IS NOT NULL AND @CustomersReadAllId IS NOT NULL
BEGIN
    IF NOT EXISTS (SELECT 1 FROM tnm_rol_permission WHERE RolId = @SuperadminRolId AND PermissionId = @CustomersReadAllId AND Sil = 0)
    BEGIN
        INSERT INTO tnm_rol_permission (Guid, EklemeZamani, EkleyenUserId, DegisiklikZamani, DegistirenUserId, Sil, RolId, PermissionId)
        VALUES (NEWID(), @Simdi, @SeedUserId, @Simdi, @SeedUserId, 0, @SuperadminRolId, @CustomersReadAllId);
        PRINT 'Superadmin rolune customers.read_all atandi.';
    END
END

-- Superadmin'e users.read_all ata
IF @SuperadminRolId IS NOT NULL AND @UsersReadAllId IS NOT NULL
BEGIN
    IF NOT EXISTS (SELECT 1 FROM tnm_rol_permission WHERE RolId = @SuperadminRolId AND PermissionId = @UsersReadAllId AND Sil = 0)
    BEGIN
        INSERT INTO tnm_rol_permission (Guid, EklemeZamani, EkleyenUserId, DegisiklikZamani, DegistirenUserId, Sil, RolId, PermissionId)
        VALUES (NEWID(), @Simdi, @SeedUserId, @Simdi, @SeedUserId, 0, @SuperadminRolId, @UsersReadAllId);
        PRINT 'Superadmin rolune users.read_all atandi.';
    END
END

-- Admin'e customers.read_all ata
IF @AdminRolId IS NOT NULL AND @CustomersReadAllId IS NOT NULL
BEGIN
    IF NOT EXISTS (SELECT 1 FROM tnm_rol_permission WHERE RolId = @AdminRolId AND PermissionId = @CustomersReadAllId AND Sil = 0)
    BEGIN
        INSERT INTO tnm_rol_permission (Guid, EklemeZamani, EkleyenUserId, DegisiklikZamani, DegistirenUserId, Sil, RolId, PermissionId)
        VALUES (NEWID(), @Simdi, @SeedUserId, @Simdi, @SeedUserId, 0, @AdminRolId, @CustomersReadAllId);
        PRINT 'Admin rolune customers.read_all atandi.';
    END
END

-- Admin'e users.read_all ata
IF @AdminRolId IS NOT NULL AND @UsersReadAllId IS NOT NULL
BEGIN
    IF NOT EXISTS (SELECT 1 FROM tnm_rol_permission WHERE RolId = @AdminRolId AND PermissionId = @UsersReadAllId AND Sil = 0)
    BEGIN
        INSERT INTO tnm_rol_permission (Guid, EklemeZamani, EkleyenUserId, DegisiklikZamani, DegistirenUserId, Sil, RolId, PermissionId)
        VALUES (NEWID(), @Simdi, @SeedUserId, @Simdi, @SeedUserId, 0, @AdminRolId, @UsersReadAllId);
        PRINT 'Admin rolune users.read_all atandi.';
    END
END

PRINT 'READ_ALL permission ekleme tamamlandi.';
GO
