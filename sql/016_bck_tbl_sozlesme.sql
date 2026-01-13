IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='bck_tbl_sozlesme' AND xtype='U')
BEGIN
    CREATE TABLE bck_tbl_sozlesme (
        BckId INT IDENTITY(1,1) PRIMARY KEY,
        KaynakId INT NOT NULL,
        EklemeZamani DATETIME2(0),
        EkleyenUserId INT NULL,
        DegisiklikZamani DATETIME2(0),
        DegistirenUserId INT NULL,
        Sil BIT,
        MusteriId INT NOT NULL,
        TeklifId INT NULL,
        SozlesmeNo NVARCHAR(50) NOT NULL,
        BaslangicTarihi DATE NULL,
        BitisTarihi DATE NULL,
        Tutar DECIMAL(16,2),
        ParaBirimi NVARCHAR(3),
        DosyaYolu NVARCHAR(255),
        Durum TINYINT,
        BackupZamani DATETIME2(0) NOT NULL DEFAULT SYSUTCDATETIME(),
        BackupUserId INT NULL
    );
END
