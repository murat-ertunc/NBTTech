-- tbl_gorusme tablosunu standart alan adlari ile yeniden olustur
IF OBJECT_ID('dbo.tbl_gorusme', 'U') IS NOT NULL
BEGIN
    DROP TABLE dbo.tbl_gorusme;
END
GO

CREATE TABLE dbo.tbl_gorusme (
    Id INT IDENTITY(1,1) PRIMARY KEY,
    Guid UNIQUEIDENTIFIER DEFAULT NEWID(),
    MusteriId INT NOT NULL,
    Tarih DATE NOT NULL,
    Konu NVARCHAR(255) NOT NULL,
    Notlar NVARCHAR(MAX) NULL,
    Kisi NVARCHAR(255) NULL,
    EklemeZamani DATETIME2 DEFAULT GETDATE(),
    EkleyenUserId INT NULL,
    DegisiklikZamani DATETIME2 NULL,
    DegistirenUserId INT NULL,
    Sil BIT DEFAULT 0,
    CONSTRAINT FK_gorusme_musteri FOREIGN KEY (MusteriId) REFERENCES tbl_musteri(Id)
);
GO

-- tbl_kisi tablosunu standart alan adlari ile yeniden olustur
IF OBJECT_ID('dbo.tbl_kisi', 'U') IS NOT NULL
BEGIN
    DROP TABLE dbo.tbl_kisi;
END
GO

CREATE TABLE dbo.tbl_kisi (
    Id INT IDENTITY(1,1) PRIMARY KEY,
    Guid UNIQUEIDENTIFIER DEFAULT NEWID(),
    MusteriId INT NOT NULL,
    AdSoyad NVARCHAR(255) NOT NULL,
    Unvan NVARCHAR(255) NULL,
    Telefon NVARCHAR(50) NULL,
    Email NVARCHAR(255) NULL,
    Notlar NVARCHAR(MAX) NULL,
    EklemeZamani DATETIME2 DEFAULT GETDATE(),
    EkleyenUserId INT NULL,
    DegisiklikZamani DATETIME2 NULL,
    DegistirenUserId INT NULL,
    Sil BIT DEFAULT 0,
    CONSTRAINT FK_kisi_musteri FOREIGN KEY (MusteriId) REFERENCES tbl_musteri(Id)
);
GO

-- tbl_damgavergisi tablosunu standart alan adlari ile yeniden olustur
IF OBJECT_ID('dbo.tbl_damgavergisi', 'U') IS NOT NULL
BEGIN
    DROP TABLE dbo.tbl_damgavergisi;
END
GO

CREATE TABLE dbo.tbl_damgavergisi (
    Id INT IDENTITY(1,1) PRIMARY KEY,
    Guid UNIQUEIDENTIFIER DEFAULT NEWID(),
    MusteriId INT NOT NULL,
    Tarih DATE NOT NULL,
    Tutar DECIMAL(18,2) NOT NULL,
    DovizCinsi NVARCHAR(10) DEFAULT 'TRY',
    Aciklama NVARCHAR(500) NULL,
    BelgeNo NVARCHAR(100) NULL,
    EklemeZamani DATETIME2 DEFAULT GETDATE(),
    EkleyenUserId INT NULL,
    DegisiklikZamani DATETIME2 NULL,
    DegistirenUserId INT NULL,
    Sil BIT DEFAULT 0,
    CONSTRAINT FK_damgavergisi_musteri FOREIGN KEY (MusteriId) REFERENCES tbl_musteri(Id)
);
GO

-- bck_tbl_gorusme tablosunu yeniden olustur
IF OBJECT_ID('dbo.bck_tbl_gorusme', 'U') IS NOT NULL
BEGIN
    DROP TABLE dbo.bck_tbl_gorusme;
END
GO

CREATE TABLE dbo.bck_tbl_gorusme (
    BackupId INT IDENTITY(1,1) PRIMARY KEY,
    KaynakId INT NOT NULL,
    Guid UNIQUEIDENTIFIER NULL,
    MusteriId INT NULL,
    Tarih DATE NULL,
    Konu NVARCHAR(255) NULL,
    Notlar NVARCHAR(MAX) NULL,
    Kisi NVARCHAR(255) NULL,
    EklemeZamani DATETIME2 NULL,
    EkleyenUserId INT NULL,
    DegisiklikZamani DATETIME2 NULL,
    DegistirenUserId INT NULL,
    Sil BIT NULL,
    BackupZamani DATETIME2 DEFAULT GETDATE(),
    BackupUserId INT NULL
);
GO

-- bck_tbl_kisi tablosunu yeniden olustur
IF OBJECT_ID('dbo.bck_tbl_kisi', 'U') IS NOT NULL
BEGIN
    DROP TABLE dbo.bck_tbl_kisi;
END
GO

CREATE TABLE dbo.bck_tbl_kisi (
    BackupId INT IDENTITY(1,1) PRIMARY KEY,
    KaynakId INT NOT NULL,
    Guid UNIQUEIDENTIFIER NULL,
    MusteriId INT NULL,
    AdSoyad NVARCHAR(255) NULL,
    Unvan NVARCHAR(255) NULL,
    Telefon NVARCHAR(50) NULL,
    Email NVARCHAR(255) NULL,
    Notlar NVARCHAR(MAX) NULL,
    EklemeZamani DATETIME2 NULL,
    EkleyenUserId INT NULL,
    DegisiklikZamani DATETIME2 NULL,
    DegistirenUserId INT NULL,
    Sil BIT NULL,
    BackupZamani DATETIME2 DEFAULT GETDATE(),
    BackupUserId INT NULL
);
GO

-- bck_tbl_damgavergisi tablosunu yeniden olustur
IF OBJECT_ID('dbo.bck_tbl_damgavergisi', 'U') IS NOT NULL
BEGIN
    DROP TABLE dbo.bck_tbl_damgavergisi;
END
GO

CREATE TABLE dbo.bck_tbl_damgavergisi (
    BackupId INT IDENTITY(1,1) PRIMARY KEY,
    KaynakId INT NOT NULL,
    Guid UNIQUEIDENTIFIER NULL,
    MusteriId INT NULL,
    Tarih DATE NULL,
    Tutar DECIMAL(18,2) NULL,
    DovizCinsi NVARCHAR(10) NULL,
    Aciklama NVARCHAR(500) NULL,
    BelgeNo NVARCHAR(100) NULL,
    EklemeZamani DATETIME2 NULL,
    EkleyenUserId INT NULL,
    DegisiklikZamani DATETIME2 NULL,
    DegistirenUserId INT NULL,
    Sil BIT NULL,
    BackupZamani DATETIME2 DEFAULT GETDATE(),
    BackupUserId INT NULL
);
GO

PRINT 'Tablolar standart alan adlari ile yeniden olusturuldu';
GO
