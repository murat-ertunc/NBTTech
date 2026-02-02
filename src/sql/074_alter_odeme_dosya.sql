-- =============================================
-- Ödeme dosya kolonlari
-- =============================================
IF NOT EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('tbl_odeme') AND name = 'DosyaAdi')
BEGIN
    ALTER TABLE tbl_odeme ADD DosyaAdi NVARCHAR(255) NULL;
    PRINT 'tbl_odeme tablosuna DosyaAdi kolonu eklendi.';
END
ELSE
BEGIN
    PRINT 'tbl_odeme tablosunda DosyaAdi kolonu zaten mevcut.';
END
GO

IF NOT EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('tbl_odeme') AND name = 'DosyaYolu')
BEGIN
    ALTER TABLE tbl_odeme ADD DosyaYolu NVARCHAR(500) NULL;
    PRINT 'tbl_odeme tablosuna DosyaYolu kolonu eklendi.';
END
ELSE
BEGIN
    PRINT 'tbl_odeme tablosunda DosyaYolu kolonu zaten mevcut.';
END
GO

-- =============================================
-- Yedek tablo uyumu
-- =============================================
IF EXISTS (SELECT 1 FROM sys.tables WHERE name = 'bck_tbl_odeme')
BEGIN
    IF NOT EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('bck_tbl_odeme') AND name = 'DosyaAdi')
    BEGIN
        ALTER TABLE bck_tbl_odeme ADD DosyaAdi NVARCHAR(255) NULL;
        PRINT 'bck_tbl_odeme tablosuna DosyaAdi kolonu eklendi.';
    END

    IF NOT EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('bck_tbl_odeme') AND name = 'DosyaYolu')
    BEGIN
        ALTER TABLE bck_tbl_odeme ADD DosyaYolu NVARCHAR(500) NULL;
        PRINT 'bck_tbl_odeme tablosuna DosyaYolu kolonu eklendi.';
    END
END
GO

PRINT '======================================';
PRINT 'Ödeme dosya kolonlari migration tamamlandi.';
PRINT '======================================';
GO
