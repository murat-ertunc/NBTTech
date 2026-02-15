-- =============================================
-- Backup Tablolari - Sehir ve Ilce
-- kurallar.txt: Her tablo icin bck_ prefix ile backup tablosu
-- =============================================

-- Backup: tnm_sehir
IF OBJECT_ID('bck_tnm_sehir', 'U') IS NULL
BEGIN
    CREATE TABLE bck_tnm_sehir (
        Id                  INT IDENTITY(1,1) PRIMARY KEY,
        KaynakId            INT NOT NULL,
        BackupZamani        DATETIME2(0) NOT NULL DEFAULT SYSUTCDATETIME(),
        BackupUserId        INT NULL,

        -- Orijinal tablo verileri
        Guid                UNIQUEIDENTIFIER NOT NULL,
        EklemeZamani        DATETIME2(0) NOT NULL,
        EkleyenUserId       INT NULL,
        DegisiklikZamani    DATETIME2(0) NOT NULL,
        DegistirenUserId    INT NULL,
        Sil                 BIT NOT NULL,

        -- Sehir Bilgileri
        PlakaKodu           NVARCHAR(2) NOT NULL,
        Ad                  NVARCHAR(50) NOT NULL,
        Bolge               NVARCHAR(50) NULL
    );

    CREATE NONCLUSTERED INDEX IX_bck_tnm_sehir_KaynakId ON bck_tnm_sehir(KaynakId);
    CREATE NONCLUSTERED INDEX IX_bck_tnm_sehir_BackupZamani ON bck_tnm_sehir(BackupZamani);

    PRINT 'bck_tnm_sehir tablosu olusturuldu.';
END
GO

-- Backup: tnm_ilce
IF OBJECT_ID('bck_tnm_ilce', 'U') IS NULL
BEGIN
    CREATE TABLE bck_tnm_ilce (
        Id                  INT IDENTITY(1,1) PRIMARY KEY,
        KaynakId            INT NOT NULL,
        BackupZamani        DATETIME2(0) NOT NULL DEFAULT SYSUTCDATETIME(),
        BackupUserId        INT NULL,

        -- Orijinal tablo verileri
        Guid                UNIQUEIDENTIFIER NOT NULL,
        EklemeZamani        DATETIME2(0) NOT NULL,
        EkleyenUserId       INT NULL,
        DegisiklikZamani    DATETIME2(0) NOT NULL,
        DegistirenUserId    INT NULL,
        Sil                 BIT NOT NULL,

        -- Ilce Bilgileri
        SehirId             INT NOT NULL,
        Ad                  NVARCHAR(50) NOT NULL
    );

    CREATE NONCLUSTERED INDEX IX_bck_tnm_ilce_KaynakId ON bck_tnm_ilce(KaynakId);
    CREATE NONCLUSTERED INDEX IX_bck_tnm_ilce_BackupZamani ON bck_tnm_ilce(BackupZamani);

    PRINT 'bck_tnm_ilce tablosu olusturuldu.';
END
GO

PRINT '======================================';
PRINT 'bck_tnm_sehir ve bck_tnm_ilce migration tamamlandi.';
PRINT '======================================';
GO
