-- Sözleşme tablosunda kolon isimlerini güncelle
-- BaslangicTarihi -> SozlesmeTarihi
-- BitisTarihi kolonunu kaldır

-- Önce mevcut verileri kontrol et
IF EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('tbl_sozlesme') AND name = 'BaslangicTarihi')
BEGIN
    -- BaslangicTarihi'yi SozlesmeTarihi olarak yeniden adlandır
    EXEC sp_rename 'tbl_sozlesme.BaslangicTarihi', 'SozlesmeTarihi', 'COLUMN';
    PRINT 'BaslangicTarihi kolonu SozlesmeTarihi olarak yeniden adlandırıldı.';
END
ELSE
BEGIN
    PRINT 'BaslangicTarihi kolonu bulunamadı, muhtemelen zaten güncellenmiş.';
END
GO

-- BitisTarihi kolonunu kaldır
IF EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('tbl_sozlesme') AND name = 'BitisTarihi')
BEGIN
    ALTER TABLE tbl_sozlesme DROP COLUMN BitisTarihi;
    PRINT 'BitisTarihi kolonu kaldırıldı.';
END
ELSE
BEGIN
    PRINT 'BitisTarihi kolonu bulunamadı, muhtemelen zaten kaldırılmış.';
END
GO

-- Backup tablosunda da aynı değişiklikleri yap
IF EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('bck_tbl_sozlesme') AND name = 'BaslangicTarihi')
BEGIN
    EXEC sp_rename 'bck_tbl_sozlesme.BaslangicTarihi', 'SozlesmeTarihi', 'COLUMN';
    PRINT 'Backup tablosunda BaslangicTarihi kolonu SozlesmeTarihi olarak yeniden adlandırıldı.';
END
GO

IF EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('bck_tbl_sozlesme') AND name = 'BitisTarihi')
BEGIN
    ALTER TABLE bck_tbl_sozlesme DROP COLUMN BitisTarihi;
    PRINT 'Backup tablosunda BitisTarihi kolonu kaldırıldı.';
END
GO

PRINT 'Sözleşme tabloları başarıyla güncellendi.';
