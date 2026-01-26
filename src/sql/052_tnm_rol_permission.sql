-- =============================================
-- Rol-Permission Pivot Tablosu
-- =============================================
-- Hangi rolun hangi permissionlara sahip oldugunu tutar

IF OBJECT_ID('tnm_rol_permission', 'U') IS NULL
BEGIN
    CREATE TABLE tnm_rol_permission (
        -- Standart Alanlar
        Id                  INT IDENTITY(1,1) PRIMARY KEY,
        Guid                UNIQUEIDENTIFIER NOT NULL DEFAULT NEWID(),
        EklemeZamani        DATETIME2(0) NOT NULL DEFAULT SYSUTCDATETIME(),
        EkleyenUserId       INT NULL,
        DegisiklikZamani    DATETIME2(0) NOT NULL DEFAULT SYSUTCDATETIME(),
        DegistirenUserId    INT NULL,
        Sil                 BIT NOT NULL DEFAULT 0,
        
        -- Iliski Alanlari
        RolId               INT NOT NULL,
        PermissionId        INT NOT NULL,
        
        -- Foreign Keys
        CONSTRAINT FK_tnm_rol_permission_Rol FOREIGN KEY (RolId) 
            REFERENCES tnm_rol(Id),
        CONSTRAINT FK_tnm_rol_permission_Permission FOREIGN KEY (PermissionId) 
            REFERENCES tnm_permission(Id)
    );
    
    -- Unique constraint: Ayni rol-permission kombinasyonu tekrar edemez
    CREATE UNIQUE NONCLUSTERED INDEX IX_tnm_rol_permission_Unique 
        ON tnm_rol_permission(RolId, PermissionId) WHERE Sil = 0;
    
    CREATE NONCLUSTERED INDEX IX_tnm_rol_permission_RolId ON tnm_rol_permission(RolId);
    CREATE NONCLUSTERED INDEX IX_tnm_rol_permission_PermissionId ON tnm_rol_permission(PermissionId);
    CREATE NONCLUSTERED INDEX IX_tnm_rol_permission_Sil ON tnm_rol_permission(Sil);
    
    PRINT 'tnm_rol_permission tablosu olusturuldu.';
END
ELSE
BEGIN
    PRINT 'tnm_rol_permission tablosu zaten mevcut.';
END
GO
