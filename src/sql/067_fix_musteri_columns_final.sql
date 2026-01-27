-- =============================================
-- FIX: Müşteri tablosu Il/Ilce kolonları - Nihai düzeltme
-- Bu migration mevcut tablolara eksik kolonları ekler
-- Idempotent: Tekrar çalıştırılsa bile hata vermez
-- =============================================

-- 1. Ana tabloya Il kolonu ekle (yoksa)
IF NOT EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('tbl_musteri') AND name = 'Il')
BEGIN
    ALTER TABLE tbl_musteri ADD Il NVARCHAR(50) NULL;
    PRINT 'tbl_musteri.Il kolonu eklendi.';
END
GO

-- 2. Ana tabloya Ilce kolonu ekle (yoksa)
IF NOT EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('tbl_musteri') AND name = 'Ilce')
BEGIN
    ALTER TABLE tbl_musteri ADD Ilce NVARCHAR(50) NULL;
    PRINT 'tbl_musteri.Ilce kolonu eklendi.';
END
GO

-- 3. Backup tabloya Il kolonu ekle (yoksa)
IF EXISTS (SELECT 1 FROM sys.tables WHERE name = 'bck_tbl_musteri')
   AND NOT EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('bck_tbl_musteri') AND name = 'Il')
BEGIN
    ALTER TABLE bck_tbl_musteri ADD Il NVARCHAR(50) NULL;
    PRINT 'bck_tbl_musteri.Il kolonu eklendi.';
END
GO

-- 4. Backup tabloya Ilce kolonu ekle (yoksa)
IF EXISTS (SELECT 1 FROM sys.tables WHERE name = 'bck_tbl_musteri')
   AND NOT EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('bck_tbl_musteri') AND name = 'Ilce')
BEGIN
    ALTER TABLE bck_tbl_musteri ADD Ilce NVARCHAR(50) NULL;
    PRINT 'bck_tbl_musteri.Ilce kolonu eklendi.';
END
GO

-- 5. Kontrol: Kolonların varlığını doğrula
SELECT 
    'tbl_musteri' AS Tablo,
    c.name AS Kolon,
    t.name AS VeriTipi,
    c.max_length AS MaxUzunluk
FROM sys.columns c
INNER JOIN sys.types t ON c.user_type_id = t.user_type_id
WHERE c.object_id = OBJECT_ID('tbl_musteri')
  AND c.name IN ('Il', 'Ilce')
ORDER BY c.name;
GO

PRINT '======================================';
PRINT 'Migration 067 tamamlandi.';
PRINT 'tbl_musteri tablosunda Il ve Ilce kolonlari hazir.';
PRINT '======================================';
GO
