-- Damga Vergisi Ana Tablosu
IF OBJECT_ID('tbl_damgavergisi', 'U') IS NULL
BEGIN
    CREATE TABLE tbl_damgavergisi (
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
        
        -- Damga Vergisi Bilgileri
        Tarih DATE NOT NULL,
        Tutar DECIMAL(18,2) NOT NULL,
        DovizCinsi NVARCHAR(10) DEFAULT 'TRY',
        BelgeNo NVARCHAR(100) NULL,
        OdemeDurumu NVARCHAR(50) NULL,
        Notlar NVARCHAR(500) NULL,
        
        -- Dosya Bilgileri
        DosyaAdi NVARCHAR(255) NULL,
        DosyaYolu NVARCHAR(500) NULL,
        
        CONSTRAINT FK_damgavergisi_musteri FOREIGN KEY (MusteriId) REFERENCES tbl_musteri(Id),
        CONSTRAINT FK_damgavergisi_proje FOREIGN KEY (ProjeId) REFERENCES tbl_proje(Id)
    );
END
GO

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_tbl_damgavergisi_MusteriId' AND object_id = OBJECT_ID('tbl_damgavergisi'))
    CREATE INDEX IX_tbl_damgavergisi_MusteriId ON tbl_damgavergisi(MusteriId);
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_tbl_damgavergisi_ProjeId' AND object_id = OBJECT_ID('tbl_damgavergisi'))
    CREATE INDEX IX_tbl_damgavergisi_ProjeId ON tbl_damgavergisi(ProjeId);
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_tbl_damgavergisi_Sil' AND object_id = OBJECT_ID('tbl_damgavergisi'))
    CREATE INDEX IX_tbl_damgavergisi_Sil ON tbl_damgavergisi(Sil);
GO
