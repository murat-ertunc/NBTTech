-- =============================================
-- Role Seed Data ve Permission Atamalari
-- =============================================

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

-- Mevcut atamalari temizle
UPDATE tnm_rol_permission SET Sil = 1 WHERE Sil = 0;
GO

-- Super Admin: TUM YETKILER
INSERT INTO tnm_rol_permission (Guid, EklemeZamani, EkleyenUserId, DegisiklikZamani, DegistirenUserId, Sil, RolId, PermissionId)
SELECT NEWID(), SYSUTCDATETIME(), 1, SYSUTCDATETIME(), 1, 0, r.Id, p.Id
FROM tnm_rol r
CROSS JOIN tnm_permission p
WHERE r.RolKodu = 'superadmin' AND r.Sil = 0 AND p.Sil = 0 AND p.Aktif = 1;

-- Admin (Yonetici): TUM YETKILER (superadmin ile ayni)
INSERT INTO tnm_rol_permission (Guid, EklemeZamani, EkleyenUserId, DegisiklikZamani, DegistirenUserId, Sil, RolId, PermissionId)
SELECT NEWID(), SYSUTCDATETIME(), 1, SYSUTCDATETIME(), 1, 0, r.Id, p.Id
FROM tnm_rol r
CROSS JOIN tnm_permission p
WHERE r.RolKodu = 'admin' AND r.Sil = 0 AND p.Sil = 0 AND p.Aktif = 1;

-- Editor: Tum read + create + update (delete haric)
INSERT INTO tnm_rol_permission (Guid, EklemeZamani, EkleyenUserId, DegisiklikZamani, DegistirenUserId, Sil, RolId, PermissionId)
SELECT NEWID(), SYSUTCDATETIME(), 1, SYSUTCDATETIME(), 1, 0, r.Id, p.Id
FROM tnm_rol r
CROSS JOIN tnm_permission p
WHERE r.RolKodu = 'editor' AND r.Sil = 0 AND p.Sil = 0 AND p.Aktif = 1
  AND p.ModulAdi NOT IN ('users', 'roles', 'logs', 'parameters')
  AND p.Aksiyon IN ('create', 'read', 'update');

-- Editor: Dashboard ve Alarm sadece read
INSERT INTO tnm_rol_permission (Guid, EklemeZamani, EkleyenUserId, DegisiklikZamani, DegistirenUserId, Sil, RolId, PermissionId)
SELECT NEWID(), SYSUTCDATETIME(), 1, SYSUTCDATETIME(), 1, 0, r.Id, p.Id
FROM tnm_rol r
CROSS JOIN tnm_permission p
WHERE r.RolKodu = 'editor' AND r.Sil = 0 AND p.Sil = 0 AND p.Aktif = 1
  AND p.ModulAdi IN ('dashboard', 'alarms') AND p.Aksiyon = 'read';

-- Viewer: Sadece read yetkiler (users, roles, logs haric)
INSERT INTO tnm_rol_permission (Guid, EklemeZamani, EkleyenUserId, DegisiklikZamani, DegistirenUserId, Sil, RolId, PermissionId)
SELECT NEWID(), SYSUTCDATETIME(), 1, SYSUTCDATETIME(), 1, 0, r.Id, p.Id
FROM tnm_rol r
CROSS JOIN tnm_permission p
WHERE r.RolKodu = 'viewer' AND r.Sil = 0 AND p.Sil = 0 AND p.Aktif = 1
  AND p.ModulAdi NOT IN ('users', 'roles', 'logs', 'parameters')
  AND p.Aksiyon = 'read';

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
