-- Ödeme Ana Tablosu
IF OBJECT_ID('tbl_odeme', 'U') IS NULL
BEGIN
    CREATE TABLE tbl_odeme (
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
        FaturaId INT NULL,

        -- Ödeme Bilgileri
        Tarih DATE NOT NULL,
        Tutar DECIMAL(16,2) NOT NULL DEFAULT 0,
        Aciklama NVARCHAR(MAX) NULL,

        CONSTRAINT FK_odeme_musteri FOREIGN KEY (MusteriId) REFERENCES tbl_musteri(Id),
        CONSTRAINT FK_odeme_proje FOREIGN KEY (ProjeId) REFERENCES tbl_proje(Id),
        CONSTRAINT FK_odeme_fatura FOREIGN KEY (FaturaId) REFERENCES tbl_fatura(Id)
    );
END
GO

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_tbl_odeme_MusteriId' AND object_id = OBJECT_ID('tbl_odeme'))
    CREATE INDEX IX_tbl_odeme_MusteriId ON tbl_odeme(MusteriId);
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_tbl_odeme_ProjeId' AND object_id = OBJECT_ID('tbl_odeme'))
    CREATE INDEX IX_tbl_odeme_ProjeId ON tbl_odeme(ProjeId);
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_tbl_odeme_FaturaId' AND object_id = OBJECT_ID('tbl_odeme'))
    CREATE INDEX IX_tbl_odeme_FaturaId ON tbl_odeme(FaturaId);
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_tbl_odeme_Tarih' AND object_id = OBJECT_ID('tbl_odeme'))
    CREATE INDEX IX_tbl_odeme_Tarih ON tbl_odeme(Tarih);
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_tbl_odeme_Sil' AND object_id = OBJECT_ID('tbl_odeme'))
    CREATE INDEX IX_tbl_odeme_Sil ON tbl_odeme(Sil);
GO
