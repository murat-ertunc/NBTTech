-- Dosya Ana Tablosu
CREATE TABLE tbl_dosya (
    Id INT IDENTITY(1,1) PRIMARY KEY,
    Guid UNIQUEIDENTIFIER NOT NULL DEFAULT NEWID(),
    EklemeZamani DATETIME2(0) NOT NULL DEFAULT SYSUTCDATETIME(),
    EkleyenUserId INT NULL,
    DegisiklikZamani DATETIME2(0) NOT NULL DEFAULT SYSUTCDATETIME(),
    DegistirenUserId INT NULL,
    Sil BIT NOT NULL DEFAULT 0,
    
    -- İlişkiler
    MusteriId INT NOT NULL,
    ProjeId INT NULL,
    FaturaId INT NULL,
    
    -- Dosya Bilgileri
    DosyaAdi NVARCHAR(255) NOT NULL,
    DosyaYolu NVARCHAR(500) NOT NULL,
    DosyaTipi NVARCHAR(100) NULL,
    DosyaBoyutu INT NULL,
    Aciklama NVARCHAR(500) NULL,
    
    CONSTRAINT FK_dosya_musteri FOREIGN KEY (MusteriId) REFERENCES tbl_musteri(Id),
    CONSTRAINT FK_dosya_proje FOREIGN KEY (ProjeId) REFERENCES tbl_proje(Id),
    CONSTRAINT FK_dosya_fatura FOREIGN KEY (FaturaId) REFERENCES tbl_fatura(Id)
);
GO

CREATE INDEX IX_tbl_dosya_MusteriId ON tbl_dosya(MusteriId);
CREATE INDEX IX_tbl_dosya_ProjeId ON tbl_dosya(ProjeId);
CREATE INDEX IX_tbl_dosya_FaturaId ON tbl_dosya(FaturaId);
CREATE INDEX IX_tbl_dosya_Sil ON tbl_dosya(Sil);
GO
