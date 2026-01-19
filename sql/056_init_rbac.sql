-- =============================================
-- RBAC Sistem Kurulumu - Tum Dosyalari Calistir
-- =============================================
-- Bu dosya sqlcmd ile calistirilmalidir:
-- sqlcmd -S localhost -U sa -P <password> -d NbtProject -i 056_init_rbac.sql

USE NbtProject;
GO

PRINT '======================================';
PRINT 'RBAC Sistemi Kuruluyor...';
PRINT '======================================';

-- Tablolari olustur
:r 050_tnm_rol.sql
:r 051_tnm_permission.sql
:r 052_tnm_rol_permission.sql
:r 053_tnm_user_rol.sql

-- Seed data ekle
:r 054_seed_permissions.sql
:r 055_seed_roles.sql

PRINT '======================================';
PRINT 'RBAC Sistemi Kurulumu Tamamlandi!';
PRINT '======================================';

-- Ozet rapor
SELECT 'Rol Sayisi' AS Metrik, COUNT(*) AS Deger FROM tnm_rol WHERE Sil = 0
UNION ALL
SELECT 'Permission Sayisi', COUNT(*) FROM tnm_permission WHERE Sil = 0
UNION ALL
SELECT 'Rol-Permission Atamasi', COUNT(*) FROM tnm_rol_permission WHERE Sil = 0
UNION ALL
SELECT 'User-Rol Atamasi', COUNT(*) FROM tnm_user_rol WHERE Sil = 0;
GO
