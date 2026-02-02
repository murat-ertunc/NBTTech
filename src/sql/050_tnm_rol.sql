-- =============================================
-- Rol Tanimlari Tablosu (RBAC)
-- =============================================
-- Prefix: tnm_ (tanim tablosu)
-- Kurallar.txt uyumlu: 7 standart alan, PascalCase, Turkce

IF OBJECT_ID('tnm_rol', 'U') IS NULL
BEGIN
    CREATE TABLE tnm_rol (
        -- Standart Alanlar
        Id                  INT IDENTITY(1,1) PRIMARY KEY,
        Guid                UNIQUEIDENTIFIER NOT NULL DEFAULT NEWID(),
        EklemeZamani        DATETIME2(0) NOT NULL DEFAULT SYSUTCDATETIME(),
        EkleyenUserId       INT NULL,
        DegisiklikZamani    DATETIME2(0) NOT NULL DEFAULT SYSUTCDATETIME(),
        DegistirenUserId    INT NULL,
        Sil                 BIT NOT NULL DEFAULT 0,

        -- Rol Bilgileri
        RolAdi              NVARCHAR(50) NOT NULL,
        RolKodu             NVARCHAR(30) NOT NULL,       -- Teknik kod: admin, editor, viewer
        Aciklama            NVARCHAR(250) NULL,
        Seviye              INT NOT NULL DEFAULT 0,      -- Hiyerarsi seviyesi (yuksek=yetkili)
        SistemRolu          BIT NOT NULL DEFAULT 0,      -- Sistem rolleri silinemez/duzenlenemez
        Aktif               BIT NOT NULL DEFAULT 1
    );

    -- Indexler
    CREATE UNIQUE NONCLUSTERED INDEX IX_tnm_rol_RolKodu ON tnm_rol(RolKodu) WHERE Sil = 0;
    CREATE NONCLUSTERED INDEX IX_tnm_rol_Sil ON tnm_rol(Sil);
    CREATE NONCLUSTERED INDEX IX_tnm_rol_Aktif ON tnm_rol(Aktif);
    CREATE NONCLUSTERED INDEX IX_tnm_rol_Seviye ON tnm_rol(Seviye);

    PRINT 'tnm_rol tablosu olusturuldu.';
END
ELSE
BEGIN
    PRINT 'tnm_rol tablosu zaten mevcut.';
END
GO
