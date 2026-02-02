-- =============================================
-- User-Rol Pivot Tablosu
-- =============================================
-- Hangi kullanicinin hangi rollere sahip oldugunu tutar
-- Bir kullanici birden fazla role sahip olabilir

IF OBJECT_ID('tnm_user_rol', 'U') IS NULL
BEGIN
    CREATE TABLE tnm_user_rol (
        -- Standart Alanlar
        Id                  INT IDENTITY(1,1) PRIMARY KEY,
        Guid                UNIQUEIDENTIFIER NOT NULL DEFAULT NEWID(),
        EklemeZamani        DATETIME2(0) NOT NULL DEFAULT SYSUTCDATETIME(),
        EkleyenUserId       INT NULL,
        DegisiklikZamani    DATETIME2(0) NOT NULL DEFAULT SYSUTCDATETIME(),
        DegistirenUserId    INT NULL,
        Sil                 BIT NOT NULL DEFAULT 0,

        -- Iliski Alanlari
        UserId              INT NOT NULL,
        RolId               INT NOT NULL,

        -- Foreign Keys
        CONSTRAINT FK_tnm_user_rol_User FOREIGN KEY (UserId)
            REFERENCES tnm_user(Id),
        CONSTRAINT FK_tnm_user_rol_Rol FOREIGN KEY (RolId)
            REFERENCES tnm_rol(Id)
    );

    -- Unique constraint: Ayni user-rol kombinasyonu tekrar edemez
    CREATE UNIQUE NONCLUSTERED INDEX IX_tnm_user_rol_Unique
        ON tnm_user_rol(UserId, RolId) WHERE Sil = 0;

    CREATE NONCLUSTERED INDEX IX_tnm_user_rol_UserId ON tnm_user_rol(UserId);
    CREATE NONCLUSTERED INDEX IX_tnm_user_rol_RolId ON tnm_user_rol(RolId);
    CREATE NONCLUSTERED INDEX IX_tnm_user_rol_Sil ON tnm_user_rol(Sil);

    PRINT 'tnm_user_rol tablosu olusturuldu.';
END
ELSE
BEGIN
    PRINT 'tnm_user_rol tablosu zaten mevcut.';
END
GO
