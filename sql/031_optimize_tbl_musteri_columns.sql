-- Müşteri tablosu kolon boyutlarını optimize et
-- MusteriKodu: 5, Unvan: 150, VergiDairesi: 50, VergiNo: 11, MersisNo: 16
-- Telefon: 20, Faks: 20, Web: 150, Adres: 300, Aciklama: 500

-- Önce tüm ilgili index'leri kaldır
IF EXISTS (SELECT * FROM sys.indexes WHERE name = 'IX_tbl_musteri_Unvan' AND object_id = OBJECT_ID('tbl_musteri'))
    DROP INDEX IX_tbl_musteri_Unvan ON tbl_musteri;
GO

IF EXISTS (SELECT * FROM sys.indexes WHERE name = 'IX_tbl_musteri_MusteriKodu' AND object_id = OBJECT_ID('tbl_musteri'))
    DROP INDEX IX_tbl_musteri_MusteriKodu ON tbl_musteri;
GO

IF EXISTS (SELECT * FROM sys.indexes WHERE name = 'IX_tbl_musteri_VergiNo' AND object_id = OBJECT_ID('tbl_musteri'))
    DROP INDEX IX_tbl_musteri_VergiNo ON tbl_musteri;
GO

-- tbl_musteri kolon optimizasyonları
-- MusteriKodu: 50 -> 5
IF EXISTS (SELECT * FROM sys.columns WHERE object_id = OBJECT_ID('tbl_musteri') AND name = 'MusteriKodu')
    ALTER TABLE tbl_musteri ALTER COLUMN MusteriKodu NVARCHAR(5) NULL;
GO

-- Unvan: 255 -> 150
IF EXISTS (SELECT * FROM sys.columns WHERE object_id = OBJECT_ID('tbl_musteri') AND name = 'Unvan')
    ALTER TABLE tbl_musteri ALTER COLUMN Unvan NVARCHAR(150) NOT NULL;
GO

-- VergiDairesi: 100 -> 50
IF EXISTS (SELECT * FROM sys.columns WHERE object_id = OBJECT_ID('tbl_musteri') AND name = 'VergiDairesi')
    ALTER TABLE tbl_musteri ALTER COLUMN VergiDairesi NVARCHAR(50) NULL;
GO

-- VergiNo: 20 -> 11
IF EXISTS (SELECT * FROM sys.columns WHERE object_id = OBJECT_ID('tbl_musteri') AND name = 'VergiNo')
    ALTER TABLE tbl_musteri ALTER COLUMN VergiNo NVARCHAR(11) NULL;
GO

-- MersisNo: 20 -> 16
IF EXISTS (SELECT * FROM sys.columns WHERE object_id = OBJECT_ID('tbl_musteri') AND name = 'MersisNo')
    ALTER TABLE tbl_musteri ALTER COLUMN MersisNo NVARCHAR(16) NULL;
GO

-- Telefon: 30 -> 20
IF EXISTS (SELECT * FROM sys.columns WHERE object_id = OBJECT_ID('tbl_musteri') AND name = 'Telefon')
    ALTER TABLE tbl_musteri ALTER COLUMN Telefon NVARCHAR(20) NULL;
GO

-- Faks: 30 -> 20
IF EXISTS (SELECT * FROM sys.columns WHERE object_id = OBJECT_ID('tbl_musteri') AND name = 'Faks')
    ALTER TABLE tbl_musteri ALTER COLUMN Faks NVARCHAR(20) NULL;
GO

-- Web: 255 -> 150
IF EXISTS (SELECT * FROM sys.columns WHERE object_id = OBJECT_ID('tbl_musteri') AND name = 'Web')
    ALTER TABLE tbl_musteri ALTER COLUMN Web NVARCHAR(150) NULL;
GO

-- Adres: 500 -> 300
IF EXISTS (SELECT * FROM sys.columns WHERE object_id = OBJECT_ID('tbl_musteri') AND name = 'Adres')
    ALTER TABLE tbl_musteri ALTER COLUMN Adres NVARCHAR(300) NULL;
