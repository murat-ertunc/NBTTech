-- =============================================
-- PARAMETRE TABLOSU
-- Sistem genelinde kullanılan parametreler
-- =============================================

-- Ana tablo
IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='tbl_parametre' AND xtype='U')
BEGIN
    CREATE TABLE tbl_parametre (
        Id INT IDENTITY(1,1) PRIMARY KEY,
        Grup NVARCHAR(50) NOT NULL,           -- doviz, durum, genel
        Kod NVARCHAR(50) NOT NULL,            -- TRY, USD, bekliyor, aktif, pagination_default
        Deger NVARCHAR(255) NULL,             -- Değer (badge rengi, para birimi simgesi vb)
        Etiket NVARCHAR(100) NULL,            -- Görünen isim (Türk Lirası, Bekliyor vb)
        Sira INT DEFAULT 0,                   -- Sıralama
        Aktif BIT DEFAULT 1,                  -- Aktif mi
        Varsayilan BIT DEFAULT 0,             -- Varsayılan mı (doviz için)
        EkleyenUserId INT NULL,
        EklemeZamani DATETIME DEFAULT GETDATE(),
        GuncelleyenUserId INT NULL,
        GuncellemeZamani DATETIME NULL,
        SilenUserId INT NULL,
        SilmeZamani DATETIME NULL,
        Sil BIT DEFAULT 0
    );
    PRINT 'tbl_parametre tablosu oluşturuldu';
END
GO

-- Backup tablosu
IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='bck_tbl_parametre' AND xtype='U')
BEGIN
    CREATE TABLE bck_tbl_parametre (
        YedekId INT IDENTITY(1,1) PRIMARY KEY,
        YedekZamani DATETIME DEFAULT GETDATE(),
        YedekleyenUserId INT NULL,
        Id INT,
        Grup NVARCHAR(50),
        Kod NVARCHAR(50),
        Deger NVARCHAR(255),
        Etiket NVARCHAR(100),
        Sira INT,
        Aktif BIT,
        Varsayilan BIT,
        EkleyenUserId INT,
        EklemeZamani DATETIME,
        GuncelleyenUserId INT,
        GuncellemeZamani DATETIME,
        SilenUserId INT,
        SilmeZamani DATETIME,
        Sil BIT
    );
    PRINT 'bck_tbl_parametre tablosu oluşturuldu';
END
GO

-- Varsayılan döviz parametreleri
IF NOT EXISTS (SELECT 1 FROM tbl_parametre WHERE Grup = 'doviz')
BEGIN
    INSERT INTO tbl_parametre (Grup, Kod, Deger, Etiket, Sira, Aktif, Varsayilan) VALUES
    ('doviz', 'TRY', '₺', 'Türk Lirası', 1, 1, 1),
    ('doviz', 'USD', '$', 'ABD Doları', 2, 1, 0),
    ('doviz', 'EUR', '€', 'Euro', 3, 1, 0),
    ('doviz', 'GBP', '£', 'İngiliz Sterlini', 4, 0, 0);
    PRINT 'Varsayılan döviz parametreleri eklendi';
END
GO

-- Varsayılan durum parametreleri (Proje)
IF NOT EXISTS (SELECT 1 FROM tbl_parametre WHERE Grup = 'durum_proje')
BEGIN
    INSERT INTO tbl_parametre (Grup, Kod, Deger, Etiket, Sira, Aktif, Varsayilan) VALUES
    ('durum_proje', '1', 'success', 'Aktif', 1, 1, 1),
    ('durum_proje', '2', 'warning', 'Beklemede', 2, 1, 0),
    ('durum_proje', '3', 'danger', 'İptal', 3, 1, 0),
    ('durum_proje', '4', 'secondary', 'Tamamlandı', 4, 1, 0);
    PRINT 'Varsayılan proje durum parametreleri eklendi';
END
GO

-- Varsayılan durum parametreleri (Teklif)
IF NOT EXISTS (SELECT 1 FROM tbl_parametre WHERE Grup = 'durum_teklif')
BEGIN
    INSERT INTO tbl_parametre (Grup, Kod, Deger, Etiket, Sira, Aktif, Varsayilan) VALUES
    ('durum_teklif', '0', 'secondary', 'Taslak', 1, 1, 1),
    ('durum_teklif', '1', 'warning', 'Gönderildi', 2, 1, 0),
    ('durum_teklif', '2', 'success', 'Onaylandı', 3, 1, 0),
    ('durum_teklif', '3', 'danger', 'Reddedildi', 4, 1, 0);
    PRINT 'Varsayılan teklif durum parametreleri eklendi';
END
GO

-- Varsayılan durum parametreleri (Sözleşme)
IF NOT EXISTS (SELECT 1 FROM tbl_parametre WHERE Grup = 'durum_sozlesme')
BEGIN
    INSERT INTO tbl_parametre (Grup, Kod, Deger, Etiket, Sira, Aktif, Varsayilan) VALUES
    ('durum_sozlesme', '1', 'success', 'Aktif', 1, 1, 1),
    ('durum_sozlesme', '2', 'secondary', 'Pasif', 2, 1, 0),
    ('durum_sozlesme', '3', 'danger', 'İptal', 3, 1, 0);
    PRINT 'Varsayılan sözleşme durum parametreleri eklendi';
END
GO

-- Varsayılan durum parametreleri (Teminat)
IF NOT EXISTS (SELECT 1 FROM tbl_parametre WHERE Grup = 'durum_teminat')
BEGIN
    INSERT INTO tbl_parametre (Grup, Kod, Deger, Etiket, Sira, Aktif, Varsayilan) VALUES
    ('durum_teminat', '1', 'warning', 'Bekliyor', 1, 1, 1),
    ('durum_teminat', '2', 'info', 'İade Edildi', 2, 1, 0),
    ('durum_teminat', '3', 'success', 'Tahsil Edildi', 3, 1, 0),
    ('durum_teminat', '4', 'danger', 'Yandı', 4, 1, 0);
    PRINT 'Varsayılan teminat durum parametreleri eklendi';
END
GO

-- Varsayılan genel parametreler
IF NOT EXISTS (SELECT 1 FROM tbl_parametre WHERE Grup = 'genel')
BEGIN
    INSERT INTO tbl_parametre (Grup, Kod, Deger, Etiket, Sira, Aktif, Varsayilan) VALUES
    ('genel', 'pagination_default', '10', 'Sayfa Başına Kayıt', 1, 1, 1);
    PRINT 'Varsayılan genel parametreler eklendi';
END
GO

PRINT 'Migration 033: Parametre tablosu ve varsayılan veriler oluşturuldu';
