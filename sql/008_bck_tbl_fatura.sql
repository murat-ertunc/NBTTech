CREATE TABLE bck_tbl_fatura (
    Id INT IDENTITY(1,1) PRIMARY KEY,
    KaynakId INT NOT NULL, -- Orijinal kaydin Id si
    BackupZamani DATETIME2(0) NOT NULL DEFAULT SYSUTCDATETIME(),
    BackupUserId INT NULL,
    
    -- Orijinal tablodaki veriler
    Guid UNIQUEIDENTIFIER NOT NULL,
    EklemeZamani DATETIME2(0) NOT NULL,
    EkleyenUserId INT NULL,
    DegisiklikZamani DATETIME2(0) NOT NULL,
    DegistirenUserId INT NULL,
    Sil BIT NOT NULL,
    
    MusteriId INT NOT NULL,
    Tarih DATE NOT NULL,
    Tutar DECIMAL(16,2) NOT NULL,
    DovizCinsi NVARCHAR(3) NOT NULL,
    Aciklama NVARCHAR(MAX) NULL
);

CREATE INDEX IX_bck_tbl_fatura_KaynakId ON bck_tbl_fatura (KaynakId);
