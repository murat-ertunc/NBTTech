CREATE TABLE tbl_musteri (
    Id INT IDENTITY(1,1) PRIMARY KEY,
    Guid UNIQUEIDENTIFIER NOT NULL DEFAULT NEWID(),
    EklemeZamani DATETIME2(0) NOT NULL DEFAULT SYSUTCDATETIME(),
    EkleyenUserId INT NULL,
    DegisiklikZamani DATETIME2(0) NOT NULL DEFAULT SYSUTCDATETIME(),
    DegistirenUserId INT NULL,
    Sil BIT NOT NULL DEFAULT 0,
    Unvan NVARCHAR(255) NOT NULL,
    Aciklama NVARCHAR(MAX) NULL
);

CREATE INDEX IX_tbl_musteri_Unvan ON tbl_musteri (Unvan);
CREATE INDEX IX_tbl_musteri_EkleyenUserId ON tbl_musteri (EkleyenUserId);
CREATE INDEX IX_tbl_musteri_Sil ON tbl_musteri (Sil);
