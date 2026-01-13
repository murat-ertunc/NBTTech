CREATE TABLE tbl_fatura (
    Id INT IDENTITY(1,1) PRIMARY KEY,
    Guid UNIQUEIDENTIFIER NOT NULL DEFAULT NEWID(),
    EklemeZamani DATETIME2(0) NOT NULL DEFAULT SYSUTCDATETIME(),
    EkleyenUserId INT NULL,
    DegisiklikZamani DATETIME2(0) NOT NULL DEFAULT SYSUTCDATETIME(),
    DegistirenUserId INT NULL,
    Sil BIT NOT NULL DEFAULT 0,
    
    MusteriId INT NOT NULL,
    Tarih DATE NOT NULL,
    Tutar DECIMAL(16,2) NOT NULL DEFAULT 0,
    DovizCinsi NVARCHAR(3) NOT NULL DEFAULT 'TL',
    Aciklama NVARCHAR(MAX) NULL
);

CREATE INDEX IX_tbl_fatura_MusteriId ON tbl_fatura (MusteriId);
CREATE INDEX IX_tbl_fatura_Tarih ON tbl_fatura (Tarih);
CREATE INDEX IX_tbl_fatura_Sil ON tbl_fatura (Sil);
