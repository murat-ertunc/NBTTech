-- bck_tbl_musteri tablosuna SehirId ve IlceId kolonları ekleme
-- tbl_musteri tablosuna 070_alter_musteri_sehir_ilce.sql ile eklenen
-- SehirId/IlceId kolonlarının backup tablosunda da olması gerekiyor.

IF NOT EXISTS (
    SELECT 1 FROM sys.columns
    WHERE object_id = OBJECT_ID('bck_tbl_musteri') AND name = 'SehirId'
)
BEGIN
    ALTER TABLE bck_tbl_musteri ADD SehirId INT NULL;
END
GO

IF NOT EXISTS (
    SELECT 1 FROM sys.columns
    WHERE object_id = OBJECT_ID('bck_tbl_musteri') AND name = 'IlceId'
)
BEGIN
    ALTER TABLE bck_tbl_musteri ADD IlceId INT NULL;
END
GO
