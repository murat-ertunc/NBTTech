-- =============================================
-- PARAMETRE TABLOSU
-- Sistem genelinde kullanılan parametreler
-- =============================================

-- Parametre Ana Tablosu
IF OBJECT_ID('tbl_parametre', 'U') IS NULL
BEGIN
    CREATE TABLE tbl_parametre (
        Id INT IDENTITY(1,1) PRIMARY KEY,
        Guid UNIQUEIDENTIFIER NOT NULL DEFAULT NEWID(),
        EklemeZamani DATETIME2(0) NOT NULL DEFAULT SYSUTCDATETIME(),
        EkleyenUserId INT NULL,
        DegisiklikZamani DATETIME2(0) NOT NULL DEFAULT SYSUTCDATETIME(),
        DegistirenUserId INT NULL,
        Sil BIT NOT NULL DEFAULT 0,
        
        -- Parametre Bilgileri
        Grup NVARCHAR(50) NOT NULL,           -- doviz, durum, genel
        Kod NVARCHAR(50) NOT NULL,            -- TRY, USD, bekliyor, aktif, pagination_default
        Deger NVARCHAR(255) NULL,             -- Değer (badge rengi, para birimi simgesi vb)
        Etiket NVARCHAR(100) NULL,            -- Görünen isim (Türk Lirası, Bekliyor vb)
        Sira INT DEFAULT 0,                   -- Sıralama
        Aktif BIT DEFAULT 1,                  -- Aktif mi
        Varsayilan BIT DEFAULT 0              -- Varsayılan mı (doviz için)
    );
END
GO

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_tbl_parametre_Grup' AND object_id = OBJECT_ID('tbl_parametre'))
    CREATE INDEX IX_tbl_parametre_Grup ON tbl_parametre(Grup);
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_tbl_parametre_Kod' AND object_id = OBJECT_ID('tbl_parametre'))
    CREATE INDEX IX_tbl_parametre_Kod ON tbl_parametre(Kod);
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_tbl_parametre_Sil' AND object_id = OBJECT_ID('tbl_parametre'))
    CREATE INDEX IX_tbl_parametre_Sil ON tbl_parametre(Sil);
GO

-- Parametre Backup Tablosu
IF OBJECT_ID('bck_tbl_parametre', 'U') IS NULL
BEGIN
    CREATE TABLE bck_tbl_parametre (
        Id INT IDENTITY(1,1) PRIMARY KEY,
        KaynakId INT NOT NULL,
        BackupZamani DATETIME2(0) NOT NULL DEFAULT SYSUTCDATETIME(),
        BackupUserId INT NULL,
        
        -- Orijinal tablo verileri
        Guid UNIQUEIDENTIFIER NULL,
        EklemeZamani DATETIME2(0) NULL,
        EkleyenUserId INT NULL,
        DegisiklikZamani DATETIME2(0) NULL,
        DegistirenUserId INT NULL,
        Sil BIT NULL,
        
        -- Parametre Bilgileri
        Grup NVARCHAR(50) NULL,
        Kod NVARCHAR(50) NULL,
        Deger NVARCHAR(255) NULL,
        Etiket NVARCHAR(100) NULL,
        Sira INT NULL,
        Aktif BIT NULL,
        Varsayilan BIT NULL
    );
END
GO

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_bck_tbl_parametre_KaynakId' AND object_id = OBJECT_ID('bck_tbl_parametre'))
    CREATE INDEX IX_bck_tbl_parametre_KaynakId ON bck_tbl_parametre(KaynakId);
GO
