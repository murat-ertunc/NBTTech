-- Teklif Ana Tablosu
IF OBJECT_ID('tbl_teklif', 'U') IS NULL
BEGIN
    CREATE TABLE tbl_teklif (
        Id INT IDENTITY(1,1) PRIMARY KEY,
        Guid UNIQUEIDENTIFIER NOT NULL DEFAULT NEWID(),
        EklemeZamani DATETIME2(0) NOT NULL DEFAULT SYSUTCDATETIME(),
        EkleyenUserId INT NULL,
        DegisiklikZamani DATETIME2(0) NOT NULL DEFAULT SYSUTCDATETIME(),
        DegistirenUserId INT NULL,
        Sil BIT NOT NULL DEFAULT 0,
        
        -- İlişkiler
        MusteriId INT NOT NULL,
        ProjeId INT NULL,
        
        -- Teklif Bilgileri
        Konu NVARCHAR(255) NULL,
        Tutar DECIMAL(16,2) DEFAULT 0.00,
        ParaBirimi NVARCHAR(3) DEFAULT 'TRY',
        TeklifTarihi DATE NULL,
        GecerlilikTarihi DATE NULL,
        Durum TINYINT DEFAULT 0,
        
        -- Dosya Bilgileri
        DosyaAdi NVARCHAR(255) NULL,
        DosyaYolu NVARCHAR(500) NULL,
        
        CONSTRAINT FK_teklif_musteri FOREIGN KEY (MusteriId) REFERENCES tbl_musteri(Id),
        CONSTRAINT FK_teklif_proje FOREIGN KEY (ProjeId) REFERENCES tbl_proje(Id)
    );
END
GO

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_tbl_teklif_MusteriId' AND object_id = OBJECT_ID('tbl_teklif'))
    CREATE INDEX IX_tbl_teklif_MusteriId ON tbl_teklif(MusteriId);
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_tbl_teklif_ProjeId' AND object_id = OBJECT_ID('tbl_teklif'))
    CREATE INDEX IX_tbl_teklif_ProjeId ON tbl_teklif(ProjeId);
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_tbl_teklif_Sil' AND object_id = OBJECT_ID('tbl_teklif'))
    CREATE INDEX IX_tbl_teklif_Sil ON tbl_teklif(Sil);
GO
