-- Kişi Backup Tablosu
IF OBJECT_ID('dbo.bck_tbl_kisi', 'U') IS NULL
BEGIN
    CREATE TABLE dbo.bck_tbl_kisi (
        Id INT IDENTITY(1,1) PRIMARY KEY,
        KaynakId INT NOT NULL,
        MusteriId INT NOT NULL,
        AdSoyad NVARCHAR(255) NOT NULL,
        Unvan NVARCHAR(255) NULL,
        Telefon NVARCHAR(50) NULL,
        Email NVARCHAR(255) NULL,
        Notlar NVARCHAR(MAX) NULL,
        OlusturmaZamani DATETIME2 NULL,
        OlusturanUserId INT NULL,
        DegisiklikZamani DATETIME2 NULL,
        DegistirenUserId INT NULL,
        Sil TINYINT DEFAULT 0,
        BackupZamani DATETIME2 DEFAULT GETDATE(),
        BackupUserId INT NULL
    );
END
GO
