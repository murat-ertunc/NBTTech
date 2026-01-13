-- Damga Vergisi Backup Tablosu
IF OBJECT_ID('dbo.bck_tbl_damgavergisi', 'U') IS NULL
BEGIN
    CREATE TABLE dbo.bck_tbl_damgavergisi (
        Id INT IDENTITY(1,1) PRIMARY KEY,
        KaynakId INT NOT NULL,
        MusteriId INT NOT NULL,
        Tarih DATE NOT NULL,
        Tutar DECIMAL(18,2) NOT NULL,
        DovizCinsi NVARCHAR(10) DEFAULT 'TRY',
        Aciklama NVARCHAR(500) NULL,
        BelgeNo NVARCHAR(100) NULL,
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
