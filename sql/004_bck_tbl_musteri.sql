-- Müşteri Backup Tablosu
CREATE TABLE bck_tbl_musteri (
    Id INT IDENTITY(1,1) PRIMARY KEY,
    KaynakId INT NOT NULL,
    BackupZamani DATETIME2(0) NOT NULL DEFAULT SYSUTCDATETIME(),
    BackupUserId INT NULL,
    
    -- Orijinal tablo verileri
    Guid UNIQUEIDENTIFIER NOT NULL,
    EklemeZamani DATETIME2(0) NOT NULL,
    EkleyenUserId INT NULL,
    DegisiklikZamani DATETIME2(0) NOT NULL,
    DegistirenUserId INT NULL,
    Sil BIT NOT NULL,
    
    -- Temel Bilgiler
    MusteriKodu NVARCHAR(10) NULL,
    Unvan NVARCHAR(150) NOT NULL,
    Aciklama NVARCHAR(500) NULL,
    
    -- Vergi Bilgileri
    VergiDairesi NVARCHAR(50) NULL,
    VergiNo NVARCHAR(11) NULL,
    MersisNo NVARCHAR(16) NULL,
    
    -- İletişim Bilgileri
    Adres NVARCHAR(300) NULL,
    Telefon NVARCHAR(20) NULL,
    Faks NVARCHAR(20) NULL,
    Web NVARCHAR(150) NULL
);
GO

CREATE INDEX IX_bck_tbl_musteri_KaynakId ON bck_tbl_musteri(KaynakId);
GO
