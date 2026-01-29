-- Takvim Durum kolonunu ekle ve varsayilan durum parametrelerini olustur
IF NOT EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('tbl_takvim') AND name = 'Durum')
BEGIN
    ALTER TABLE tbl_takvim ADD Durum TINYINT NOT NULL CONSTRAINT DF_tbl_takvim_Durum DEFAULT 1;
    PRINT 'tbl_takvim tablosuna Durum kolonu eklendi.';
END
ELSE
BEGIN
    PRINT 'tbl_takvim tablosunda Durum kolonu zaten mevcut.';
END
GO

IF EXISTS (SELECT 1 FROM sys.tables WHERE name = 'bck_tbl_takvim')
BEGIN
    IF NOT EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('bck_tbl_takvim') AND name = 'Durum')
    BEGIN
        ALTER TABLE bck_tbl_takvim ADD Durum TINYINT NULL;
        PRINT 'bck_tbl_takvim tablosuna Durum kolonu eklendi.';
    END
    ELSE
    BEGIN
        PRINT 'bck_tbl_takvim tablosunda Durum kolonu zaten mevcut.';
    END
END
GO

-- Takvim durum parametreleri (sozlesme durumlari ile birebir mantik)
IF NOT EXISTS (SELECT 1 FROM tbl_parametre WHERE Grup = 'durum_takvim' AND Kod = '1' AND Sil = 0)
BEGIN
    INSERT INTO tbl_parametre (Grup, Kod, Deger, Etiket, Sira, Aktif, Varsayilan)
    VALUES ('durum_takvim', '1', 'success', 'Aktif', 1, 1, 1);
END
GO

IF NOT EXISTS (SELECT 1 FROM tbl_parametre WHERE Grup = 'durum_takvim' AND Kod = '2' AND Sil = 0)
BEGIN
    INSERT INTO tbl_parametre (Grup, Kod, Deger, Etiket, Sira, Aktif, Varsayilan)
    VALUES ('durum_takvim', '2', 'secondary', 'Pasif', 2, 1, 0);
END
GO

IF NOT EXISTS (SELECT 1 FROM tbl_parametre WHERE Grup = 'durum_takvim' AND Kod = '3' AND Sil = 0)
BEGIN
    INSERT INTO tbl_parametre (Grup, Kod, Deger, Etiket, Sira, Aktif, Varsayilan)
    VALUES ('durum_takvim', '3', 'danger', N'İptal', 3, 1, 0);
END
GO
