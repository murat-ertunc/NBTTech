CREATE TABLE tbl_odeme (
    Id INT IDENTITY(1,1) PRIMARY KEY,
    Guid UNIQUEIDENTIFIER NOT NULL DEFAULT NEWID(),
    EklemeZamani DATETIME2(0) NOT NULL DEFAULT SYSUTCDATETIME(),
    EkleyenUserId INT NULL,
    DegisiklikZamani DATETIME2(0) NOT NULL DEFAULT SYSUTCDATETIME(),
    DegistirenUserId INT NULL,
    Sil BIT NOT NULL DEFAULT 0,
    
    MusteriId INT NOT NULL,
    FaturaId INT NULL, -- Hangi fatura icin odeme yapildi (opsiyonel)
    Tarih DATE NOT NULL,
    Tutar DECIMAL(16,2) NOT NULL DEFAULT 0,
    Aciklama NVARCHAR(MAX) NULL
);

CREATE INDEX IX_tbl_odeme_MusteriId ON tbl_odeme (MusteriId);
CREATE INDEX IX_tbl_odeme_FaturaId ON tbl_odeme (FaturaId);
CREATE INDEX IX_tbl_odeme_Tarih ON tbl_odeme (Tarih);
CREATE INDEX IX_tbl_odeme_Sil ON tbl_odeme (Sil);
