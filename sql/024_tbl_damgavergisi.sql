-- Damga Vergisi Tablosu
IF OBJECT_ID('dbo.tbl_damgavergisi', 'U') IS NULL
BEGIN
    CREATE TABLE dbo.tbl_damgavergisi (
        Id INT IDENTITY(1,1) PRIMARY KEY,
        MusteriId INT NOT NULL,
        Tarih DATE NOT NULL,
        Tutar DECIMAL(18,2) NOT NULL,
        DovizCinsi NVARCHAR(10) DEFAULT 'TRY',
        Aciklama NVARCHAR(500) NULL,
        BelgeNo NVARCHAR(100) NULL,
        OlusturmaZamani DATETIME2 DEFAULT GETDATE(),
        OlusturanUserId INT NULL,
        DegisiklikZamani DATETIME2 NULL,
        DegistirenUserId INT NULL,
        Sil TINYINT DEFAULT 0,
        CONSTRAINT FK_damgavergisi_musteri FOREIGN KEY (MusteriId) REFERENCES tbl_musteri(Id)
    );
END
GO
