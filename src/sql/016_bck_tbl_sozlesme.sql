-- Sözleşme Backup Tablosu
IF OBJECT_ID('bck_tbl_sozlesme', 'U') IS NULL
BEGIN
    CREATE TABLE bck_tbl_sozlesme (
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
        TeklifId INT NULL,

        -- Sözleşme Bilgileri
        SozlesmeTarihi DATE NULL,
        Tutar DECIMAL(16,2) NULL,
        ParaBirimi NVARCHAR(3) NULL,
        Durum TINYINT NULL,

        -- Dosya Bilgileri
        DosyaAdi NVARCHAR(255) NULL,
        DosyaYolu NVARCHAR(500) NULL
    );
END
GO

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_bck_tbl_sozlesme_KaynakId' AND object_id = OBJECT_ID('bck_tbl_sozlesme'))
    CREATE INDEX IX_bck_tbl_sozlesme_KaynakId ON bck_tbl_sozlesme(KaynakId);
GO
