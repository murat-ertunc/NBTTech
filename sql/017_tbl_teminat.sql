IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='tbl_teminat' AND xtype='U')
BEGIN
    CREATE TABLE tbl_teminat (
        Id INT IDENTITY(1,1) PRIMARY KEY,
        Guid UNIQUEIDENTIFIER NOT NULL DEFAULT NEWID(),
        EklemeZamani DATETIME2(0) NOT NULL DEFAULT SYSUTCDATETIME(),
        EkleyenUserId INT NULL,
        DegisiklikZamani DATETIME2(0) NOT NULL DEFAULT SYSUTCDATETIME(),
        DegistirenUserId INT NULL,
        Sil BIT NOT NULL DEFAULT 0,
        MusteriId INT NOT NULL,
        ProjeId INT NULL,
        Tur NVARCHAR(50) NOT NULL,
        Tutar DECIMAL(16,2) DEFAULT 0.00,
        ParaBirimi NVARCHAR(3) DEFAULT 'TRY',
        BankaAdi NVARCHAR(100) NULL,
        VadeTarihi DATE NULL,
        BelgeNo NVARCHAR(50) NULL,
        Durum TINYINT DEFAULT 1
    );
    CREATE INDEX IX_tbl_teminat_MusteriId ON tbl_teminat (MusteriId);
    CREATE INDEX IX_tbl_teminat_Sil ON tbl_teminat (Sil);
END
