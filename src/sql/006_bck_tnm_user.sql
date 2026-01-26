-- tnm_user icin yedekleme tablosu (Update oncesi eski verinin saklanmasi)
IF OBJECT_ID('bck_tnm_user', 'U') IS NULL
BEGIN
    CREATE TABLE bck_tnm_user (
        Id INT IDENTITY(1,1) PRIMARY KEY,
        KaynakId INT NOT NULL,
        Guid UNIQUEIDENTIFIER NOT NULL,
        EklemeZamani DATETIME2(0) NOT NULL,
        EkleyenUserId INT NULL,
        DegisiklikZamani DATETIME2(0) NOT NULL,
        DegistirenUserId INT NULL,
        Sil BIT NOT NULL,
        KullaniciAdi NVARCHAR(50) NOT NULL,
        Parola NVARCHAR(255) NOT NULL,
        AdSoyad NVARCHAR(150) NOT NULL,
        Aktif BIT NOT NULL,
        Rol NVARCHAR(50) NOT NULL,
        BackupZamani DATETIME2(0) NOT NULL DEFAULT SYSUTCDATETIME(),
        BackupUserId INT NULL
    );
END

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_bck_tnm_user_KaynakId' AND object_id = OBJECT_ID('bck_tnm_user'))
    CREATE INDEX IX_bck_tnm_user_KaynakId ON bck_tnm_user (KaynakId);
