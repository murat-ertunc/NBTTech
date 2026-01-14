-- Fatura Kalemleri Tablosu
CREATE TABLE tbl_fatura_kalem (
    Id INT IDENTITY(1,1) PRIMARY KEY,
    Guid UNIQUEIDENTIFIER NOT NULL DEFAULT NEWID(),
    EklemeZamani DATETIME2(0) NOT NULL DEFAULT SYSUTCDATETIME(),
    EkleyenUserId INT NULL,
    DegisiklikZamani DATETIME2(0) NOT NULL DEFAULT SYSUTCDATETIME(),
    DegistirenUserId INT NULL,
    Sil BIT NOT NULL DEFAULT 0,
    
    -- İlişkiler
    FaturaId INT NOT NULL,
    
    -- Kalem Bilgileri
    Sira INT NOT NULL DEFAULT 1,
    Miktar DECIMAL(10,2) NOT NULL DEFAULT 0,
    Aciklama NVARCHAR(500) NULL,
    KdvOran DECIMAL(5,2) NOT NULL DEFAULT 0,
    BirimFiyat DECIMAL(18,2) NOT NULL DEFAULT 0,
    Tutar DECIMAL(18,2) NOT NULL DEFAULT 0,
    
    CONSTRAINT FK_fatura_kalem_fatura FOREIGN KEY (FaturaId) REFERENCES tbl_fatura(Id)
);
GO

CREATE INDEX IX_tbl_fatura_kalem_FaturaId ON tbl_fatura_kalem(FaturaId);
CREATE INDEX IX_tbl_fatura_kalem_Sil ON tbl_fatura_kalem(Sil);
GO
