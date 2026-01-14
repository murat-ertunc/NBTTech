-- Teminat tablosuna DosyaAdi ve DosyaYolu alanları ekleme
IF NOT EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('dbo.tbl_teminat') AND name = 'DosyaAdi')
BEGIN
    ALTER TABLE dbo.tbl_teminat ADD DosyaAdi NVARCHAR(255) NULL;
END
GO

IF NOT EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('dbo.tbl_teminat') AND name = 'DosyaYolu')
BEGIN
    ALTER TABLE dbo.tbl_teminat ADD DosyaYolu NVARCHAR(500) NULL;
END
GO
