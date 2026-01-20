-- =============================================================================
-- TEST KULLANICILARI SEED
-- =============================================================================
-- Bu dosya sadece development/test ortamlarında kullanılır.
-- Production'da bu kullanıcıları OLUŞTURMAYIN!
--
-- Kullanıcılar:
--   1. test_superadmin  - Tüm yetkilere sahip
--   2. test_limited     - Sadece customers ve projects read yetkisi
--   3. test_noperm      - Hiçbir permission yok (sadece login)
--
-- Şifreler: 
--   test_superadmin: Test123!
--   test_limited: Test123!
--   test_noperm: Test123!
--
-- Şifre hash'i PHP password_hash('Test123!', PASSWORD_DEFAULT) ile üretildi
-- =============================================================================

-- Test kullanıcıları için rol
DECLARE @TestRolId INT;
DECLARE @LimitedRolId INT;
DECLARE @NopermRolId INT;

-- 1. Test Superadmin Rolü (tüm yetkiler)
IF NOT EXISTS (SELECT 1 FROM tnm_rol WHERE RolKodu = 'test_superadmin' AND Sil = 0)
BEGIN
    INSERT INTO tnm_rol (Guid, RolKodu, RolAdi, Aciklama, EklemeZamani, EkleyenUserId, DegisiklikZamani, DegistirenUserId, Sil)
    VALUES (NEWID(), 'test_superadmin', 'Test Superadmin', 'Test için tüm yetkilere sahip rol', GETDATE(), 1, GETDATE(), 1, 0);
END
SELECT @TestRolId = Id FROM tnm_rol WHERE RolKodu = 'test_superadmin' AND Sil = 0;

-- Superadmin rolüne tüm permission'ları ata
INSERT INTO tnm_rol_permission (Guid, RolId, PermissionId, EklemeZamani, EkleyenUserId, DegisiklikZamani, DegistirenUserId, Sil)
SELECT NEWID(), @TestRolId, p.Id, GETDATE(), 1, GETDATE(), 1, 0
FROM tnm_permission p
WHERE p.Sil = 0
AND NOT EXISTS (
    SELECT 1 FROM tnm_rol_permission rp 
    WHERE rp.RolId = @TestRolId AND rp.PermissionId = p.Id AND rp.Sil = 0
);

-- 2. Test Limited Rolü (sadece customers ve projects read)
IF NOT EXISTS (SELECT 1 FROM tnm_rol WHERE RolKodu = 'test_limited' AND Sil = 0)
BEGIN
    INSERT INTO tnm_rol (Guid, RolKodu, RolAdi, Aciklama, EklemeZamani, EkleyenUserId, DegisiklikZamani, DegistirenUserId, Sil)
    VALUES (NEWID(), 'test_limited', 'Test Limited', 'Test için sınırlı yetkili rol', GETDATE(), 1, GETDATE(), 1, 0);
END
SELECT @LimitedRolId = Id FROM tnm_rol WHERE RolKodu = 'test_limited' AND Sil = 0;

-- Limited rolüne sadece customers.read ve projects.read ata
INSERT INTO tnm_rol_permission (Guid, RolId, PermissionId, EklemeZamani, EkleyenUserId, DegisiklikZamani, DegistirenUserId, Sil)
SELECT NEWID(), @LimitedRolId, p.Id, GETDATE(), 1, GETDATE(), 1, 0
FROM tnm_permission p
WHERE p.Sil = 0 AND p.Kod IN ('customers.read', 'projects.read', 'dashboard.read')
AND NOT EXISTS (
    SELECT 1 FROM tnm_rol_permission rp 
    WHERE rp.RolId = @LimitedRolId AND rp.PermissionId = p.Id AND rp.Sil = 0
);

-- 3. Test NoPerm Rolü (hiçbir permission yok)
IF NOT EXISTS (SELECT 1 FROM tnm_rol WHERE RolKodu = 'test_noperm' AND Sil = 0)
BEGIN
    INSERT INTO tnm_rol (Guid, RolKodu, RolAdi, Aciklama, EklemeZamani, EkleyenUserId, DegisiklikZamani, DegistirenUserId, Sil)
    VALUES (NEWID(), 'test_noperm', 'Test No Permission', 'Test için yetkisiz rol', GETDATE(), 1, GETDATE(), 1, 0);
END
SELECT @NopermRolId = Id FROM tnm_rol WHERE RolKodu = 'test_noperm' AND Sil = 0;

-- =============================================================================
-- KULLANICILAR
-- =============================================================================
-- Şifre: Test123! (bcrypt hash)
DECLARE @PasswordHash VARCHAR(255) = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

-- Test Superadmin Kullanıcısı
IF NOT EXISTS (SELECT 1 FROM tnm_user WHERE KullaniciAdi = 'test_superadmin' AND Sil = 0)
BEGIN
    INSERT INTO tnm_user (Guid, KullaniciAdi, Parola, AdSoyad, Email, Rol, Aktif, EklemeZamani, EkleyenUserId, DegisiklikZamani, DegistirenUserId, Sil)
    VALUES (NEWID(), 'test_superadmin', @PasswordHash, 'Test Superadmin', 'test.superadmin@test.local', 'superadmin', 1, GETDATE(), 1, GETDATE(), 1, 0);
