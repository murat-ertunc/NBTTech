-- Fatura Kalemleri Tablosu
IF OBJECT_ID('dbo.tbl_fatura_kalem', 'U') IS NULL
BEGIN
    CREATE TABLE dbo.tbl_fatura_kalem (
        Id INT IDENTITY(1,1) PRIMARY KEY,
        FaturaId INT NOT NULL,
        Sira INT NOT NULL DEFAULT 1,
        Miktar DECIMAL(10,2) NOT NULL DEFAULT 0,
        Aciklama NVARCHAR(500) NULL,
        KdvOran DECIMAL(5,2) NOT NULL DEFAULT 0,
        BirimFiyat DECIMAL(18,2) NOT NULL DEFAULT 0,
        Tutar DECIMAL(18,2) NOT NULL DEFAULT 0,
        OlusturmaZamani DATETIME2 DEFAULT GETDATE(),
        OlusturanUserId INT NULL,
        DegisiklikZamani DATETIME2 NULL,
        DegistirenUserId INT NULL,
        Sil TINYINT DEFAULT 0,
        CONSTRAINT FK_fatura_kalem_fatura FOREIGN KEY (FaturaId) REFERENCES tbl_fatura(Id)
    );
END
GO

-- Index oluştur
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_fatura_kalem_faturaid' AND object_id = OBJECT_ID('dbo.tbl_fatura_kalem'))
BEGIN
    CREATE INDEX IX_fatura_kalem_faturaid ON dbo.tbl_fatura_kalem(FaturaId);
END
GO
