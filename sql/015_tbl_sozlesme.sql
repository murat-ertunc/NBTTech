-- Sözleşme Ana Tablosu
CREATE TABLE tbl_sozlesme (
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
    TeklifId INT NULL,
    
    -- Sözleşme Bilgileri
    SozlesmeTarihi DATE NULL,
    Tutar DECIMAL(16,2) DEFAULT 0.00,
    ParaBirimi NVARCHAR(3) DEFAULT 'TRY',
    Durum TINYINT DEFAULT 1,
    
    -- Dosya Bilgileri
    DosyaAdi NVARCHAR(255) NULL,
    DosyaYolu NVARCHAR(500) NULL,
    
    CONSTRAINT FK_sozlesme_musteri FOREIGN KEY (MusteriId) REFERENCES tbl_musteri(Id),
    CONSTRAINT FK_sozlesme_proje FOREIGN KEY (ProjeId) REFERENCES tbl_proje(Id),
    CONSTRAINT FK_sozlesme_teklif FOREIGN KEY (TeklifId) REFERENCES tbl_teklif(Id)
);
GO

CREATE INDEX IX_tbl_sozlesme_MusteriId ON tbl_sozlesme(MusteriId);
CREATE INDEX IX_tbl_sozlesme_ProjeId ON tbl_sozlesme(ProjeId);
CREATE INDEX IX_tbl_sozlesme_TeklifId ON tbl_sozlesme(TeklifId);
CREATE INDEX IX_tbl_sozlesme_Sil ON tbl_sozlesme(Sil);
GO
