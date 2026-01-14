-- Görüşme tablosuna Eposta ve Telefon alanları ekleme
IF NOT EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('dbo.tbl_gorusme') AND name = 'Eposta')
BEGIN
    ALTER TABLE dbo.tbl_gorusme ADD Eposta NVARCHAR(255) NULL;
END
GO

IF NOT EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('dbo.tbl_gorusme') AND name = 'Telefon')
BEGIN
    ALTER TABLE dbo.tbl_gorusme ADD Telefon NVARCHAR(50) NULL;
END
GO
