-- =============================================
-- PARAMETRE TABLOSU
-- Sistem genelinde kullanılan parametreler
-- =============================================

-- Parametre Ana Tablosu
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
GO

CREATE INDEX IX_tbl_parametre_Grup ON tbl_parametre(Grup);
CREATE INDEX IX_tbl_parametre_Kod ON tbl_parametre(Kod);
CREATE INDEX IX_tbl_parametre_Sil ON tbl_parametre(Sil);
GO

-- Parametre Backup Tablosu
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
GO

CREATE INDEX IX_bck_tbl_parametre_KaynakId ON bck_tbl_parametre(KaynakId);
GO
