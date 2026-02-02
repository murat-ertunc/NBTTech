-- Teminat Ana Tablosu
IF OBJECT_ID('tbl_teminat', 'U') IS NULL
BEGIN
    CREATE TABLE tbl_teminat (
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

        -- Teminat Bilgileri
        Tur NVARCHAR(50) NOT NULL,
        Tutar DECIMAL(16,2) DEFAULT 0.00,
        ParaBirimi NVARCHAR(3) DEFAULT 'TRY',
        BankaAdi NVARCHAR(100) NULL,
        TerminTarihi DATE NULL,
        Durum TINYINT DEFAULT 1,

        -- Dosya Bilgileri
        DosyaAdi NVARCHAR(255) NULL,
        DosyaYolu NVARCHAR(500) NULL,

        CONSTRAINT FK_teminat_musteri FOREIGN KEY (MusteriId) REFERENCES tbl_musteri(Id),
        CONSTRAINT FK_teminat_proje FOREIGN KEY (ProjeId) REFERENCES tbl_proje(Id)
    );
END
GO

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_tbl_teminat_MusteriId' AND object_id = OBJECT_ID('tbl_teminat'))
    CREATE INDEX IX_tbl_teminat_MusteriId ON tbl_teminat(MusteriId);
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_tbl_teminat_ProjeId' AND object_id = OBJECT_ID('tbl_teminat'))
    CREATE INDEX IX_tbl_teminat_ProjeId ON tbl_teminat(ProjeId);
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_tbl_teminat_Sil' AND object_id = OBJECT_ID('tbl_teminat'))
    CREATE INDEX IX_tbl_teminat_Sil ON tbl_teminat(Sil);
GO
