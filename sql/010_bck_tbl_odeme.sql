CREATE TABLE bck_tbl_odeme (
    Id INT IDENTITY(1,1) PRIMARY KEY,
    KaynakId INT NOT NULL,
    BackupZamani DATETIME2(0) NOT NULL DEFAULT SYSUTCDATETIME(),
    BackupUserId INT NULL,
    
    Guid UNIQUEIDENTIFIER NOT NULL,
    EklemeZamani DATETIME2(0) NOT NULL,
    EkleyenUserId INT NULL,
    DegisiklikZamani DATETIME2(0) NOT NULL,
    DegistirenUserId INT NULL,
    Sil BIT NOT NULL,
    
    MusteriId INT NOT NULL,
    FaturaId INT NULL,
    Tarih DATE NOT NULL,
    Tutar DECIMAL(16,2) NOT NULL,
    Aciklama NVARCHAR(MAX) NULL
);

CREATE INDEX IX_bck_tbl_odeme_KaynakId ON bck_tbl_odeme (KaynakId);
