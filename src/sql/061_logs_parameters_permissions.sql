-- =============================================
-- Logs ve Parameters Module Permission Ekleme
-- =============================================
-- logs.read - Log kayitlarini gorebilme
-- parameters.create - Parametre olusturabilme
-- parameters.update - Parametre guncelleyebilme
-- parameters.delete - Parametre silebilme

IF OBJECT_ID('tnm_permission', 'U') IS NULL
    OR OBJECT_ID('tnm_rol', 'U') IS NULL
    OR OBJECT_ID('tnm_rol_permission', 'U') IS NULL
BEGIN
    PRINT 'RBAC tablolari eksik, logs/parameters izinleri atlandi.';
    RETURN;
END

DECLARE @SeedUserId INT = 1;
DECLARE @Simdi DATETIME2(0) = SYSUTCDATETIME();

-- =============================================
-- logs.read - Log kayitlarini gorebilme
-- =============================================
IF NOT EXISTS (SELECT 1 FROM tnm_permission WHERE PermissionKodu = 'logs.read' AND Sil = 0)
BEGIN
    INSERT INTO tnm_permission (Guid, EklemeZamani, EkleyenUserId, DegisiklikZamani, DegistirenUserId, Sil, PermissionKodu, ModulAdi, Aksiyon, Aciklama, Aktif)
    VALUES (NEWID(), @Simdi, @SeedUserId, @Simdi, @SeedUserId, 0, 'logs.read', 'logs', 'read', 'Islem log kayitlarini gorebilme yetkisi', 1);
    PRINT 'logs.read permission eklendi.';
END
ELSE
BEGIN
    PRINT 'logs.read zaten mevcut.';
END

-- =============================================
-- parameters.create - Parametre olusturabilme
-- =============================================
IF NOT EXISTS (SELECT 1 FROM tnm_permission WHERE PermissionKodu = 'parameters.create' AND Sil = 0)
BEGIN
    INSERT INTO tnm_permission (Guid, EklemeZamani, EkleyenUserId, DegisiklikZamani, DegistirenUserId, Sil, PermissionKodu, ModulAdi, Aksiyon, Aciklama, Aktif)
    VALUES (NEWID(), @Simdi, @SeedUserId, @Simdi, @SeedUserId, 0, 'parameters.create', 'parameters', 'create', 'Sistem parametresi olusturabilme yetkisi', 1);
    PRINT 'parameters.create permission eklendi.';
END
ELSE
BEGIN
    PRINT 'parameters.create zaten mevcut.';
END

-- =============================================
-- parameters.update - Parametre guncelleyebilme
-- =============================================
IF NOT EXISTS (SELECT 1 FROM tnm_permission WHERE PermissionKodu = 'parameters.update' AND Sil = 0)
BEGIN
    INSERT INTO tnm_permission (Guid, EklemeZamani, EkleyenUserId, DegisiklikZamani, DegistirenUserId, Sil, PermissionKodu, ModulAdi, Aksiyon, Aciklama, Aktif)
    VALUES (NEWID(), @Simdi, @SeedUserId, @Simdi, @SeedUserId, 0, 'parameters.update', 'parameters', 'update', 'Sistem parametresi guncelleyebilme yetkisi', 1);
    PRINT 'parameters.update permission eklendi.';
END
ELSE
BEGIN
    PRINT 'parameters.update zaten mevcut.';
END

-- =============================================
-- parameters.delete - Parametre silebilme
-- =============================================
IF NOT EXISTS (SELECT 1 FROM tnm_permission WHERE PermissionKodu = 'parameters.delete' AND Sil = 0)
BEGIN
    INSERT INTO tnm_permission (Guid, EklemeZamani, EkleyenUserId, DegisiklikZamani, DegistirenUserId, Sil, PermissionKodu, ModulAdi, Aksiyon, Aciklama, Aktif)
    VALUES (NEWID(), @Simdi, @SeedUserId, @Simdi, @SeedUserId, 0, 'parameters.delete', 'parameters', 'delete', 'Sistem parametresi silebilme yetkisi', 1);
    PRINT 'parameters.delete permission eklendi.';
END
ELSE
BEGIN
    PRINT 'parameters.delete zaten mevcut.';
END

