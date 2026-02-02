-- =============================================
-- Role Seed Data ve Permission Atamalari
-- =============================================

IF OBJECT_ID('tnm_rol', 'U') IS NULL
    OR OBJECT_ID('tnm_permission', 'U') IS NULL
    OR OBJECT_ID('tnm_rol_permission', 'U') IS NULL
    OR OBJECT_ID('tnm_user', 'U') IS NULL
    OR OBJECT_ID('tnm_user_rol', 'U') IS NULL
BEGIN
    PRINT 'RBAC tablolari eksik, seed islemi atlandi.';
    RETURN;
END

DECLARE @SeedUserId INT = 1;
DECLARE @Simdi DATETIME2(0) = SYSUTCDATETIME();

-- =============================================
-- ROLLER
-- =============================================

-- 1. Super Admin - Tum yetkiler (Sistem rolu, silinemez)
IF NOT EXISTS (SELECT 1 FROM tnm_rol WHERE RolKodu = 'superadmin' AND Sil = 0)
BEGIN
    INSERT INTO tnm_rol (Guid, EklemeZamani, EkleyenUserId, DegisiklikZamani, DegistirenUserId, Sil, RolAdi, RolKodu, Aciklama, Seviye, SistemRolu, Aktif)
    VALUES (NEWID(), @Simdi, @SeedUserId, @Simdi, @SeedUserId, 0, 'Super Admin', 'superadmin', 'Tum sistem yetkilerine sahip yonetici', 100, 1, 1);
END

-- 2. Admin - Kullanici ve rol yonetimi haric tum yetkiler
IF NOT EXISTS (SELECT 1 FROM tnm_rol WHERE RolKodu = 'admin' AND Sil = 0)
BEGIN
    INSERT INTO tnm_rol (Guid, EklemeZamani, EkleyenUserId, DegisiklikZamani, DegistirenUserId, Sil, RolAdi, RolKodu, Aciklama, Seviye, SistemRolu, Aktif)
    VALUES (NEWID(), @Simdi, @SeedUserId, @Simdi, @SeedUserId, 0, 'Yonetici', 'admin', 'Genel yonetim yetkileri', 80, 0, 1);
END

-- 3. Editor - Tum modullerde okuma ve duzenleme (silme haric)
IF NOT EXISTS (SELECT 1 FROM tnm_rol WHERE RolKodu = 'editor' AND Sil = 0)
BEGIN
    INSERT INTO tnm_rol (Guid, EklemeZamani, EkleyenUserId, DegisiklikZamani, DegistirenUserId, Sil, RolAdi, RolKodu, Aciklama, Seviye, SistemRolu, Aktif)
    VALUES (NEWID(), @Simdi, @SeedUserId, @Simdi, @SeedUserId, 0, 'Editor', 'editor', 'Veri giris ve duzenleme yetkisi', 50, 0, 1);
END

-- 4. Viewer - Sadece okuma yetkisi
IF NOT EXISTS (SELECT 1 FROM tnm_rol WHERE RolKodu = 'viewer' AND Sil = 0)
BEGIN
    INSERT INTO tnm_rol (Guid, EklemeZamani, EkleyenUserId, DegisiklikZamani, DegistirenUserId, Sil, RolAdi, RolKodu, Aciklama, Seviye, SistemRolu, Aktif)
    VALUES (NEWID(), @Simdi, @SeedUserId, @Simdi, @SeedUserId, 0, 'Izleyici', 'viewer', 'Sadece okuma yetkisi', 10, 0, 1);
END

-- =============================================
-- ROL-PERMISSION ATAMALARI
-- =============================================

IF OBJECT_ID('tnm_rol_permission', 'U') IS NOT NULL
BEGIN
    -- Mevcut base rol atamalari temizle (sadece superadmin, admin, editor, viewer)
    DELETE FROM tnm_rol_permission
    WHERE RolId IN (
        SELECT Id FROM tnm_rol
        WHERE RolKodu IN ('superadmin', 'admin', 'editor', 'viewer') AND Sil = 0
    );
END
GO

IF OBJECT_ID('tnm_rol_permission', 'U') IS NOT NULL
BEGIN
    -- Super Admin: TUM YETKILER
    INSERT INTO tnm_rol_permission (Guid, EklemeZamani, EkleyenUserId, DegisiklikZamani, DegistirenUserId, Sil, RolId, PermissionId)
    SELECT NEWID(), SYSUTCDATETIME(), 1, SYSUTCDATETIME(), 1, 0, r.Id, p.Id
    FROM tnm_rol r
    CROSS JOIN tnm_permission p
    WHERE r.RolKodu = 'superadmin' AND r.Sil = 0 AND p.Sil = 0 AND p.Aktif = 1
      AND NOT EXISTS (
          SELECT 1 FROM tnm_rol_permission rp
          WHERE rp.RolId = r.Id AND rp.PermissionId = p.Id AND rp.Sil = 0
      );
