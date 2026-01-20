-- =============================================
-- Eksik Kolon Düzeltmeleri
-- Bu migration tbl_damgavergisi ve tbl_fatura tablolarına
-- eksik kolonları ekler
-- =============================================
USE NbtProject;
GO

-- =============================================
-- 1. tbl_damgavergisi tablosuna BelgeNo kolonu ekle
-- =============================================
IF NOT EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('tbl_damgavergisi') AND name = 'BelgeNo')
BEGIN
    ALTER TABLE tbl_damgavergisi ADD BelgeNo NVARCHAR(100) NULL;
    PRINT 'tbl_damgavergisi tablosuna BelgeNo kolonu eklendi.';
END
ELSE
BEGIN
    PRINT 'tbl_damgavergisi tablosunda BelgeNo kolonu zaten mevcut.';
END
GO

-- =============================================
-- 2. tbl_fatura tablosuna Aciklama kolonu ekle
-- =============================================
IF NOT EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('tbl_fatura') AND name = 'Aciklama')
BEGIN
    ALTER TABLE tbl_fatura ADD Aciklama NVARCHAR(500) NULL;
    PRINT 'tbl_fatura tablosuna Aciklama kolonu eklendi.';
END
ELSE
BEGIN
    PRINT 'tbl_fatura tablosunda Aciklama kolonu zaten mevcut.';
END
GO

-- =============================================
-- 3. bck_tbl_damgavergisi tablosuna BelgeNo kolonu ekle (yedekleme uyumu)
-- =============================================
IF EXISTS (SELECT 1 FROM sys.tables WHERE name = 'bck_tbl_damgavergisi')
BEGIN
    IF NOT EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('bck_tbl_damgavergisi') AND name = 'BelgeNo')
    BEGIN
        ALTER TABLE bck_tbl_damgavergisi ADD BelgeNo NVARCHAR(100) NULL;
        PRINT 'bck_tbl_damgavergisi tablosuna BelgeNo kolonu eklendi.';
    END
END
GO

-- =============================================
-- 4. bck_tbl_fatura tablosuna Aciklama kolonu ekle (yedekleme uyumu)
-- =============================================
IF EXISTS (SELECT 1 FROM sys.tables WHERE name = 'bck_tbl_fatura')
BEGIN
    IF NOT EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('bck_tbl_fatura') AND name = 'Aciklama')
    BEGIN
        ALTER TABLE bck_tbl_fatura ADD Aciklama NVARCHAR(500) NULL;
        PRINT 'bck_tbl_fatura tablosuna Aciklama kolonu eklendi.';
    END
END
GO

PRINT '======================================';
PRINT 'Migration tamamlandi.';
PRINT '======================================';
GO
