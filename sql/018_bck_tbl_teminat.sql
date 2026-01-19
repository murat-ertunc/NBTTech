-- Teminat Backup Tablosu
CREATE TABLE bck_tbl_teminat (
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
    
    -- Teminat Bilgileri
    Tur NVARCHAR(50) NOT NULL,
    Tutar DECIMAL(16,2) NULL,
    ParaBirimi NVARCHAR(3) NULL,
    BankaAdi NVARCHAR(100) NULL,
    TerminTarihi DATE NULL,
    Durum TINYINT NULL,
    
    -- Dosya Bilgileri
    DosyaAdi NVARCHAR(255) NULL,
    DosyaYolu NVARCHAR(500) NULL
);
GO

CREATE INDEX IX_bck_tbl_teminat_KaynakId ON bck_tbl_teminat(KaynakId);
GO
