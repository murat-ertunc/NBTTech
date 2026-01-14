-- tbl_dosya tablosuna FaturaId kolonu ekleme
-- Bu, faturalara dosya ekleme özelliğini destekler

-- FaturaId kolonu ekle
IF NOT EXISTS (SELECT * FROM sys.columns WHERE object_id = OBJECT_ID('tbl_dosya') AND name = 'FaturaId')
BEGIN
    ALTER TABLE tbl_dosya ADD FaturaId INT NULL;
    CREATE INDEX IX_tbl_dosya_FaturaId ON tbl_dosya (FaturaId);
    PRINT 'tbl_dosya tablosuna FaturaId kolonu eklendi.';
END
GO

-- Backup tablosuna da ekle
IF NOT EXISTS (SELECT * FROM sys.columns WHERE object_id = OBJECT_ID('bck_tbl_dosya') AND name = 'FaturaId')
BEGIN
    ALTER TABLE bck_tbl_dosya ADD FaturaId INT NULL;
    PRINT 'bck_tbl_dosya tablosuna FaturaId kolonu eklendi.';
END
GO

-- OlusturmaZamani kolonu yoksa ekle (eski tablo yapısı için)
IF NOT EXISTS (SELECT * FROM sys.columns WHERE object_id = OBJECT_ID('tbl_dosya') AND name = 'OlusturmaZamani')
BEGIN
    ALTER TABLE tbl_dosya ADD OlusturmaZamani DATETIME2(0) NOT NULL DEFAULT SYSUTCDATETIME();
    PRINT 'tbl_dosya tablosuna OlusturmaZamani kolonu eklendi.';
END
GO

PRINT '036_add_faturaid_to_dosya.sql tamamlandı.';
GO
