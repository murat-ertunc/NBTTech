-- Dosya Ana Tablosu
IF OBJECT_ID('tbl_dosya', 'U') IS NULL
BEGIN
    CREATE TABLE tbl_dosya (
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

        -- Dosya Bilgileri
        DosyaAdi NVARCHAR(255) NOT NULL,
        DosyaYolu NVARCHAR(500) NOT NULL,
        DosyaTipi NVARCHAR(100) NULL,
        DosyaBoyutu INT NULL,
        Aciklama NVARCHAR(500) NULL,

        CONSTRAINT FK_dosya_musteri FOREIGN KEY (MusteriId) REFERENCES tbl_musteri(Id),
        CONSTRAINT FK_dosya_proje FOREIGN KEY (ProjeId) REFERENCES tbl_proje(Id),
        CONSTRAINT FK_dosya_fatura FOREIGN KEY (FaturaId) REFERENCES tbl_fatura(Id)
    );
END
GO

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_tbl_dosya_MusteriId' AND object_id = OBJECT_ID('tbl_dosya'))
    CREATE INDEX IX_tbl_dosya_MusteriId ON tbl_dosya(MusteriId);
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_tbl_dosya_ProjeId' AND object_id = OBJECT_ID('tbl_dosya'))
    CREATE INDEX IX_tbl_dosya_ProjeId ON tbl_dosya(ProjeId);
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_tbl_dosya_FaturaId' AND object_id = OBJECT_ID('tbl_dosya'))
    CREATE INDEX IX_tbl_dosya_FaturaId ON tbl_dosya(FaturaId);
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_tbl_dosya_Sil' AND object_id = OBJECT_ID('tbl_dosya'))
    CREATE INDEX IX_tbl_dosya_Sil ON tbl_dosya(Sil);
GO
