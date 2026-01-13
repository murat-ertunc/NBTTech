-- Görüşme Backup Tablosu
IF OBJECT_ID('dbo.bck_tbl_gorusme', 'U') IS NULL
BEGIN
    CREATE TABLE dbo.bck_tbl_gorusme (
        Id INT IDENTITY(1,1) PRIMARY KEY,
        KaynakId INT NOT NULL,
        MusteriId INT NOT NULL,
        Tarih DATE NOT NULL,
        Konu NVARCHAR(255) NOT NULL,
        Notlar NVARCHAR(MAX) NULL,
        Kisi NVARCHAR(255) NULL,
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
