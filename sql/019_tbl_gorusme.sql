-- Görüşme Ana Tablosu
CREATE TABLE tbl_gorusme (
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
    
    -- Görüşme Bilgileri
    Tarih DATE NOT NULL,
    Konu NVARCHAR(255) NOT NULL,
    Notlar NVARCHAR(MAX) NULL,
    Kisi NVARCHAR(255) NULL,
    Eposta NVARCHAR(255) NULL,
    Telefon NVARCHAR(50) NULL,
    
    CONSTRAINT FK_gorusme_musteri FOREIGN KEY (MusteriId) REFERENCES tbl_musteri(Id),
    CONSTRAINT FK_gorusme_proje FOREIGN KEY (ProjeId) REFERENCES tbl_proje(Id)
);
GO

CREATE INDEX IX_tbl_gorusme_MusteriId ON tbl_gorusme(MusteriId);
CREATE INDEX IX_tbl_gorusme_ProjeId ON tbl_gorusme(ProjeId);
CREATE INDEX IX_tbl_gorusme_Tarih ON tbl_gorusme(Tarih);
CREATE INDEX IX_tbl_gorusme_Sil ON tbl_gorusme(Sil);
GO
