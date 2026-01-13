-- Görüşme Tablosu
IF OBJECT_ID('dbo.tbl_gorusme', 'U') IS NULL
BEGIN
    CREATE TABLE dbo.tbl_gorusme (
        Id INT IDENTITY(1,1) PRIMARY KEY,
        MusteriId INT NOT NULL,
        Tarih DATE NOT NULL,
        Konu NVARCHAR(255) NOT NULL,
        Notlar NVARCHAR(MAX) NULL,
        Kisi NVARCHAR(255) NULL,
        OlusturmaZamani DATETIME2 DEFAULT GETDATE(),
        OlusturanUserId INT NULL,
        DegisiklikZamani DATETIME2 NULL,
        DegistirenUserId INT NULL,
        Sil TINYINT DEFAULT 0,
        CONSTRAINT FK_gorusme_musteri FOREIGN KEY (MusteriId) REFERENCES tbl_musteri(Id)
    );
END
GO
