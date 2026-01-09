IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='tbl_teklif' AND xtype='U')
BEGIN
    CREATE TABLE tbl_teklif (
        Id INT IDENTITY(1,1) PRIMARY KEY,
        Guid UNIQUEIDENTIFIER NOT NULL DEFAULT NEWID(),
        EklemeZamani DATETIME2(0) NOT NULL DEFAULT SYSUTCDATETIME(),
        EkleyenUserId INT NULL,
        DegisiklikZamani DATETIME2(0) NOT NULL DEFAULT SYSUTCDATETIME(),
        DegistirenUserId INT NULL,
        Sil BIT NOT NULL DEFAULT 0,
        MusteriId INT NOT NULL,
        ProjeId INT NULL,
        TeklifNo NVARCHAR(50) NOT NULL,
        Konu NVARCHAR(255) NULL,
        Tutar DECIMAL(16,2) DEFAULT 0.00,
        ParaBirimi NVARCHAR(3) DEFAULT 'TRY',
        TeklifTarihi DATE NULL,
        GecerlilikTarihi DATE NULL,
        Durum TINYINT DEFAULT 0
    );
    CREATE INDEX IX_tbl_teklif_MusteriId ON tbl_teklif (MusteriId);
    CREATE INDEX IX_tbl_teklif_ProjeId ON tbl_teklif (ProjeId);
    CREATE INDEX IX_tbl_teklif_Sil ON tbl_teklif (Sil);
END
