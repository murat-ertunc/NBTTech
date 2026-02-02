-- Ödeme: OdemeTuru, BankaHesap, Notlar kolonlarini ekle
IF NOT EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('tbl_odeme') AND name = 'OdemeTuru')
BEGIN
    ALTER TABLE tbl_odeme ADD OdemeTuru NVARCHAR(50) NULL;
    PRINT 'tbl_odeme tablosuna OdemeTuru kolonu eklendi.';
END
ELSE
BEGIN
    PRINT 'tbl_odeme tablosunda OdemeTuru kolonu zaten mevcut.';
END
GO

IF NOT EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('tbl_odeme') AND name = 'BankaHesap')
BEGIN
    ALTER TABLE tbl_odeme ADD BankaHesap NVARCHAR(150) NULL;
    PRINT 'tbl_odeme tablosuna BankaHesap kolonu eklendi.';
END
ELSE
BEGIN
    PRINT 'tbl_odeme tablosunda BankaHesap kolonu zaten mevcut.';
END
GO

IF NOT EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('tbl_odeme') AND name = 'Notlar')
BEGIN
    ALTER TABLE tbl_odeme ADD Notlar NVARCHAR(500) NULL;
    PRINT 'tbl_odeme tablosuna Notlar kolonu eklendi.';
END
ELSE
BEGIN
    PRINT 'tbl_odeme tablosunda Notlar kolonu zaten mevcut.';
END
GO

-- Backup tabloya da ekle
IF EXISTS (SELECT 1 FROM sys.tables WHERE name = 'bck_tbl_odeme')
BEGIN
    IF NOT EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('bck_tbl_odeme') AND name = 'OdemeTuru')
    BEGIN
        ALTER TABLE bck_tbl_odeme ADD OdemeTuru NVARCHAR(50) NULL;
        PRINT 'bck_tbl_odeme tablosuna OdemeTuru kolonu eklendi.';
    END

    IF NOT EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('bck_tbl_odeme') AND name = 'BankaHesap')
    BEGIN
        ALTER TABLE bck_tbl_odeme ADD BankaHesap NVARCHAR(150) NULL;
        PRINT 'bck_tbl_odeme tablosuna BankaHesap kolonu eklendi.';
    END

    IF NOT EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('bck_tbl_odeme') AND name = 'Notlar')
    BEGIN
        ALTER TABLE bck_tbl_odeme ADD Notlar NVARCHAR(500) NULL;
        PRINT 'bck_tbl_odeme tablosuna Notlar kolonu eklendi.';
    END
END
GO
