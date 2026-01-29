-- Teminat: Notlar kolonlarini ekle
IF NOT EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('tbl_teminat') AND name = 'Notlar')
BEGIN
    ALTER TABLE tbl_teminat ADD Notlar NVARCHAR(MAX) NULL;
    PRINT 'tbl_teminat tablosuna Notlar kolonu eklendi.';
END
ELSE
BEGIN
    PRINT 'tbl_teminat tablosunda Notlar kolonu zaten mevcut.';
END
GO

IF NOT EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('bck_tbl_teminat') AND name = 'Notlar')
BEGIN
    ALTER TABLE bck_tbl_teminat ADD Notlar NVARCHAR(MAX) NULL;
    PRINT 'bck_tbl_teminat tablosuna Notlar kolonu eklendi.';
END
ELSE
BEGIN
    PRINT 'bck_tbl_teminat tablosunda Notlar kolonu zaten mevcut.';
END
GO
