-- Migration: ProjeId ekleme - tbl_odeme, tbl_kisi
-- Kurallar.txt'e uygun: Foreign Key alanları sonuna Id eklenir

-- tbl_odeme
IF NOT EXISTS (SELECT * FROM sys.columns WHERE object_id = OBJECT_ID('tbl_odeme') AND name = 'ProjeId')
BEGIN
    ALTER TABLE tbl_odeme ADD ProjeId INT NULL;
    CREATE INDEX IX_tbl_odeme_ProjeId ON tbl_odeme (ProjeId);
END
GO

-- bck_tbl_odeme
IF NOT EXISTS (SELECT * FROM sys.columns WHERE object_id = OBJECT_ID('bck_tbl_odeme') AND name = 'ProjeId')
BEGIN
    ALTER TABLE bck_tbl_odeme ADD ProjeId INT NULL;
END
GO

-- tbl_kisi
IF NOT EXISTS (SELECT * FROM sys.columns WHERE object_id = OBJECT_ID('tbl_kisi') AND name = 'ProjeId')
BEGIN
    ALTER TABLE tbl_kisi ADD ProjeId INT NULL;
    CREATE INDEX IX_tbl_kisi_ProjeId ON tbl_kisi (ProjeId);
END
GO

-- bck_tbl_kisi
IF NOT EXISTS (SELECT * FROM sys.columns WHERE object_id = OBJECT_ID('bck_tbl_kisi') AND name = 'ProjeId')
BEGIN
    ALTER TABLE bck_tbl_kisi ADD ProjeId INT NULL;
END
GO

PRINT 'Migration 032: ProjeId added to tbl_odeme, tbl_kisi and their backup tables';
