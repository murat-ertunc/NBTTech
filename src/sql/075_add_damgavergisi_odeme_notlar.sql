-- Damga Vergisi: OdemeDurumu ve Notlar kolonlarini ekle
IF NOT EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('tbl_damgavergisi') AND name = 'OdemeDurumu')
BEGIN
    ALTER TABLE tbl_damgavergisi ADD OdemeDurumu NVARCHAR(50) NULL;
    PRINT 'tbl_damgavergisi tablosuna OdemeDurumu kolonu eklendi.';
END
ELSE
BEGIN
    PRINT 'tbl_damgavergisi tablosunda OdemeDurumu kolonu zaten mevcut.';
END
GO

IF NOT EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('tbl_damgavergisi') AND name = 'Notlar')
BEGIN
    ALTER TABLE tbl_damgavergisi ADD Notlar NVARCHAR(500) NULL;
    PRINT 'tbl_damgavergisi tablosuna Notlar kolonu eklendi.';
END
ELSE
BEGIN
    PRINT 'tbl_damgavergisi tablosunda Notlar kolonu zaten mevcut.';
END
GO

-- Backup tabloya da ekle
IF EXISTS (SELECT 1 FROM sys.tables WHERE name = 'bck_tbl_damgavergisi')
BEGIN
    IF NOT EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('bck_tbl_damgavergisi') AND name = 'OdemeDurumu')
    BEGIN
        ALTER TABLE bck_tbl_damgavergisi ADD OdemeDurumu NVARCHAR(50) NULL;
        PRINT 'bck_tbl_damgavergisi tablosuna OdemeDurumu kolonu eklendi.';
    END
    ELSE
    BEGIN
        PRINT 'bck_tbl_damgavergisi tablosunda OdemeDurumu kolonu zaten mevcut.';
    END

    IF NOT EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('bck_tbl_damgavergisi') AND name = 'Notlar')
    BEGIN
        ALTER TABLE bck_tbl_damgavergisi ADD Notlar NVARCHAR(500) NULL;
        PRINT 'bck_tbl_damgavergisi tablosuna Notlar kolonu eklendi.';
    END
    ELSE
    BEGIN
        PRINT 'bck_tbl_damgavergisi tablosunda Notlar kolonu zaten mevcut.';
    END
END
GO
