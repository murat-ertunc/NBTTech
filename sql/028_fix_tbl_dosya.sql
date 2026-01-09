-- tbl_dosya tablosunu standart alan adlarina uygun hale getir
-- Bu script mevcut tabloyu kaldirir ve yeniden olusturur

-- Mevcut tabloyu kaldir (varsa)
IF OBJECT_ID('dbo.tbl_dosya', 'U') IS NOT NULL
BEGIN
    DROP TABLE dbo.tbl_dosya;
END
GO

-- Standart alan adlari ile yeniden olustur
CREATE TABLE dbo.tbl_dosya (
    Id INT IDENTITY(1,1) PRIMARY KEY,
    Guid UNIQUEIDENTIFIER NOT NULL DEFAULT NEWID(),
    EklemeZamani DATETIME2(0) NOT NULL DEFAULT SYSUTCDATETIME(),
    EkleyenUserId INT NULL,
    DegisiklikZamani DATETIME2(0) NOT NULL DEFAULT SYSUTCDATETIME(),
    DegistirenUserId INT NULL,
    Sil BIT NOT NULL DEFAULT 0,
    MusteriId INT NOT NULL,
    DosyaAdi NVARCHAR(255) NOT NULL,
    DosyaYolu NVARCHAR(500) NOT NULL,
    DosyaTipi NVARCHAR(100) NULL,
    DosyaBoyutu INT NULL,
    Aciklama NVARCHAR(500) NULL,
    CONSTRAINT FK_dosya_musteri FOREIGN KEY (MusteriId) REFERENCES tbl_musteri(Id)
);
GO

CREATE INDEX IX_tbl_dosya_MusteriId ON tbl_dosya (MusteriId);
CREATE INDEX IX_tbl_dosya_Sil ON tbl_dosya (Sil);
GO

PRINT 'tbl_dosya tablosu standart alan adlari ile yeniden olusturuldu.';
GO
