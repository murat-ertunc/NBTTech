-- ProjeId ekleme: tbl_gorusme, tbl_sozlesme, tbl_damgavergisi, tbl_fatura, tbl_dosya
-- Ve backup tablolarına da ekleme

-- tbl_gorusme
IF NOT EXISTS (SELECT * FROM sys.columns WHERE object_id = OBJECT_ID('tbl_gorusme') AND name = 'ProjeId')
BEGIN
    ALTER TABLE tbl_gorusme ADD ProjeId INT NULL;
    CREATE INDEX IX_tbl_gorusme_ProjeId ON tbl_gorusme (ProjeId);
END
GO

-- bck_tbl_gorusme
IF NOT EXISTS (SELECT * FROM sys.columns WHERE object_id = OBJECT_ID('bck_tbl_gorusme') AND name = 'ProjeId')
BEGIN
    ALTER TABLE bck_tbl_gorusme ADD ProjeId INT NULL;
END
GO

-- tbl_sozlesme
IF NOT EXISTS (SELECT * FROM sys.columns WHERE object_id = OBJECT_ID('tbl_sozlesme') AND name = 'ProjeId')
BEGIN
    ALTER TABLE tbl_sozlesme ADD ProjeId INT NULL;
    CREATE INDEX IX_tbl_sozlesme_ProjeId ON tbl_sozlesme (ProjeId);
END
GO

-- bck_tbl_sozlesme
IF NOT EXISTS (SELECT * FROM sys.columns WHERE object_id = OBJECT_ID('bck_tbl_sozlesme') AND name = 'ProjeId')
BEGIN
    ALTER TABLE bck_tbl_sozlesme ADD ProjeId INT NULL;
END
GO

-- tbl_damgavergisi
IF NOT EXISTS (SELECT * FROM sys.columns WHERE object_id = OBJECT_ID('tbl_damgavergisi') AND name = 'ProjeId')
BEGIN
    ALTER TABLE tbl_damgavergisi ADD ProjeId INT NULL;
    CREATE INDEX IX_tbl_damgavergisi_ProjeId ON tbl_damgavergisi (ProjeId);
END
GO

-- bck_tbl_damgavergisi
IF NOT EXISTS (SELECT * FROM sys.columns WHERE object_id = OBJECT_ID('bck_tbl_damgavergisi') AND name = 'ProjeId')
BEGIN
    ALTER TABLE bck_tbl_damgavergisi ADD ProjeId INT NULL;
END
GO

-- tbl_fatura
IF NOT EXISTS (SELECT * FROM sys.columns WHERE object_id = OBJECT_ID('tbl_fatura') AND name = 'ProjeId')
BEGIN
    ALTER TABLE tbl_fatura ADD ProjeId INT NULL;
    CREATE INDEX IX_tbl_fatura_ProjeId ON tbl_fatura (ProjeId);
END
GO

-- bck_tbl_fatura
IF NOT EXISTS (SELECT * FROM sys.columns WHERE object_id = OBJECT_ID('bck_tbl_fatura') AND name = 'ProjeId')
BEGIN
    ALTER TABLE bck_tbl_fatura ADD ProjeId INT NULL;
END
GO

-- tbl_dosya
IF NOT EXISTS (SELECT * FROM sys.columns WHERE object_id = OBJECT_ID('tbl_dosya') AND name = 'ProjeId')
BEGIN
    ALTER TABLE tbl_dosya ADD ProjeId INT NULL;
    CREATE INDEX IX_tbl_dosya_ProjeId ON tbl_dosya (ProjeId);
END
GO

-- bck_tbl_dosya
IF NOT EXISTS (SELECT * FROM sys.columns WHERE object_id = OBJECT_ID('bck_tbl_dosya') AND name = 'ProjeId')
BEGIN
    ALTER TABLE bck_tbl_dosya ADD ProjeId INT NULL;
END
GO
