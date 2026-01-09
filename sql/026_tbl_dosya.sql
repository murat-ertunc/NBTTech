-- Dosya Tablosu
IF OBJECT_ID('dbo.tbl_dosya', 'U') IS NULL
BEGIN
    CREATE TABLE dbo.tbl_dosya (
        Id INT IDENTITY(1,1) PRIMARY KEY,
        MusteriId INT NOT NULL,
        DosyaAdi NVARCHAR(255) NOT NULL,
        DosyaYolu NVARCHAR(500) NOT NULL,
        DosyaTipi NVARCHAR(100) NULL,
        DosyaBoyutu INT NULL,
        Aciklama NVARCHAR(500) NULL,
        OlusturmaZamani DATETIME2 DEFAULT GETDATE(),
        OlusturanUserId INT NULL,
        DegisiklikZamani DATETIME2 NULL,
        DegistirenUserId INT NULL,
        Sil TINYINT DEFAULT 0,
        CONSTRAINT FK_dosya_musteri FOREIGN KEY (MusteriId) REFERENCES tbl_musteri(Id)
    );
END
GO