GO

-- Aciklama: MAX -> 500
IF EXISTS (SELECT * FROM sys.columns WHERE object_id = OBJECT_ID('tbl_musteri') AND name = 'Aciklama')
    ALTER TABLE tbl_musteri ALTER COLUMN Aciklama NVARCHAR(500) NULL;
GO

-- Index'leri yeniden oluştur
CREATE NONCLUSTERED INDEX IX_tbl_musteri_Unvan ON tbl_musteri(Unvan);
GO

CREATE NONCLUSTERED INDEX IX_tbl_musteri_MusteriKodu ON tbl_musteri(MusteriKodu);
GO

CREATE NONCLUSTERED INDEX IX_tbl_musteri_VergiNo ON tbl_musteri(VergiNo);
GO

-- bck_tbl_musteri kolon optimizasyonları
IF EXISTS (SELECT * FROM sys.columns WHERE object_id = OBJECT_ID('bck_tbl_musteri') AND name = 'MusteriKodu')
    ALTER TABLE bck_tbl_musteri ALTER COLUMN MusteriKodu NVARCHAR(5) NULL;
GO

IF EXISTS (SELECT * FROM sys.columns WHERE object_id = OBJECT_ID('bck_tbl_musteri') AND name = 'Unvan')
    ALTER TABLE bck_tbl_musteri ALTER COLUMN Unvan NVARCHAR(150) NOT NULL;
GO

IF EXISTS (SELECT * FROM sys.columns WHERE object_id = OBJECT_ID('bck_tbl_musteri') AND name = 'VergiDairesi')
    ALTER TABLE bck_tbl_musteri ALTER COLUMN VergiDairesi NVARCHAR(50) NULL;
GO

IF EXISTS (SELECT * FROM sys.columns WHERE object_id = OBJECT_ID('bck_tbl_musteri') AND name = 'VergiNo')
    ALTER TABLE bck_tbl_musteri ALTER COLUMN VergiNo NVARCHAR(11) NULL;
GO

IF EXISTS (SELECT * FROM sys.columns WHERE object_id = OBJECT_ID('bck_tbl_musteri') AND name = 'MersisNo')
    ALTER TABLE bck_tbl_musteri ALTER COLUMN MersisNo NVARCHAR(16) NULL;
GO

IF EXISTS (SELECT * FROM sys.columns WHERE object_id = OBJECT_ID('bck_tbl_musteri') AND name = 'Telefon')
    ALTER TABLE bck_tbl_musteri ALTER COLUMN Telefon NVARCHAR(20) NULL;
GO

IF EXISTS (SELECT * FROM sys.columns WHERE object_id = OBJECT_ID('bck_tbl_musteri') AND name = 'Faks')
    ALTER TABLE bck_tbl_musteri ALTER COLUMN Faks NVARCHAR(20) NULL;
GO

IF EXISTS (SELECT * FROM sys.columns WHERE object_id = OBJECT_ID('bck_tbl_musteri') AND name = 'Web')
    ALTER TABLE bck_tbl_musteri ALTER COLUMN Web NVARCHAR(150) NULL;
GO

IF EXISTS (SELECT * FROM sys.columns WHERE object_id = OBJECT_ID('bck_tbl_musteri') AND name = 'Adres')
    ALTER TABLE bck_tbl_musteri ALTER COLUMN Adres NVARCHAR(300) NULL;
GO

IF EXISTS (SELECT * FROM sys.columns WHERE object_id = OBJECT_ID('bck_tbl_musteri') AND name = 'Aciklama')
    ALTER TABLE bck_tbl_musteri ALTER COLUMN Aciklama NVARCHAR(500) NULL;
GO

PRINT 'tbl_musteri ve bck_tbl_musteri kolon boyutlari optimize edildi.'
GO
