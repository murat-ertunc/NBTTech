-- Kişi (Müşteri İletişim Kişisi) Tablosu
IF OBJECT_ID('dbo.tbl_kisi', 'U') IS NULL
BEGIN
    CREATE TABLE dbo.tbl_kisi (
        Id INT IDENTITY(1,1) PRIMARY KEY,
        MusteriId INT NOT NULL,
        AdSoyad NVARCHAR(255) NOT NULL,
        Unvan NVARCHAR(255) NULL,
        Telefon NVARCHAR(50) NULL,
        Email NVARCHAR(255) NULL,
        Notlar NVARCHAR(MAX) NULL,
        OlusturmaZamani DATETIME2 DEFAULT GETDATE(),
        OlusturanUserId INT NULL,
        DegisiklikZamani DATETIME2 NULL,
        DegistirenUserId INT NULL,
        Sil TINYINT DEFAULT 0,
        CONSTRAINT FK_kisi_musteri FOREIGN KEY (MusteriId) REFERENCES tbl_musteri(Id)
    );
END
GO
