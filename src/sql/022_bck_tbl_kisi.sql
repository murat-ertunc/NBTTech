-- Kişi Backup Tablosu
IF OBJECT_ID('bck_tbl_kisi', 'U') IS NULL
BEGIN
    CREATE TABLE bck_tbl_kisi (
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
        
        -- Kişi Bilgileri
        AdSoyad NVARCHAR(255) NOT NULL,
        Unvan NVARCHAR(255) NULL,
        Telefon NVARCHAR(50) NULL,
        DahiliNo NVARCHAR(50) NULL,
        Email NVARCHAR(255) NULL,
        Notlar NVARCHAR(MAX) NULL
    );
END
GO

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_bck_tbl_kisi_KaynakId' AND object_id = OBJECT_ID('bck_tbl_kisi'))
    CREATE INDEX IX_bck_tbl_kisi_KaynakId ON bck_tbl_kisi(KaynakId);
GO
