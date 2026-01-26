-- Ödeme Backup Tablosu
IF OBJECT_ID('bck_tbl_odeme', 'U') IS NULL
BEGIN
    CREATE TABLE bck_tbl_odeme (
        Id INT IDENTITY(1,1) PRIMARY KEY,
        KaynakId INT NOT NULL,
        BackupZamani DATETIME2(0) NOT NULL DEFAULT SYSUTCDATETIME(),
        BackupUserId INT NULL,
        
        -- Orijinal tablo verileri
        Guid UNIQUEIDENTIFIER NOT NULL,
        EklemeZamani DATETIME2(0) NOT NULL,
        EkleyenUserId INT NULL,
        DegisiklikZamani DATETIME2(0) NOT NULL,
        DegistirenUserId INT NULL,
        Sil BIT NOT NULL,
        
        -- İlişkiler
        MusteriId INT NOT NULL,
        ProjeId INT NULL,
        FaturaId INT NULL,
        
        -- Ödeme Bilgileri
        Tarih DATE NOT NULL,
        Tutar DECIMAL(16,2) NOT NULL,
        Aciklama NVARCHAR(MAX) NULL
    );
END
GO

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_bck_tbl_odeme_KaynakId' AND object_id = OBJECT_ID('bck_tbl_odeme'))
    CREATE INDEX IX_bck_tbl_odeme_KaynakId ON bck_tbl_odeme(KaynakId);
GO
