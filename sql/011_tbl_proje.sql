IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='tbl_proje' AND xtype='U')
BEGIN
    CREATE TABLE tbl_proje (
        Id INT IDENTITY(1,1) PRIMARY KEY,
        Guid UNIQUEIDENTIFIER NOT NULL DEFAULT NEWID(),
        EklemeZamani DATETIME2(0) NOT NULL DEFAULT SYSUTCDATETIME(),
        EkleyenUserId INT NULL,
        DegisiklikZamani DATETIME2(0) NOT NULL DEFAULT SYSUTCDATETIME(),
        DegistirenUserId INT NULL,
        Sil BIT NOT NULL DEFAULT 0,
        MusteriId INT NOT NULL,
        ProjeAdi NVARCHAR(255) NOT NULL,
        BaslangicTarihi DATE NULL,
        BitisTarihi DATE NULL,
        Butce DECIMAL(16,2) DEFAULT 0.00,
        Durum TINYINT DEFAULT 1
    );
    CREATE INDEX IX_tbl_proje_MusteriId ON tbl_proje (MusteriId);
    CREATE INDEX IX_tbl_proje_Sil ON tbl_proje (Sil);
END