END

IF OBJECT_ID('tnm_rol_permission', 'U') IS NOT NULL
BEGIN
    -- Admin (Yonetici): TUM YETKILER (superadmin ile ayni)
    INSERT INTO tnm_rol_permission (Guid, EklemeZamani, EkleyenUserId, DegisiklikZamani, DegistirenUserId, Sil, RolId, PermissionId)
    SELECT NEWID(), SYSUTCDATETIME(), 1, SYSUTCDATETIME(), 1, 0, r.Id, p.Id
    FROM tnm_rol r
    CROSS JOIN tnm_permission p
    WHERE r.RolKodu = 'admin' AND r.Sil = 0 AND p.Sil = 0 AND p.Aktif = 1
      AND NOT EXISTS (
          SELECT 1 FROM tnm_rol_permission rp
          WHERE rp.RolId = r.Id AND rp.PermissionId = p.Id AND rp.Sil = 0
      );
END

IF OBJECT_ID('tnm_rol_permission', 'U') IS NOT NULL
BEGIN
        -- Editor: Tum read + create + update (delete haric)
        -- NOT: dashboard ve alarms modulleri de bu sorguya dahil (read yetkisi otomatik gelir)
        INSERT INTO tnm_rol_permission (Guid, EklemeZamani, EkleyenUserId, DegisiklikZamani, DegistirenUserId, Sil, RolId, PermissionId)
        SELECT NEWID(), SYSUTCDATETIME(), 1, SYSUTCDATETIME(), 1, 0, r.Id, p.Id
        FROM tnm_rol r
        CROSS JOIN tnm_permission p
        WHERE r.RolKodu = 'editor' AND r.Sil = 0 AND p.Sil = 0 AND p.Aktif = 1
            AND p.ModulAdi NOT IN ('users', 'roles', 'logs', 'parameters')
            AND p.Aksiyon IN ('create', 'read', 'update')
            AND NOT EXISTS (
                    SELECT 1 FROM tnm_rol_permission rp
                    WHERE rp.RolId = r.Id AND rp.PermissionId = p.Id AND rp.Sil = 0
            );
END

IF OBJECT_ID('tnm_rol_permission', 'U') IS NOT NULL
BEGIN
        -- Viewer: Sadece read yetkiler (users, roles, logs haric)
        INSERT INTO tnm_rol_permission (Guid, EklemeZamani, EkleyenUserId, DegisiklikZamani, DegistirenUserId, Sil, RolId, PermissionId)
        SELECT NEWID(), SYSUTCDATETIME(), 1, SYSUTCDATETIME(), 1, 0, r.Id, p.Id
        FROM tnm_rol r
        CROSS JOIN tnm_permission p
        WHERE r.RolKodu = 'viewer' AND r.Sil = 0 AND p.Sil = 0 AND p.Aktif = 1
            AND p.ModulAdi NOT IN ('users', 'roles', 'logs', 'parameters')
            AND p.Aksiyon = 'read'
            AND NOT EXISTS (
                    SELECT 1 FROM tnm_rol_permission rp
                    WHERE rp.RolId = r.Id AND rp.PermissionId = p.Id AND rp.Sil = 0
            );
END

PRINT 'Role seed data ve permission atamalari eklendi.';
GO

-- =============================================
-- MEVCUT SUPERADMIN KULLANICISINA ROL ATA
-- =============================================
DECLARE @SuperAdminRolId INT;
SELECT @SuperAdminRolId = Id FROM tnm_rol WHERE RolKodu = 'superadmin' AND Sil = 0;

IF @SuperAdminRolId IS NOT NULL
BEGIN
    -- Mevcut superadmin kullanicilarina superadmin rolu ata
    INSERT INTO tnm_user_rol (Guid, EklemeZamani, EkleyenUserId, DegisiklikZamani, DegistirenUserId, Sil, UserId, RolId)
    SELECT NEWID(), SYSUTCDATETIME(), 1, SYSUTCDATETIME(), 1, 0, u.Id, @SuperAdminRolId
    FROM tnm_user u
    WHERE u.Rol = 'superadmin' AND u.Sil = 0
      AND NOT EXISTS (
          SELECT 1 FROM tnm_user_rol ur
          WHERE ur.UserId = u.Id AND ur.RolId = @SuperAdminRolId AND ur.Sil = 0
      );

    PRINT 'Mevcut superadmin kullanicilarina rol atandi.';
END
GO
