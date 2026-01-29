-- bck_tbl_parametre tablosuna Pasif kolonu ekle (yedekleme uyumu)
IF EXISTS (SELECT 1 FROM sys.tables WHERE name = 'bck_tbl_parametre')
BEGIN
    IF NOT EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('bck_tbl_parametre') AND name = 'Pasif')
    BEGIN
        ALTER TABLE bck_tbl_parametre ADD Pasif BIT NULL;
        PRINT 'bck_tbl_parametre tablosuna Pasif kolonu eklendi.';
    END
    ELSE
    BEGIN
        PRINT 'bck_tbl_parametre tablosunda Pasif kolonu zaten mevcut.';
    END
END
GO