-- =============================================
-- Superadmin rolune bu permissionlari ata
-- =============================================
DECLARE @SuperadminRolId INT;
DECLARE @LogsReadId INT;
DECLARE @ParametersCreateId INT;
DECLARE @ParametersUpdateId INT;
DECLARE @ParametersDeleteId INT;

SELECT @SuperadminRolId = Id FROM tnm_rol WHERE RolKodu = 'superadmin' AND Sil = 0;
SELECT @LogsReadId = Id FROM tnm_permission WHERE PermissionKodu = 'logs.read' AND Sil = 0;
SELECT @ParametersCreateId = Id FROM tnm_permission WHERE PermissionKodu = 'parameters.create' AND Sil = 0;
SELECT @ParametersUpdateId = Id FROM tnm_permission WHERE PermissionKodu = 'parameters.update' AND Sil = 0;
SELECT @ParametersDeleteId = Id FROM tnm_permission WHERE PermissionKodu = 'parameters.delete' AND Sil = 0;

-- Superadmin'e logs.read ata
IF @SuperadminRolId IS NOT NULL AND @LogsReadId IS NOT NULL
BEGIN
    IF NOT EXISTS (SELECT 1 FROM tnm_rol_permission WHERE RolId = @SuperadminRolId AND PermissionId = @LogsReadId AND Sil = 0)
    BEGIN
        INSERT INTO tnm_rol_permission (Guid, EklemeZamani, EkleyenUserId, DegisiklikZamani, DegistirenUserId, Sil, RolId, PermissionId)
        VALUES (NEWID(), @Simdi, @SeedUserId, @Simdi, @SeedUserId, 0, @SuperadminRolId, @LogsReadId);
        PRINT 'Superadmin rolune logs.read atandi.';
    END
END

-- Superadmin'e parameters.create ata
IF @SuperadminRolId IS NOT NULL AND @ParametersCreateId IS NOT NULL
BEGIN
    IF NOT EXISTS (SELECT 1 FROM tnm_rol_permission WHERE RolId = @SuperadminRolId AND PermissionId = @ParametersCreateId AND Sil = 0)
    BEGIN
        INSERT INTO tnm_rol_permission (Guid, EklemeZamani, EkleyenUserId, DegisiklikZamani, DegistirenUserId, Sil, RolId, PermissionId)
        VALUES (NEWID(), @Simdi, @SeedUserId, @Simdi, @SeedUserId, 0, @SuperadminRolId, @ParametersCreateId);
        PRINT 'Superadmin rolune parameters.create atandi.';
    END
END

-- Superadmin'e parameters.update ata
IF @SuperadminRolId IS NOT NULL AND @ParametersUpdateId IS NOT NULL
BEGIN
    IF NOT EXISTS (SELECT 1 FROM tnm_rol_permission WHERE RolId = @SuperadminRolId AND PermissionId = @ParametersUpdateId AND Sil = 0)
    BEGIN
        INSERT INTO tnm_rol_permission (Guid, EklemeZamani, EkleyenUserId, DegisiklikZamani, DegistirenUserId, Sil, RolId, PermissionId)
        VALUES (NEWID(), @Simdi, @SeedUserId, @Simdi, @SeedUserId, 0, @SuperadminRolId, @ParametersUpdateId);
        PRINT 'Superadmin rolune parameters.update atandi.';
    END
END

-- Superadmin'e parameters.delete ata
IF @SuperadminRolId IS NOT NULL AND @ParametersDeleteId IS NOT NULL
BEGIN
    IF NOT EXISTS (SELECT 1 FROM tnm_rol_permission WHERE RolId = @SuperadminRolId AND PermissionId = @ParametersDeleteId AND Sil = 0)
    BEGIN
        INSERT INTO tnm_rol_permission (Guid, EklemeZamani, EkleyenUserId, DegisiklikZamani, DegistirenUserId, Sil, RolId, PermissionId)
        VALUES (NEWID(), @Simdi, @SeedUserId, @Simdi, @SeedUserId, 0, @SuperadminRolId, @ParametersDeleteId);
        PRINT 'Superadmin rolune parameters.delete atandi.';
    END
END

PRINT 'Logs ve Parameters permission ekleme tamamlandi.';
GO
