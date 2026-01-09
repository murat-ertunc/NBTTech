IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='tbl_sozlesme' AND xtype='U')
BEGIN
    CREATE TABLE tbl_sozlesme (
        Id INT IDENTITY(1,1) PRIMARY KEY,
        Guid UNIQUEIDENTIFIER NOT NULL DEFAULT NEWID(),
        EklemeZamani DATETIME2(0) NOT NULL DEFAULT SYSUTCDATETIME(),
        EkleyenUserId INT NULL,
        DegisiklikZamani DATETIME2(0) NOT NULL DEFAULT SYSUTCDATETIME(),
        DegistirenUserId INT NULL,
        Sil BIT NOT NULL DEFAULT 0,
        MusteriId INT NOT NULL,
        TeklifId INT NULL,
        SozlesmeNo NVARCHAR(50) NOT NULL,
        BaslangicTarihi DATE NULL,
        BitisTarihi DATE NULL,
        Tutar DECIMAL(16,2) DEFAULT 0.00,
        ParaBirimi NVARCHAR(3) DEFAULT 'TRY',
        DosyaYolu NVARCHAR(255) NULL,
        Durum TINYINT DEFAULT 1
    );
    CREATE INDEX IX_tbl_sozlesme_MusteriId ON tbl_sozlesme (MusteriId);
    CREATE INDEX IX_tbl_sozlesme_TeklifId ON tbl_sozlesme (TeklifId);
    CREATE INDEX IX_tbl_sozlesme_Sil ON tbl_sozlesme (Sil);
END
