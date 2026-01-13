-- tbl_kisi tablosuna DahiliNo alanı eklenmesi
IF NOT EXISTS (SELECT * FROM sys.columns WHERE object_id = OBJECT_ID('dbo.tbl_kisi') AND name = 'DahiliNo')
BEGIN
    ALTER TABLE dbo.tbl_kisi ADD DahiliNo NVARCHAR(50) NULL;
END
GO
