-- Takvim tablosunda kolon isimlerini güncelle
-- BaslangicTarihi -> TerminTarihi
-- BitisTarihi kolonunu kaldır

-- Önce mevcut verileri kontrol et
IF EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('tbl_takvim') AND name = 'BaslangicTarihi')
BEGIN
    -- BaslangicTarihi'yi TerminTarihi olarak yeniden adlandır
    EXEC sp_rename 'tbl_takvim.BaslangicTarihi', 'TerminTarihi', 'COLUMN';
    PRINT 'BaslangicTarihi kolonu TerminTarihi olarak yeniden adlandırıldı.';
END
ELSE
BEGIN
    PRINT 'BaslangicTarihi kolonu bulunamadı, muhtemelen zaten güncellenmiş.';
END
GO

-- BitisTarihi kolonunu kaldır
IF EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('tbl_takvim') AND name = 'BitisTarihi')
BEGIN
    ALTER TABLE tbl_takvim DROP COLUMN BitisTarihi;
    PRINT 'BitisTarihi kolonu kaldırıldı.';
END
ELSE
BEGIN
    PRINT 'BitisTarihi kolonu bulunamadı, muhtemelen zaten kaldırılmış.';
END
GO

-- Backup tablosunda da aynı değişiklikleri yap
IF EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('bck_tbl_takvim') AND name = 'BaslangicTarihi')
BEGIN
    EXEC sp_rename 'bck_tbl_takvim.BaslangicTarihi', 'TerminTarihi', 'COLUMN';
    PRINT 'Backup tablosunda BaslangicTarihi kolonu TerminTarihi olarak yeniden adlandırıldı.';
END
GO

IF EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('bck_tbl_takvim') AND name = 'BitisTarihi')
BEGIN
    ALTER TABLE bck_tbl_takvim DROP COLUMN BitisTarihi;
    PRINT 'Backup tablosunda BitisTarihi kolonu kaldırıldı.';
END
GO

PRINT 'Takvim tabloları başarıyla güncellendi.';
