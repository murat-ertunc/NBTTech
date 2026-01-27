-- =============================================
-- Müşteri İl/İlçe Kolonları Ekleme
-- =============================================
IF NOT EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('tbl_musteri') AND name = 'Il')
BEGIN
    ALTER TABLE tbl_musteri ADD Il NVARCHAR(50) NULL;
    PRINT 'tbl_musteri tablosuna Il kolonu eklendi.';
END
ELSE
BEGIN
    PRINT 'tbl_musteri tablosunda Il kolonu zaten mevcut.';
END
GO

IF NOT EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('tbl_musteri') AND name = 'Ilce')
BEGIN
    ALTER TABLE tbl_musteri ADD Ilce NVARCHAR(50) NULL;
    PRINT 'tbl_musteri tablosuna Ilce kolonu eklendi.';
END
ELSE
BEGIN
    PRINT 'tbl_musteri tablosunda Ilce kolonu zaten mevcut.';
END
GO

-- Backup tablo uyumu
IF EXISTS (SELECT 1 FROM sys.tables WHERE name = 'bck_tbl_musteri')
BEGIN
    IF NOT EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('bck_tbl_musteri') AND name = 'Il')
    BEGIN
        ALTER TABLE bck_tbl_musteri ADD Il NVARCHAR(50) NULL;
        PRINT 'bck_tbl_musteri tablosuna Il kolonu eklendi.';
    END
    IF NOT EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('bck_tbl_musteri') AND name = 'Ilce')
    BEGIN
        ALTER TABLE bck_tbl_musteri ADD Ilce NVARCHAR(50) NULL;
        PRINT 'bck_tbl_musteri tablosuna Ilce kolonu eklendi.';
    END
END
GO

PRINT '======================================';
PRINT 'Musteri Il/Ilce migration tamamlandi.';
PRINT '======================================';
GO
