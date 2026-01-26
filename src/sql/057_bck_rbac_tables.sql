-- =============================================
-- Backup Tablolari - Rol ve Permission
-- =============================================
-- kurallar.txt: Her tablo icin bck_ prefix ile backup tablosu

-- Backup: tnm_rol
IF OBJECT_ID('bck_tnm_rol', 'U') IS NULL
BEGIN
    CREATE TABLE bck_tnm_rol (
        BackupId            INT IDENTITY(1,1) PRIMARY KEY,
        BackupZamani        DATETIME2(0) NOT NULL DEFAULT SYSUTCDATETIME(),
        BackupIslemTipi     NVARCHAR(10) NOT NULL, -- UPDATE, DELETE
        
        -- Original table columns
        Id                  INT NOT NULL,
        Guid                UNIQUEIDENTIFIER NOT NULL,
        EklemeZamani        DATETIME2(0) NOT NULL,
        EkleyenUserId       INT NULL,
        DegisiklikZamani    DATETIME2(0) NOT NULL,
        DegistirenUserId    INT NULL,
        Sil                 BIT NOT NULL,
        RolAdi              NVARCHAR(50) NOT NULL,
        RolKodu             NVARCHAR(30) NOT NULL,
        Aciklama            NVARCHAR(250) NULL,
        Seviye              INT NOT NULL,
        SistemRolu          BIT NOT NULL,
        Aktif               BIT NOT NULL
    );
    
    CREATE NONCLUSTERED INDEX IX_bck_tnm_rol_Id ON bck_tnm_rol(Id);
    CREATE NONCLUSTERED INDEX IX_bck_tnm_rol_BackupZamani ON bck_tnm_rol(BackupZamani);
    
    PRINT 'bck_tnm_rol tablosu olusturuldu.';
END
GO

-- Backup: tnm_permission
IF OBJECT_ID('bck_tnm_permission', 'U') IS NULL
BEGIN
    CREATE TABLE bck_tnm_permission (
        BackupId            INT IDENTITY(1,1) PRIMARY KEY,
        BackupZamani        DATETIME2(0) NOT NULL DEFAULT SYSUTCDATETIME(),
        BackupIslemTipi     NVARCHAR(10) NOT NULL,
        
        Id                  INT NOT NULL,
        Guid                UNIQUEIDENTIFIER NOT NULL,
        EklemeZamani        DATETIME2(0) NOT NULL,
        EkleyenUserId       INT NULL,
        DegisiklikZamani    DATETIME2(0) NOT NULL,
        DegistirenUserId    INT NULL,
        Sil                 BIT NOT NULL,
        PermissionKodu      NVARCHAR(100) NOT NULL,
        ModulAdi            NVARCHAR(50) NOT NULL,
        Aksiyon             NVARCHAR(30) NOT NULL,
        Aciklama            NVARCHAR(250) NULL,
        Aktif               BIT NOT NULL
    );
    
    CREATE NONCLUSTERED INDEX IX_bck_tnm_permission_Id ON bck_tnm_permission(Id);
    CREATE NONCLUSTERED INDEX IX_bck_tnm_permission_BackupZamani ON bck_tnm_permission(BackupZamani);
    
    PRINT 'bck_tnm_permission tablosu olusturuldu.';
END
GO

-- Backup: tnm_rol_permission
IF OBJECT_ID('bck_tnm_rol_permission', 'U') IS NULL
BEGIN
    CREATE TABLE bck_tnm_rol_permission (
        BackupId            INT IDENTITY(1,1) PRIMARY KEY,
        BackupZamani        DATETIME2(0) NOT NULL DEFAULT SYSUTCDATETIME(),
        BackupIslemTipi     NVARCHAR(10) NOT NULL,
        
        Id                  INT NOT NULL,
        Guid                UNIQUEIDENTIFIER NOT NULL,
        EklemeZamani        DATETIME2(0) NOT NULL,
        EkleyenUserId       INT NULL,
        DegisiklikZamani    DATETIME2(0) NOT NULL,
        DegistirenUserId    INT NULL,
        Sil                 BIT NOT NULL,
        RolId               INT NOT NULL,
        PermissionId        INT NOT NULL
    );
    
    CREATE NONCLUSTERED INDEX IX_bck_tnm_rol_permission_Id ON bck_tnm_rol_permission(Id);
    CREATE NONCLUSTERED INDEX IX_bck_tnm_rol_permission_BackupZamani ON bck_tnm_rol_permission(BackupZamani);
    
    PRINT 'bck_tnm_rol_permission tablosu olusturuldu.';
END
GO

-- Backup: tnm_user_rol
IF OBJECT_ID('bck_tnm_user_rol', 'U') IS NULL
BEGIN
    CREATE TABLE bck_tnm_user_rol (
        BackupId            INT IDENTITY(1,1) PRIMARY KEY,
        BackupZamani        DATETIME2(0) NOT NULL DEFAULT SYSUTCDATETIME(),
        BackupIslemTipi     NVARCHAR(10) NOT NULL,
        
        Id                  INT NOT NULL,
        Guid                UNIQUEIDENTIFIER NOT NULL,
        EklemeZamani        DATETIME2(0) NOT NULL,
        EkleyenUserId       INT NULL,
        DegisiklikZamani    DATETIME2(0) NOT NULL,
        DegistirenUserId    INT NULL,
        Sil                 BIT NOT NULL,
        UserId              INT NOT NULL,
        RolId               INT NOT NULL
    );
    
    CREATE NONCLUSTERED INDEX IX_bck_tnm_user_rol_Id ON bck_tnm_user_rol(Id);
    CREATE NONCLUSTERED INDEX IX_bck_tnm_user_rol_BackupZamani ON bck_tnm_user_rol(BackupZamani);
    
    PRINT 'bck_tnm_user_rol tablosu olusturuldu.';
END
GO

PRINT 'Tum backup tablolari olusturuldu.';
GO
