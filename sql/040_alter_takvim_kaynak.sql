-- =============================================
-- TAKVIM TABLOSU KAYNAK ALANLARI
-- Takvim kaydının hangi kaynaktan oluşturulduğunu takip etmek için
-- =============================================

-- KaynakTuru: gorusme, teklif, sozlesme, damgavergisi, teminat, fatura, odeme, manuel
-- KaynakId: İlgili tablodaki kayıt ID'si

IF NOT EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('tbl_takvim') AND name = 'KaynakTuru')
BEGIN
    ALTER TABLE tbl_takvim ADD KaynakTuru NVARCHAR(50) NULL;
END
GO

IF NOT EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('tbl_takvim') AND name = 'KaynakId')
BEGIN
    ALTER TABLE tbl_takvim ADD KaynakId INT NULL;
END
GO

-- Index ekle
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE object_id = OBJECT_ID('tbl_takvim') AND name = 'IX_tbl_takvim_Kaynak')
BEGIN
    CREATE INDEX IX_tbl_takvim_Kaynak ON tbl_takvim(KaynakTuru, KaynakId);
END
GO
