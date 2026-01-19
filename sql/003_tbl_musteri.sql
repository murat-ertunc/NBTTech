-- Müşteri Ana Tablosu
CREATE TABLE tbl_musteri (
    Id INT IDENTITY(1,1) PRIMARY KEY,
    Guid UNIQUEIDENTIFIER NOT NULL DEFAULT NEWID(),
    EklemeZamani DATETIME2(0) NOT NULL DEFAULT SYSUTCDATETIME(),
    EkleyenUserId INT NULL,
    DegisiklikZamani DATETIME2(0) NOT NULL DEFAULT SYSUTCDATETIME(),
    DegistirenUserId INT NULL,
    Sil BIT NOT NULL DEFAULT 0,
    
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

CREATE NONCLUSTERED INDEX IX_tbl_musteri_Unvan ON tbl_musteri(Unvan);
CREATE NONCLUSTERED INDEX IX_tbl_musteri_MusteriKodu ON tbl_musteri(MusteriKodu);
CREATE NONCLUSTERED INDEX IX_tbl_musteri_VergiNo ON tbl_musteri(VergiNo);
CREATE NONCLUSTERED INDEX IX_tbl_musteri_EkleyenUserId ON tbl_musteri(EkleyenUserId);
CREATE NONCLUSTERED INDEX IX_tbl_musteri_Sil ON tbl_musteri(Sil);
GO
