-- =============================================
-- İl (Şehir) Tanım Tablosu
-- prefix: tnm_ (tanım tablosu)
-- =============================================
IF OBJECT_ID('tnm_sehir', 'U') IS NULL
BEGIN
    CREATE TABLE tnm_sehir (
        Id INT IDENTITY(1,1) PRIMARY KEY,
        Guid UNIQUEIDENTIFIER NOT NULL DEFAULT NEWID(),
        EklemeZamani DATETIME2(0) NOT NULL DEFAULT SYSUTCDATETIME(),
        EkleyenUserId INT NULL,
        DegisiklikZamani DATETIME2(0) NOT NULL DEFAULT SYSUTCDATETIME(),
        DegistirenUserId INT NULL,
        Sil BIT NOT NULL DEFAULT 0,
        
        -- Şehir Bilgileri
        PlakaKodu NVARCHAR(2) NOT NULL,
        Ad NVARCHAR(50) NOT NULL,
        Bolge NVARCHAR(50) NULL
    );
    PRINT 'tnm_sehir tablosu olusturuldu.';
END
ELSE
BEGIN
    PRINT 'tnm_sehir tablosu zaten mevcut.';
END
GO

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_tnm_sehir_Ad' AND object_id = OBJECT_ID('tnm_sehir'))
    CREATE NONCLUSTERED INDEX IX_tnm_sehir_Ad ON tnm_sehir(Ad);
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_tnm_sehir_PlakaKodu' AND object_id = OBJECT_ID('tnm_sehir'))
    CREATE NONCLUSTERED INDEX IX_tnm_sehir_PlakaKodu ON tnm_sehir(PlakaKodu);
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_tnm_sehir_Sil' AND object_id = OBJECT_ID('tnm_sehir'))
    CREATE NONCLUSTERED INDEX IX_tnm_sehir_Sil ON tnm_sehir(Sil);
GO

PRINT '======================================';
PRINT 'tnm_sehir tablosu migration tamamlandi.';
PRINT '======================================';
GO
