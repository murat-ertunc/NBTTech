-- 038_alter_fatura_remove_aciklama.sql
-- Fatura tablosundan Aciklama kolonunu kaldir

-- 1. tbl_fatura'dan Aciklama kolonunu kaldir
IF EXISTS (
    SELECT 1
    FROM sys.columns
    WHERE object_id = OBJECT_ID('tbl_fatura')
    AND name = 'Aciklama'
)
BEGIN
    ALTER TABLE tbl_fatura DROP COLUMN Aciklama;
    PRINT 'tbl_fatura.Aciklama kolonu kaldirildi.';
END
ELSE
BEGIN
    PRINT 'tbl_fatura.Aciklama kolonu zaten yok.';
END
GO

-- 2. bck_tbl_fatura'dan Aciklama kolonunu kaldir
IF EXISTS (
    SELECT 1
    FROM sys.columns
    WHERE object_id = OBJECT_ID('bck_tbl_fatura')
    AND name = 'Aciklama'
)
BEGIN
    ALTER TABLE bck_tbl_fatura DROP COLUMN Aciklama;
    PRINT 'bck_tbl_fatura.Aciklama kolonu kaldirildi.';
END
ELSE
BEGIN
    PRINT 'bck_tbl_fatura.Aciklama kolonu zaten yok.';
END
GO

PRINT '038_alter_fatura_remove_aciklama.sql basariyla tamamlandi.';
GO
