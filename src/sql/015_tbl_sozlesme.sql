-- Sözleşme Ana Tablosu
IF OBJECT_ID('tbl_sozlesme', 'U') IS NULL
BEGIN
    CREATE TABLE tbl_sozlesme (
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
        TeklifId INT NULL,
        
        -- Sözleşme Bilgileri
        SozlesmeTarihi DATE NULL,
        Tutar DECIMAL(16,2) DEFAULT 0.00,
        ParaBirimi NVARCHAR(3) DEFAULT 'TRY',
        Durum TINYINT DEFAULT 1,
        
        -- Dosya Bilgileri
        DosyaAdi NVARCHAR(255) NULL,
        DosyaYolu NVARCHAR(500) NULL,
        
        CONSTRAINT FK_sozlesme_musteri FOREIGN KEY (MusteriId) REFERENCES tbl_musteri(Id),
        CONSTRAINT FK_sozlesme_proje FOREIGN KEY (ProjeId) REFERENCES tbl_proje(Id),
        CONSTRAINT FK_sozlesme_teklif FOREIGN KEY (TeklifId) REFERENCES tbl_teklif(Id)
    );
END
GO

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_tbl_sozlesme_MusteriId' AND object_id = OBJECT_ID('tbl_sozlesme'))
    CREATE INDEX IX_tbl_sozlesme_MusteriId ON tbl_sozlesme(MusteriId);
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_tbl_sozlesme_ProjeId' AND object_id = OBJECT_ID('tbl_sozlesme'))
    CREATE INDEX IX_tbl_sozlesme_ProjeId ON tbl_sozlesme(ProjeId);
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_tbl_sozlesme_TeklifId' AND object_id = OBJECT_ID('tbl_sozlesme'))
    CREATE INDEX IX_tbl_sozlesme_TeklifId ON tbl_sozlesme(TeklifId);
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_tbl_sozlesme_Sil' AND object_id = OBJECT_ID('tbl_sozlesme'))
    CREATE INDEX IX_tbl_sozlesme_Sil ON tbl_sozlesme(Sil);
GO
