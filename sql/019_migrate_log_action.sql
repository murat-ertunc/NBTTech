-- Migration: log_action tablosu yeniden yapilandirma
-- Bu script mevcut log_action tablosunu yeni yapiya gunceller

-- Eski tablo varsa yedekle ve sil
IF OBJECT_ID('log_action', 'U') IS NOT NULL
BEGIN
    -- Varsa eski veriyi yedekle
    IF NOT EXISTS (SELECT * FROM sys.tables WHERE name = 'log_action_backup_v1')
    BEGIN
        SELECT * INTO log_action_backup_v1 FROM log_action;
        PRINT 'Eski log_action tablosu log_action_backup_v1 olarak yedeklendi.';
    END
    
    -- Eski tabloyu sil
    DROP TABLE log_action;
    PRINT 'Eski log_action tablosu silindi.';
END

-- Yeni log_action tablosunu olustur
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

PRINT 'Yeni log_action tablosu olusturuldu.';
