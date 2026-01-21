-- Teklif Backup Tablosu
CREATE TABLE bck_tbl_teklif (
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
    
    -- Teklif Bilgileri
    Konu NVARCHAR(255) NULL,
    Tutar DECIMAL(16,2) NULL,
    ParaBirimi NVARCHAR(3) NULL,
    TeklifTarihi DATE NULL,
    GecerlilikTarihi DATE NULL,
    Durum TINYINT NULL,
    
    -- Dosya Bilgileri
    DosyaAdi NVARCHAR(255) NULL,
    DosyaYolu NVARCHAR(500) NULL
);
GO

CREATE INDEX IX_bck_tbl_teklif_KaynakId ON bck_tbl_teklif(KaynakId);
GO
