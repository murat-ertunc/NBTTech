-- log_action tablosu isimlendirme ve standart alan kurallarına uygun
CREATE TABLE log_action (
    Id INT IDENTITY(1,1) PRIMARY KEY,
    Guid UNIQUEIDENTIFIER NOT NULL DEFAULT NEWID(),
    EklemeZamani DATETIME2(0) NOT NULL DEFAULT SYSUTCDATETIME(),
    EkleyenUserId INT NULL,
    DegisiklikZamani DATETIME2(0) NOT NULL DEFAULT SYSUTCDATETIME(),
    DegistirenUserId INT NULL,
    Sil BIT NOT NULL DEFAULT 0,
    Islem NVARCHAR(64) NOT NULL,
    Tablo NVARCHAR(128) NOT NULL,
    KayitId INT NULL,
    IpAdresi NVARCHAR(45) NULL,
    EskiDeger NVARCHAR(MAX) NULL,
    YeniDeger NVARCHAR(MAX) NULL
);

CREATE INDEX IX_log_action_Tablo ON log_action (Tablo);
CREATE INDEX IX_log_action_Islem ON log_action (Islem);
CREATE INDEX IX_log_action_EklemeZamani ON log_action (EklemeZamani);
CREATE INDEX IX_log_action_KayitId ON log_action (KayitId);
