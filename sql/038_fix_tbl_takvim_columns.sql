-- Takvim tablosundaki kolon isimlerini standart hale getir
-- BaseRepository'nin beklediği standart alan isimleri: Guid, EklemeZamani, EkleyenUserId, DegisiklikZamani, DegistirenUserId, Sil

IF NOT EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('dbo.tbl_takvim') AND name = 'Guid')
BEGIN
    ALTER TABLE dbo.tbl_takvim ADD Guid UNIQUEIDENTIFIER DEFAULT NEWID();
    PRINT 'tbl_takvim tablosuna Guid kolonu eklendi';
END
GO

IF EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('dbo.tbl_takvim') AND name = 'OlusturmaZamani')
AND NOT EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('dbo.tbl_takvim') AND name = 'EklemeZamani')
BEGIN
    EXEC sp_rename 'tbl_takvim.OlusturmaZamani', 'EklemeZamani', 'COLUMN';
    PRINT 'tbl_takvim: OlusturmaZamani -> EklemeZamani olarak değiştirildi';
END
GO

IF EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('dbo.tbl_takvim') AND name = 'OlusturanUserId')
AND NOT EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('dbo.tbl_takvim') AND name = 'EkleyenUserId')
BEGIN
    EXEC sp_rename 'tbl_takvim.OlusturanUserId', 'EkleyenUserId', 'COLUMN';
    PRINT 'tbl_takvim: OlusturanUserId -> EkleyenUserId olarak değiştirildi';
END
GO
