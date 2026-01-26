-- Damga Vergisi Backup Tablosu
IF OBJECT_ID('bck_tbl_damgavergisi', 'U') IS NULL
BEGIN
    CREATE TABLE bck_tbl_damgavergisi (
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
        
        -- Damga Vergisi Bilgileri
        Tarih DATE NOT NULL,
        Tutar DECIMAL(18,2) NOT NULL,
        DovizCinsi NVARCHAR(10) NULL,
        Aciklama NVARCHAR(500) NULL,
        BelgeNo NVARCHAR(100) NULL,
        
        -- Dosya Bilgileri
        DosyaAdi NVARCHAR(255) NULL,
        DosyaYolu NVARCHAR(500) NULL
    );
END
GO

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_bck_tbl_damgavergisi_KaynakId' AND object_id = OBJECT_ID('bck_tbl_damgavergisi'))
    CREATE INDEX IX_bck_tbl_damgavergisi_KaynakId ON bck_tbl_damgavergisi(KaynakId);
GO
