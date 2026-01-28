-- =============================================
-- İlçe Tanım Tablosu
-- prefix: tnm_ (tanım tablosu)
-- =============================================
IF OBJECT_ID('tnm_ilce', 'U') IS NULL
BEGIN
    CREATE TABLE tnm_ilce (
        Id INT IDENTITY(1,1) PRIMARY KEY,
        Guid UNIQUEIDENTIFIER NOT NULL DEFAULT NEWID(),
        EklemeZamani DATETIME2(0) NOT NULL DEFAULT SYSUTCDATETIME(),
        EkleyenUserId INT NULL,
        DegisiklikZamani DATETIME2(0) NOT NULL DEFAULT SYSUTCDATETIME(),
        DegistirenUserId INT NULL,
        Sil BIT NOT NULL DEFAULT 0,
        
        -- İlçe Bilgileri
        SehirId INT NOT NULL,
        Ad NVARCHAR(50) NOT NULL,
        
        CONSTRAINT FK_ilce_sehir FOREIGN KEY (SehirId) REFERENCES tnm_sehir(Id)
    );
    PRINT 'tnm_ilce tablosu olusturuldu.';
END
ELSE
BEGIN
    PRINT 'tnm_ilce tablosu zaten mevcut.';
END
GO

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_tnm_ilce_SehirId' AND object_id = OBJECT_ID('tnm_ilce'))
    CREATE NONCLUSTERED INDEX IX_tnm_ilce_SehirId ON tnm_ilce(SehirId);
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_tnm_ilce_Ad' AND object_id = OBJECT_ID('tnm_ilce'))
    CREATE NONCLUSTERED INDEX IX_tnm_ilce_Ad ON tnm_ilce(Ad);
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_tnm_ilce_Sil' AND object_id = OBJECT_ID('tnm_ilce'))
    CREATE NONCLUSTERED INDEX IX_tnm_ilce_Sil ON tnm_ilce(Sil);
GO

PRINT '======================================';
PRINT 'tnm_ilce tablosu migration tamamlandi.';
PRINT '======================================';
GO
