-- Proje Backup Tablosu
IF OBJECT_ID('bck_tbl_proje', 'U') IS NULL
BEGIN
    CREATE TABLE bck_tbl_proje (
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

        -- Proje Bilgileri
        ProjeAdi NVARCHAR(255) NOT NULL,
        Butce DECIMAL(16,2) NULL,
        Durum TINYINT NULL
    );
END
GO

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_bck_tbl_proje_KaynakId' AND object_id = OBJECT_ID('bck_tbl_proje'))
    CREATE INDEX IX_bck_tbl_proje_KaynakId ON bck_tbl_proje(KaynakId);
GO
