-- tnm_user tablosunu oluşturur.
-- Şema başlangıç kurulumunu içerir.

IF OBJECT_ID('tnm_user', 'U') IS NULL
BEGIN
    CREATE TABLE tnm_user (
        Id INT IDENTITY(1,1) PRIMARY KEY,
        Guid UNIQUEIDENTIFIER NOT NULL DEFAULT NEWID(),
        EklemeZamani DATETIME2(0) NOT NULL DEFAULT SYSUTCDATETIME(),
        EkleyenUserId INT NULL,
        DegisiklikZamani DATETIME2(0) NOT NULL DEFAULT SYSUTCDATETIME(),
        DegistirenUserId INT NULL,
        Sil BIT NOT NULL DEFAULT 0,
        KullaniciAdi NVARCHAR(50) NOT NULL UNIQUE,
        Parola NVARCHAR(255) NOT NULL,
        AdSoyad NVARCHAR(150) NOT NULL,
        Aktif BIT NOT NULL DEFAULT 1,
        Rol NVARCHAR(50) NOT NULL DEFAULT 'user'
    );
END

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_tnm_user_KullaniciAdi' AND object_id = OBJECT_ID('tnm_user'))
    CREATE INDEX IX_tnm_user_KullaniciAdi ON tnm_user (KullaniciAdi);
