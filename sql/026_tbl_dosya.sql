-- Dosya Tablosu (Standart alan adlari ile)
IF OBJECT_ID('dbo.tbl_dosya', 'U') IS NULL
BEGIN
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
    
    CREATE INDEX IX_tbl_dosya_MusteriId ON tbl_dosya (MusteriId);
    CREATE INDEX IX_tbl_dosya_Sil ON tbl_dosya (Sil);
END
GO
