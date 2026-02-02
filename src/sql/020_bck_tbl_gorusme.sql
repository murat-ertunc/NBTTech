-- Görüşme Backup Tablosu
IF OBJECT_ID('bck_tbl_gorusme', 'U') IS NULL
BEGIN
    CREATE TABLE bck_tbl_gorusme (
        Id INT IDENTITY(1,1) PRIMARY KEY,
        KaynakId INT NOT NULL,
        BackupZamani DATETIME2(0) NOT NULL DEFAULT SYSUTCDATETIME(),
        BackupUserId INT NULL,

        -- Orijinal tablo verileri
        Guid UNIQUEIDENTIFIER NULL,
        EklemeZamani DATETIME2(0) NULL,
        EkleyenUserId INT NULL,
        DegisiklikZamani DATETIME2(0) NULL,
        DegistirenUserId INT NULL,
        Sil BIT NULL,

        -- İlişkiler
        MusteriId INT NOT NULL,
        ProjeId INT NULL,

        -- Görüşme Bilgileri
        Tarih DATE NOT NULL,
        Konu NVARCHAR(255) NOT NULL,
        Notlar NVARCHAR(MAX) NULL,
        Kisi NVARCHAR(255) NULL,
        Eposta NVARCHAR(255) NULL,
        Telefon NVARCHAR(50) NULL
    );
END
GO

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_bck_tbl_gorusme_KaynakId' AND object_id = OBJECT_ID('bck_tbl_gorusme'))
    CREATE INDEX IX_bck_tbl_gorusme_KaynakId ON bck_tbl_gorusme(KaynakId);
GO
