-- Dosya Backup Tablosu
IF OBJECT_ID('dbo.bck_tbl_dosya', 'U') IS NULL
BEGIN
    CREATE TABLE dbo.bck_tbl_dosya (
        Id INT IDENTITY(1,1) PRIMARY KEY,
        KaynakId INT NOT NULL,
        MusteriId INT NOT NULL,
        DosyaAdi NVARCHAR(255) NOT NULL,
        DosyaYolu NVARCHAR(500) NOT NULL,
        DosyaTipi NVARCHAR(100) NULL,
        DosyaBoyutu INT NULL,
        Aciklama NVARCHAR(500) NULL,
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
