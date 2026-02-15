-- bck_tbl_takvim tablosuna KaynakTuru ve OrijinalKaynakId kolonları ekleme
-- tbl_takvim tablosuna 040_alter_takvim_kaynak.sql ile eklenen
-- KaynakTuru ve KaynakId kolonlarının backup tablosunda da olması gerekiyor.
-- Not: bck_tbl_takvim zaten "KaynakId" adında bir backup FK kolonu kullanıyor,
-- bu nedenle tbl_takvim'deki kaynak entity KaynakId'si "OrijinalKaynakId" olarak saklanır.

IF NOT EXISTS (
    SELECT 1 FROM sys.columns
    WHERE object_id = OBJECT_ID('bck_tbl_takvim') AND name = 'KaynakTuru'
)
BEGIN
    ALTER TABLE bck_tbl_takvim ADD KaynakTuru NVARCHAR(50) NULL;
END
GO

IF NOT EXISTS (
    SELECT 1 FROM sys.columns
    WHERE object_id = OBJECT_ID('bck_tbl_takvim') AND name = 'OrijinalKaynakId'
)
BEGIN
    ALTER TABLE bck_tbl_takvim ADD OrijinalKaynakId INT NULL;
END
GO
