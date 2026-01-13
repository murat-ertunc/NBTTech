-- log_action tablosu sema duzeltmesi
-- Varlik -> Tablo, Veri -> YeniDeger, KayitId ve EskiDeger eklendi
-- Bu migration mevcut veritabaninı yeni semaya guncellemek icin

-- Kolon isimlerini guncelle (eger hala eski isimler varsa)
IF EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'log_action' AND COLUMN_NAME = 'Varlik')
BEGIN
    EXEC sp_rename 'log_action.Varlik', 'Tablo', 'COLUMN';
END

IF EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'log_action' AND COLUMN_NAME = 'Veri')
BEGIN
    EXEC sp_rename 'log_action.Veri', 'YeniDeger', 'COLUMN';
END

-- Eksik kolonlari ekle
IF NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'log_action' AND COLUMN_NAME = 'KayitId')
BEGIN
    ALTER TABLE log_action ADD KayitId INT NULL;
END

IF NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'log_action' AND COLUMN_NAME = 'EskiDeger')
BEGIN
    ALTER TABLE log_action ADD EskiDeger NVARCHAR(MAX) NULL;
END

-- Index'leri ekle (eger yoksa)
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_log_action_Tablo' AND object_id = OBJECT_ID('log_action'))
BEGIN
    CREATE INDEX IX_log_action_Tablo ON log_action (Tablo);
END

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_log_action_KayitId' AND object_id = OBJECT_ID('log_action'))
BEGIN
    CREATE INDEX IX_log_action_KayitId ON log_action (KayitId);
END

PRINT 'log_action sema duzeltmesi tamamlandi';
