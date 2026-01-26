-- Proje Ana Tablosu
IF OBJECT_ID('tbl_proje', 'U') IS NULL
BEGIN
    CREATE TABLE tbl_proje (
        Id INT IDENTITY(1,1) PRIMARY KEY,
        Guid UNIQUEIDENTIFIER NOT NULL DEFAULT NEWID(),
        EklemeZamani DATETIME2(0) NOT NULL DEFAULT SYSUTCDATETIME(),
        EkleyenUserId INT NULL,
        DegisiklikZamani DATETIME2(0) NOT NULL DEFAULT SYSUTCDATETIME(),
        DegistirenUserId INT NULL,
        Sil BIT NOT NULL DEFAULT 0,
        
        -- İlişkiler
        MusteriId INT NOT NULL,
        
        -- Proje Bilgileri
        ProjeAdi NVARCHAR(255) NOT NULL,
        Butce DECIMAL(16,2) DEFAULT 0.00,
        Durum TINYINT DEFAULT 1,
        
        CONSTRAINT FK_proje_musteri FOREIGN KEY (MusteriId) REFERENCES tbl_musteri(Id)
    );
END
GO

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_tbl_proje_MusteriId' AND object_id = OBJECT_ID('tbl_proje'))
    CREATE INDEX IX_tbl_proje_MusteriId ON tbl_proje(MusteriId);
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_tbl_proje_Sil' AND object_id = OBJECT_ID('tbl_proje'))
    CREATE INDEX IX_tbl_proje_Sil ON tbl_proje(Sil);
GO
