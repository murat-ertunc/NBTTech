CREATE TABLE bck_tbl_musteri (
    Id INT IDENTITY(1,1) PRIMARY KEY,
    KaynakId INT NOT NULL,
    Guid UNIQUEIDENTIFIER NOT NULL,
    EklemeZamani DATETIME2(0) NOT NULL,
    EkleyenUserId INT NULL,
    DegisiklikZamani DATETIME2(0) NOT NULL,
    DegistirenUserId INT NULL,
    Sil BIT NOT NULL,
    Unvan NVARCHAR(255) NOT NULL,
    Aciklama NVARCHAR(MAX) NULL,
    BackupZamani DATETIME2(0) NOT NULL DEFAULT SYSUTCDATETIME(),
    BackupUserId INT NULL
);

CREATE INDEX IX_bck_tbl_musteri_KaynakId ON bck_tbl_musteri (KaynakId);
