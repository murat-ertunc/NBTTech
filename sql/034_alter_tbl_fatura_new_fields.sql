-- Fatura tablosuna yeni alanlar ekleme
-- FaturaNo, SupheliAlacak, Tevkifat, Takvim Hatırlatma alanları

-- FaturaNo alanı
IF NOT EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('dbo.tbl_fatura') AND name = 'FaturaNo')
BEGIN
    ALTER TABLE dbo.tbl_fatura ADD FaturaNo NVARCHAR(50) NULL;
END
GO

-- SupheliAlacak alanı (boolean)
IF NOT EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('dbo.tbl_fatura') AND name = 'SupheliAlacak')
BEGIN
    ALTER TABLE dbo.tbl_fatura ADD SupheliAlacak TINYINT DEFAULT 0;
END
GO

-- Tevkifat Alanları
IF NOT EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('dbo.tbl_fatura') AND name = 'TevkifatAktif')
BEGIN
    ALTER TABLE dbo.tbl_fatura ADD TevkifatAktif TINYINT DEFAULT 0;
END
GO

IF NOT EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('dbo.tbl_fatura') AND name = 'TevkifatOran1')
BEGIN
    ALTER TABLE dbo.tbl_fatura ADD TevkifatOran1 DECIMAL(5,2) NULL;
END
GO

IF NOT EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('dbo.tbl_fatura') AND name = 'TevkifatOran2')
BEGIN
    ALTER TABLE dbo.tbl_fatura ADD TevkifatOran2 DECIMAL(5,2) NULL;
END
GO

-- Takvim Hatırlatma Alanları
IF NOT EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('dbo.tbl_fatura') AND name = 'TakvimAktif')
BEGIN
    ALTER TABLE dbo.tbl_fatura ADD TakvimAktif TINYINT DEFAULT 0;
END
GO

IF NOT EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('dbo.tbl_fatura') AND name = 'TakvimSure')
BEGIN
    ALTER TABLE dbo.tbl_fatura ADD TakvimSure INT NULL;
END
GO

IF NOT EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('dbo.tbl_fatura') AND name = 'TakvimSureTipi')
BEGIN
    ALTER TABLE dbo.tbl_fatura ADD TakvimSureTipi NVARCHAR(10) NULL; -- gun, hafta, ay, yil
END
GO

-- Backup tablosuna da aynı alanları ekle
IF NOT EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('dbo.bck_tbl_fatura') AND name = 'FaturaNo')
BEGIN
    ALTER TABLE dbo.bck_tbl_fatura ADD FaturaNo NVARCHAR(50) NULL;
END
GO

IF NOT EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('dbo.bck_tbl_fatura') AND name = 'SupheliAlacak')
BEGIN
    ALTER TABLE dbo.bck_tbl_fatura ADD SupheliAlacak TINYINT NULL;
END
GO

IF NOT EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('dbo.bck_tbl_fatura') AND name = 'TevkifatAktif')
BEGIN
    ALTER TABLE dbo.bck_tbl_fatura ADD TevkifatAktif TINYINT NULL;
END
GO

IF NOT EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('dbo.bck_tbl_fatura') AND name = 'TevkifatOran1')
BEGIN
    ALTER TABLE dbo.bck_tbl_fatura ADD TevkifatOran1 DECIMAL(5,2) NULL;
END
GO

IF NOT EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('dbo.bck_tbl_fatura') AND name = 'TevkifatOran2')
BEGIN
    ALTER TABLE dbo.bck_tbl_fatura ADD TevkifatOran2 DECIMAL(5,2) NULL;
END
GO

IF NOT EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('dbo.bck_tbl_fatura') AND name = 'TakvimAktif')
BEGIN
    ALTER TABLE dbo.bck_tbl_fatura ADD TakvimAktif TINYINT NULL;
END
GO

IF NOT EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('dbo.bck_tbl_fatura') AND name = 'TakvimSure')
BEGIN
    ALTER TABLE dbo.bck_tbl_fatura ADD TakvimSure INT NULL;
END
GO

IF NOT EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('dbo.bck_tbl_fatura') AND name = 'TakvimSureTipi')
BEGIN
    ALTER TABLE dbo.bck_tbl_fatura ADD TakvimSureTipi NVARCHAR(10) NULL;
END
GO
