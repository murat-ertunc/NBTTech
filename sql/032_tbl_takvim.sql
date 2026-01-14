-- Takvim Tablosu
IF OBJECT_ID('dbo.tbl_takvim', 'U') IS NULL
BEGIN
    CREATE TABLE dbo.tbl_takvim (
        Id INT IDENTITY(1,1) PRIMARY KEY,
        MusteriId INT NOT NULL,
        ProjeId INT NULL,
        BaslangicTarihi DATE NOT NULL,
        BitisTarihi DATE NOT NULL,
        Ozet NVARCHAR(255) NOT NULL,
        OlusturmaZamani DATETIME2 DEFAULT GETDATE(),
        OlusturanUserId INT NULL,
        DegisiklikZamani DATETIME2 NULL,
        DegistirenUserId INT NULL,
        Sil TINYINT DEFAULT 0,
        CONSTRAINT FK_takvim_musteri FOREIGN KEY (MusteriId) REFERENCES tbl_musteri(Id),
        CONSTRAINT FK_takvim_proje FOREIGN KEY (ProjeId) REFERENCES tbl_proje(Id)
    );
END
GO

-- Backup Tablosu
IF OBJECT_ID('dbo.bck_tbl_takvim', 'U') IS NULL
BEGIN
    CREATE TABLE dbo.bck_tbl_takvim (
        YedekId INT IDENTITY(1,1) PRIMARY KEY,
        YedekZamani DATETIME2 DEFAULT GETDATE(),
        YedekleyenUserId INT NULL,
        Id INT,
        MusteriId INT,
        ProjeId INT,
        BaslangicTarihi DATE,
        BitisTarihi DATE,
        Ozet NVARCHAR(255),
        OlusturmaZamani DATETIME2,
        OlusturanUserId INT,
        DegisiklikZamani DATETIME2,
        DegistirenUserId INT,
        Sil TINYINT
    );
END
GO
