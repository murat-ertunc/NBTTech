-- Damga Vergisi tablosuna DosyaAdi ve DosyaYolu alanları ekleme
-- Bu, damga vergisi kayıtlarına PDF dosya ekleme özelliğini destekler

IF NOT EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('dbo.tbl_damgavergisi') AND name = 'DosyaAdi')
BEGIN
    ALTER TABLE dbo.tbl_damgavergisi ADD DosyaAdi NVARCHAR(255) NULL;
    PRINT 'tbl_damgavergisi tablosuna DosyaAdi kolonu eklendi.';
END
GO

IF NOT EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('dbo.tbl_damgavergisi') AND name = 'DosyaYolu')
BEGIN
    ALTER TABLE dbo.tbl_damgavergisi ADD DosyaYolu NVARCHAR(500) NULL;
    PRINT 'tbl_damgavergisi tablosuna DosyaYolu kolonu eklendi.';
END
GO

-- Backup tablosuna da ekle
IF NOT EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('dbo.bck_tbl_damgavergisi') AND name = 'DosyaAdi')
BEGIN
    ALTER TABLE dbo.bck_tbl_damgavergisi ADD DosyaAdi NVARCHAR(255) NULL;
    PRINT 'bck_tbl_damgavergisi tablosuna DosyaAdi kolonu eklendi.';
END
GO

IF NOT EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('dbo.bck_tbl_damgavergisi') AND name = 'DosyaYolu')
BEGIN
    ALTER TABLE dbo.bck_tbl_damgavergisi ADD DosyaYolu NVARCHAR(500) NULL;
    PRINT 'bck_tbl_damgavergisi tablosuna DosyaYolu kolonu eklendi.';
END
GO

PRINT '037_add_dosya_to_damgavergisi.sql tamamlandı.';
GO
