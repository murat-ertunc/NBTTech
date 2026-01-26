-- =============================================
-- Permission (Yetki) Tanimlari Tablosu
-- =============================================
-- Format: {modul}.{aksiyon}
-- Ornek: users.create, invoices.read, logs.read

IF OBJECT_ID('tnm_permission', 'U') IS NULL
BEGIN
    CREATE TABLE tnm_permission (
        -- Standart Alanlar
        Id                  INT IDENTITY(1,1) PRIMARY KEY,
        Guid                UNIQUEIDENTIFIER NOT NULL DEFAULT NEWID(),
        EklemeZamani        DATETIME2(0) NOT NULL DEFAULT SYSUTCDATETIME(),
        EkleyenUserId       INT NULL,
        DegisiklikZamani    DATETIME2(0) NOT NULL DEFAULT SYSUTCDATETIME(),
        DegistirenUserId    INT NULL,
        Sil                 BIT NOT NULL DEFAULT 0,
        
        -- Permission Bilgileri
        PermissionKodu      NVARCHAR(100) NOT NULL,      -- users.create, invoices.read
        ModulAdi            NVARCHAR(50) NOT NULL,       -- users, invoices, logs
        Aksiyon             NVARCHAR(30) NOT NULL,       -- create, read, update, delete
        Aciklama            NVARCHAR(250) NULL,
        Aktif               BIT NOT NULL DEFAULT 1
    );
    
    -- Indexler
    CREATE UNIQUE NONCLUSTERED INDEX IX_tnm_permission_Kodu ON tnm_permission(PermissionKodu) WHERE Sil = 0;
    CREATE NONCLUSTERED INDEX IX_tnm_permission_Modul ON tnm_permission(ModulAdi) WHERE Sil = 0;
    CREATE NONCLUSTERED INDEX IX_tnm_permission_Sil ON tnm_permission(Sil);
    
    PRINT 'tnm_permission tablosu olusturuldu.';
END
ELSE
BEGIN
    PRINT 'tnm_permission tablosu zaten mevcut.';
END
GO
