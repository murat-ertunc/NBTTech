-- Müşteri tablosuna yeni alanlar ekleniyor
-- MusteriKodu, VergiDairesi, VergiNo, Adres, Telefon, Faks, MersisNo, Web

-- Önce kolonların var olup olmadığını kontrol et ve yoksa ekle
IF NOT EXISTS (SELECT * FROM sys.columns WHERE object_id = OBJECT_ID('tbl_musteri') AND name = 'MusteriKodu')
BEGIN
    ALTER TABLE tbl_musteri ADD MusteriKodu NVARCHAR(50) NULL;
END
GO

IF NOT EXISTS (SELECT * FROM sys.columns WHERE object_id = OBJECT_ID('tbl_musteri') AND name = 'VergiDairesi')
BEGIN
    ALTER TABLE tbl_musteri ADD VergiDairesi NVARCHAR(100) NULL;
END
GO

IF NOT EXISTS (SELECT * FROM sys.columns WHERE object_id = OBJECT_ID('tbl_musteri') AND name = 'VergiNo')
BEGIN
    ALTER TABLE tbl_musteri ADD VergiNo NVARCHAR(20) NULL;
END
GO

IF NOT EXISTS (SELECT * FROM sys.columns WHERE object_id = OBJECT_ID('tbl_musteri') AND name = 'Adres')
BEGIN
    ALTER TABLE tbl_musteri ADD Adres NVARCHAR(500) NULL;
END
GO

IF NOT EXISTS (SELECT * FROM sys.columns WHERE object_id = OBJECT_ID('tbl_musteri') AND name = 'Telefon')
BEGIN
    ALTER TABLE tbl_musteri ADD Telefon NVARCHAR(30) NULL;
END
GO

IF NOT EXISTS (SELECT * FROM sys.columns WHERE object_id = OBJECT_ID('tbl_musteri') AND name = 'Faks')
BEGIN
    ALTER TABLE tbl_musteri ADD Faks NVARCHAR(30) NULL;
END
GO

IF NOT EXISTS (SELECT * FROM sys.columns WHERE object_id = OBJECT_ID('tbl_musteri') AND name = 'MersisNo')
BEGIN
    ALTER TABLE tbl_musteri ADD MersisNo NVARCHAR(20) NULL;
END
GO

IF NOT EXISTS (SELECT * FROM sys.columns WHERE object_id = OBJECT_ID('tbl_musteri') AND name = 'Web')
BEGIN
    ALTER TABLE tbl_musteri ADD Web NVARCHAR(255) NULL;
END
GO

-- Backup tablosuna da aynı alanları ekle
IF NOT EXISTS (SELECT * FROM sys.columns WHERE object_id = OBJECT_ID('bck_tbl_musteri') AND name = 'MusteriKodu')
BEGIN
    ALTER TABLE bck_tbl_musteri ADD MusteriKodu NVARCHAR(50) NULL;
END
GO

IF NOT EXISTS (SELECT * FROM sys.columns WHERE object_id = OBJECT_ID('bck_tbl_musteri') AND name = 'VergiDairesi')
BEGIN
    ALTER TABLE bck_tbl_musteri ADD VergiDairesi NVARCHAR(100) NULL;
END
GO

IF NOT EXISTS (SELECT * FROM sys.columns WHERE object_id = OBJECT_ID('bck_tbl_musteri') AND name = 'VergiNo')
BEGIN
    ALTER TABLE bck_tbl_musteri ADD VergiNo NVARCHAR(20) NULL;
END
GO

IF NOT EXISTS (SELECT * FROM sys.columns WHERE object_id = OBJECT_ID('bck_tbl_musteri') AND name = 'Adres')
BEGIN
    ALTER TABLE bck_tbl_musteri ADD Adres NVARCHAR(500) NULL;
END
GO

IF NOT EXISTS (SELECT * FROM sys.columns WHERE object_id = OBJECT_ID('bck_tbl_musteri') AND name = 'Telefon')
BEGIN
    ALTER TABLE bck_tbl_musteri ADD Telefon NVARCHAR(30) NULL;
END
GO

IF NOT EXISTS (SELECT * FROM sys.columns WHERE object_id = OBJECT_ID('bck_tbl_musteri') AND name = 'Faks')
BEGIN
    ALTER TABLE bck_tbl_musteri ADD Faks NVARCHAR(30) NULL;
END
GO

IF NOT EXISTS (SELECT * FROM sys.columns WHERE object_id = OBJECT_ID('bck_tbl_musteri') AND name = 'MersisNo')
BEGIN
    ALTER TABLE bck_tbl_musteri ADD MersisNo NVARCHAR(20) NULL;
END
GO

IF NOT EXISTS (SELECT * FROM sys.columns WHERE object_id = OBJECT_ID('bck_tbl_musteri') AND name = 'Web')
BEGIN
    ALTER TABLE bck_tbl_musteri ADD Web NVARCHAR(255) NULL;
END
GO

-- İndeksler
IF NOT EXISTS (SELECT * FROM sys.indexes WHERE object_id = OBJECT_ID('tbl_musteri') AND name = 'IX_tbl_musteri_MusteriKodu')
BEGIN
    CREATE INDEX IX_tbl_musteri_MusteriKodu ON tbl_musteri (MusteriKodu);
END
GO

IF NOT EXISTS (SELECT * FROM sys.indexes WHERE object_id = OBJECT_ID('tbl_musteri') AND name = 'IX_tbl_musteri_VergiNo')
BEGIN
    CREATE INDEX IX_tbl_musteri_VergiNo ON tbl_musteri (VergiNo);
END
GO

PRINT 'Müşteri tablosu yeni alanlarla güncellendi.';
