-- Fatura Backup Tablosu
IF OBJECT_ID('bck_tbl_fatura', 'U') IS NULL
BEGIN
    CREATE TABLE bck_tbl_fatura (
        Id INT IDENTITY(1,1) PRIMARY KEY,
        KaynakId INT NOT NULL,
        BackupZamani DATETIME2(0) NOT NULL DEFAULT SYSUTCDATETIME(),
        BackupUserId INT NULL,

        -- Orijinal tablo verileri
        Guid UNIQUEIDENTIFIER NOT NULL,
        EklemeZamani DATETIME2(0) NOT NULL,
        EkleyenUserId INT NULL,
        DegisiklikZamani DATETIME2(0) NOT NULL,
        DegistirenUserId INT NULL,
        Sil BIT NOT NULL,

        -- İlişkiler
        MusteriId INT NOT NULL,
        ProjeId INT NULL,

        -- Fatura Bilgileri
        FaturaNo NVARCHAR(50) NULL,
        Tarih DATE NOT NULL,
        Tutar DECIMAL(16,2) NOT NULL,
        DovizCinsi NVARCHAR(3) NOT NULL,
        Aciklama NVARCHAR(MAX) NULL,

        -- Şüpheli Alacak
        SupheliAlacak TINYINT NULL,

        -- Tevkifat Bilgileri
        TevkifatAktif TINYINT NULL,
        TevkifatOran1 DECIMAL(5,2) NULL,
        TevkifatOran2 DECIMAL(5,2) NULL,

        -- Takvim Hatırlatma
        TakvimAktif TINYINT NULL,
        TakvimSure INT NULL,
        TakvimSureTipi NVARCHAR(10) NULL
    );
END
GO

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_bck_tbl_fatura_KaynakId' AND object_id = OBJECT_ID('bck_tbl_fatura'))
    CREATE INDEX IX_bck_tbl_fatura_KaynakId ON bck_tbl_fatura(KaynakId);
GO
