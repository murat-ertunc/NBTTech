-- Kişi (Müşteri İletişim Kişisi) Ana Tablosu
CREATE TABLE tbl_kisi (
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
    
    -- Kişi Bilgileri
    AdSoyad NVARCHAR(255) NOT NULL,
    Unvan NVARCHAR(255) NULL,
    Telefon NVARCHAR(50) NULL,
    DahiliNo NVARCHAR(50) NULL,
    Email NVARCHAR(255) NULL,
    Notlar NVARCHAR(MAX) NULL,
    
    CONSTRAINT FK_kisi_musteri FOREIGN KEY (MusteriId) REFERENCES tbl_musteri(Id),
    CONSTRAINT FK_kisi_proje FOREIGN KEY (ProjeId) REFERENCES tbl_proje(Id)
);
GO

CREATE INDEX IX_tbl_kisi_MusteriId ON tbl_kisi(MusteriId);
CREATE INDEX IX_tbl_kisi_ProjeId ON tbl_kisi(ProjeId);
CREATE INDEX IX_tbl_kisi_Sil ON tbl_kisi(Sil);
GO
