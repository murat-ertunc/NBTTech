-- Müşteri tablosuna SehirId ve IlceId FK alanları ekleme
-- Mevcut Il/Ilce text alanları backward compatibility için korunuyor

-- SehirId kolonu ekle
IF NOT EXISTS (
    SELECT 1 FROM sys.columns
    WHERE object_id = OBJECT_ID('tbl_musteri') AND name = 'SehirId'
)
BEGIN
    ALTER TABLE tbl_musteri ADD SehirId INT NULL;
END
GO

-- IlceId kolonu ekle
IF NOT EXISTS (
    SELECT 1 FROM sys.columns
    WHERE object_id = OBJECT_ID('tbl_musteri') AND name = 'IlceId'
)
BEGIN
    ALTER TABLE tbl_musteri ADD IlceId INT NULL;
END
GO

-- SehirId için index ekle
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_tbl_musteri_SehirId' AND object_id = OBJECT_ID('tbl_musteri'))
    CREATE NONCLUSTERED INDEX IX_tbl_musteri_SehirId ON tbl_musteri(SehirId);
GO

-- IlceId için index ekle
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_tbl_musteri_IlceId' AND object_id = OBJECT_ID('tbl_musteri'))
    CREATE NONCLUSTERED INDEX IX_tbl_musteri_IlceId ON tbl_musteri(IlceId);
GO
