-- =============================================
-- Read All Permission Seed Data
-- =============================================
-- Moduller icin read_all permission'lari
-- users.read_all: Tum kullanicilari gorebilir
-- customers.read_all: Tum musterileri gorebilir

DECLARE @SeedUserId INT = 1;
DECLARE @Simdi DATETIME2(0) = SYSUTCDATETIME();

-- =============================================
-- MODUL: users (read_all - Tum Kullanicilari Gorebilir)
-- =============================================
IF NOT EXISTS (SELECT 1 FROM tnm_permission WHERE PermissionKodu = 'users.read_all' AND Sil = 0)
BEGIN
    INSERT INTO tnm_permission (Guid, EklemeZamani, EkleyenUserId, DegisiklikZamani, DegistirenUserId, Sil, PermissionKodu, ModulAdi, Aksiyon, Aciklama, Aktif)
    VALUES (NEWID(), @Simdi, @SeedUserId, @Simdi, @SeedUserId, 0, 'users.read_all', 'users', 'read_all', 'Tum kullanicilari goruntuleme yetkisi (read ile birlikte)', 1);
    PRINT 'users.read_all permission eklendi.';
END
ELSE
BEGIN
    PRINT 'users.read_all permission zaten mevcut.';
END

-- =============================================
-- MODUL: customers (read_all - Tum Musterileri Gorebilir)
-- =============================================
IF NOT EXISTS (SELECT 1 FROM tnm_permission WHERE PermissionKodu = 'customers.read_all' AND Sil = 0)
BEGIN
    INSERT INTO tnm_permission (Guid, EklemeZamani, EkleyenUserId, DegisiklikZamani, DegistirenUserId, Sil, PermissionKodu, ModulAdi, Aksiyon, Aciklama, Aktif)
    VALUES (NEWID(), @Simdi, @SeedUserId, @Simdi, @SeedUserId, 0, 'customers.read_all', 'customers', 'read_all', 'Tum musterileri goruntuleme yetkisi (read ile birlikte)', 1);
    PRINT 'customers.read_all permission eklendi.';
END
ELSE
BEGIN
    PRINT 'customers.read_all permission zaten mevcut.';
END

-- =============================================
-- MODUL: invoices (read_all - Tum Faturalari Gorebilir)
-- =============================================
IF NOT EXISTS (SELECT 1 FROM tnm_permission WHERE PermissionKodu = 'invoices.read_all' AND Sil = 0)
BEGIN
    INSERT INTO tnm_permission (Guid, EklemeZamani, EkleyenUserId, DegisiklikZamani, DegistirenUserId, Sil, PermissionKodu, ModulAdi, Aksiyon, Aciklama, Aktif)
    VALUES (NEWID(), @Simdi, @SeedUserId, @Simdi, @SeedUserId, 0, 'invoices.read_all', 'invoices', 'read_all', 'Tum faturalari goruntuleme yetkisi (read ile birlikte)', 1);
    PRINT 'invoices.read_all permission eklendi.';
END

-- =============================================
-- MODUL: payments (read_all - Tum Odemeleri Gorebilir)
-- =============================================
IF NOT EXISTS (SELECT 1 FROM tnm_permission WHERE PermissionKodu = 'payments.read_all' AND Sil = 0)
BEGIN
    INSERT INTO tnm_permission (Guid, EklemeZamani, EkleyenUserId, DegisiklikZamani, DegistirenUserId, Sil, PermissionKodu, ModulAdi, Aksiyon, Aciklama, Aktif)
    VALUES (NEWID(), @Simdi, @SeedUserId, @Simdi, @SeedUserId, 0, 'payments.read_all', 'payments', 'read_all', 'Tum odemeleri goruntuleme yetkisi (read ile birlikte)', 1);
    PRINT 'payments.read_all permission eklendi.';
END

-- =============================================
-- MODUL: projects (read_all - Tum Projeleri Gorebilir)
-- =============================================
IF NOT EXISTS (SELECT 1 FROM tnm_permission WHERE PermissionKodu = 'projects.read_all' AND Sil = 0)
BEGIN
    INSERT INTO tnm_permission (Guid, EklemeZamani, EkleyenUserId, DegisiklikZamani, DegistirenUserId, Sil, PermissionKodu, ModulAdi, Aksiyon, Aciklama, Aktif)
    VALUES (NEWID(), @Simdi, @SeedUserId, @Simdi, @SeedUserId, 0, 'projects.read_all', 'projects', 'read_all', 'Tum projeleri goruntuleme yetkisi (read ile birlikte)', 1);
    PRINT 'projects.read_all permission eklendi.';
END

GO
