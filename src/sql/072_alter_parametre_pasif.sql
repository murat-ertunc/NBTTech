-- =============================================
-- tbl_parametre Pasif sütunu ekleme
-- Proje durumlarının select listelerinde görünüp görünmemesini kontrol eder
-- =============================================

IF NOT EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_NAME = 'tbl_parametre' AND COLUMN_NAME = 'Pasif'
)
BEGIN
    ALTER TABLE tbl_parametre ADD Pasif BIT NOT NULL DEFAULT 0;
    PRINT 'Pasif sutunu eklendi';
END
GO
