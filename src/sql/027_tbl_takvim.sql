-- Takvim Ana Tablosu
IF OBJECT_ID('tbl_takvim', 'U') IS NULL
BEGIN
    CREATE TABLE tbl_takvim (
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
        
        -- Takvim Bilgileri
        TerminTarihi DATE NOT NULL,
        Ozet NVARCHAR(255) NOT NULL,
        
        CONSTRAINT FK_takvim_musteri FOREIGN KEY (MusteriId) REFERENCES tbl_musteri(Id),
        CONSTRAINT FK_takvim_proje FOREIGN KEY (ProjeId) REFERENCES tbl_proje(Id)
    );
END
GO

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_tbl_takvim_MusteriId' AND object_id = OBJECT_ID('tbl_takvim'))
    CREATE INDEX IX_tbl_takvim_MusteriId ON tbl_takvim(MusteriId);
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_tbl_takvim_ProjeId' AND object_id = OBJECT_ID('tbl_takvim'))
    CREATE INDEX IX_tbl_takvim_ProjeId ON tbl_takvim(ProjeId);
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_tbl_takvim_Sil' AND object_id = OBJECT_ID('tbl_takvim'))
    CREATE INDEX IX_tbl_takvim_Sil ON tbl_takvim(Sil);
GO

-- Takvim Backup Tablosu
IF OBJECT_ID('bck_tbl_takvim', 'U') IS NULL
BEGIN
    CREATE TABLE bck_tbl_takvim (
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
        MusteriId INT NULL,
        ProjeId INT NULL,
        
        -- Takvim Bilgileri
        TerminTarihi DATE NULL,
        Ozet NVARCHAR(255) NULL
    );
END
GO

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_bck_tbl_takvim_KaynakId' AND object_id = OBJECT_ID('bck_tbl_takvim'))
    CREATE INDEX IX_bck_tbl_takvim_KaynakId ON bck_tbl_takvim(KaynakId);
GO
