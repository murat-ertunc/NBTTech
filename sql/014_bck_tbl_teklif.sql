IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='bck_tbl_teklif' AND xtype='U')
BEGIN
    CREATE TABLE bck_tbl_teklif (
        BckId INT IDENTITY(1,1) PRIMARY KEY,
        KaynakId INT NOT NULL,
        EklemeZamani DATETIME2(0),
        EkleyenUserId INT NULL,
        DegisiklikZamani DATETIME2(0),
        DegistirenUserId INT NULL,
        Sil BIT,
        MusteriId INT NOT NULL,
        ProjeId INT NULL,
        TeklifNo NVARCHAR(50) NOT NULL,
        Konu NVARCHAR(255) NULL,
        Tutar DECIMAL(16,2),
        ParaBirimi NVARCHAR(3),
        TeklifTarihi DATE NULL,
        GecerlilikTarihi DATE NULL,
        Durum TINYINT,
        BackupZamani DATETIME2(0) NOT NULL DEFAULT SYSUTCDATETIME(),
        BackupUserId INT NULL
    );
END