END

-- Test Superadmin'e rol ata
DECLARE @TestSuperadminUserId INT;
SELECT @TestSuperadminUserId = Id FROM tnm_user WHERE KullaniciAdi = 'test_superadmin' AND Sil = 0;

IF @TestSuperadminUserId IS NOT NULL AND NOT EXISTS (
    SELECT 1 FROM tnm_user_rol WHERE UserId = @TestSuperadminUserId AND RolId = @TestRolId AND Sil = 0
)
BEGIN
    INSERT INTO tnm_user_rol (Guid, UserId, RolId, EklemeZamani, EkleyenUserId, DegisiklikZamani, DegistirenUserId, Sil)
    VALUES (NEWID(), @TestSuperadminUserId, @TestRolId, GETDATE(), 1, GETDATE(), 1, 0);
END

-- Test Limited Kullanıcısı
IF NOT EXISTS (SELECT 1 FROM tnm_user WHERE KullaniciAdi = 'test_limited' AND Sil = 0)
BEGIN
    INSERT INTO tnm_user (Guid, KullaniciAdi, Parola, AdSoyad, Email, Rol, Aktif, EklemeZamani, EkleyenUserId, DegisiklikZamani, DegistirenUserId, Sil)
    VALUES (NEWID(), 'test_limited', @PasswordHash, 'Test Limited', 'test.limited@test.local', 'user', 1, GETDATE(), 1, GETDATE(), 1, 0);
END

-- Test Limited'e rol ata
DECLARE @TestLimitedUserId INT;
SELECT @TestLimitedUserId = Id FROM tnm_user WHERE KullaniciAdi = 'test_limited' AND Sil = 0;

IF @TestLimitedUserId IS NOT NULL AND NOT EXISTS (
    SELECT 1 FROM tnm_user_rol WHERE UserId = @TestLimitedUserId AND RolId = @LimitedRolId AND Sil = 0
)
BEGIN
    INSERT INTO tnm_user_rol (Guid, UserId, RolId, EklemeZamani, EkleyenUserId, DegisiklikZamani, DegistirenUserId, Sil)
    VALUES (NEWID(), @TestLimitedUserId, @LimitedRolId, GETDATE(), 1, GETDATE(), 1, 0);
END

-- Test NoPerm Kullanıcısı
IF NOT EXISTS (SELECT 1 FROM tnm_user WHERE KullaniciAdi = 'test_noperm' AND Sil = 0)
BEGIN
    INSERT INTO tnm_user (Guid, KullaniciAdi, Parola, AdSoyad, Email, Rol, Aktif, EklemeZamani, EkleyenUserId, DegisiklikZamani, DegistirenUserId, Sil)
    VALUES (NEWID(), 'test_noperm', @PasswordHash, 'Test No Permission', 'test.noperm@test.local', 'user', 1, GETDATE(), 1, GETDATE(), 1, 0);
END

-- Test NoPerm'e rol ata
DECLARE @TestNopermUserId INT;
SELECT @TestNopermUserId = Id FROM tnm_user WHERE KullaniciAdi = 'test_noperm' AND Sil = 0;

IF @TestNopermUserId IS NOT NULL AND NOT EXISTS (
    SELECT 1 FROM tnm_user_rol WHERE UserId = @TestNopermUserId AND RolId = @NopermRolId AND Sil = 0
)
BEGIN
    INSERT INTO tnm_user_rol (Guid, UserId, RolId, EklemeZamani, EkleyenUserId, DegisiklikZamani, DegistirenUserId, Sil)
    VALUES (NEWID(), @TestNopermUserId, @NopermRolId, GETDATE(), 1, GETDATE(), 1, 0);
END

-- =============================================================================
-- SONUÇ RAPORU
-- =============================================================================
SELECT 
    'Test Kullanıcıları Oluşturuldu' AS Sonuc,
    (SELECT COUNT(*) FROM tnm_user WHERE KullaniciAdi LIKE 'test_%' AND Sil = 0) AS KullaniciSayisi,
    (SELECT COUNT(*) FROM tnm_rol WHERE RolKodu LIKE 'test_%' AND Sil = 0) AS RolSayisi;

-- Kullanıcı listesi
SELECT 
    u.KullaniciAdi,
    u.AdSoyad,
    r.RolKodu,
    r.RolAdi,
    (SELECT COUNT(*) FROM tnm_rol_permission rp WHERE rp.RolId = r.Id AND rp.Sil = 0) AS PermissionSayisi
FROM tnm_user u
LEFT JOIN tnm_user_rol ur ON u.Id = ur.UserId AND ur.Sil = 0
LEFT JOIN tnm_rol r ON ur.RolId = r.Id AND r.Sil = 0
WHERE u.KullaniciAdi LIKE 'test_%' AND u.Sil = 0;
