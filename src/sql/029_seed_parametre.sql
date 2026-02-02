-- =============================================
-- PARAMETRE SEED DATA
-- Varsayılan parametreler
-- =============================================

-- Varsayılan döviz parametreleri
IF OBJECT_ID('tbl_parametre', 'U') IS NOT NULL
BEGIN
    INSERT INTO tbl_parametre (Grup, Kod, Deger, Etiket, Sira, Aktif, Varsayilan)
    SELECT v.Grup, v.Kod, v.Deger, v.Etiket, v.Sira, v.Aktif, v.Varsayilan
    FROM (VALUES
        ('doviz', 'TRY', N'₺', N'Türk Lirası', 1, 1, 1),
        ('doviz', 'USD', '$', N'ABD Doları', 2, 1, 0),
        ('doviz', 'EUR', N'€', 'Euro', 3, 1, 0),
        ('doviz', 'GBP', N'£', N'İngiliz Sterlini', 4, 0, 0)
    ) v(Grup, Kod, Deger, Etiket, Sira, Aktif, Varsayilan)
    WHERE NOT EXISTS (
        SELECT 1 FROM tbl_parametre t
        WHERE t.Grup = v.Grup AND t.Kod = v.Kod AND t.Sil = 0
    );
END
GO

-- Varsayılan durum parametreleri (Proje)
IF OBJECT_ID('tbl_parametre', 'U') IS NOT NULL
BEGIN
    INSERT INTO tbl_parametre (Grup, Kod, Deger, Etiket, Sira, Aktif, Varsayilan)
    SELECT v.Grup, v.Kod, v.Deger, v.Etiket, v.Sira, v.Aktif, v.Varsayilan
    FROM (VALUES
        ('durum_proje', '1', 'success', 'Aktif', 1, 1, 1),
        ('durum_proje', '2', 'warning', 'Beklemede', 2, 1, 0),
        ('durum_proje', '3', 'danger', N'İptal', 3, 1, 0),
        ('durum_proje', '4', 'secondary', N'Tamamlandı', 4, 1, 0)
    ) v(Grup, Kod, Deger, Etiket, Sira, Aktif, Varsayilan)
    WHERE NOT EXISTS (
        SELECT 1 FROM tbl_parametre t
        WHERE t.Grup = v.Grup AND t.Kod = v.Kod AND t.Sil = 0
    );
END
GO

-- Varsayılan durum parametreleri (Teklif)
IF OBJECT_ID('tbl_parametre', 'U') IS NOT NULL
BEGIN
    INSERT INTO tbl_parametre (Grup, Kod, Deger, Etiket, Sira, Aktif, Varsayilan)
    SELECT v.Grup, v.Kod, v.Deger, v.Etiket, v.Sira, v.Aktif, v.Varsayilan
    FROM (VALUES
        ('durum_teklif', '0', 'warning', 'Bekliyor', 1, 1, 1),
        ('durum_teklif', '1', 'danger', 'Red', 2, 1, 0),
        ('durum_teklif', '2', 'success', N'Onaylı', 3, 1, 0),
        ('durum_teklif', '3', 'info', 'Gelsin', 4, 1, 0)
    ) v(Grup, Kod, Deger, Etiket, Sira, Aktif, Varsayilan)
    WHERE NOT EXISTS (
        SELECT 1 FROM tbl_parametre t
        WHERE t.Grup = v.Grup AND t.Kod = v.Kod AND t.Sil = 0
    );
END
GO

-- Varsayılan durum parametreleri (Sözleşme)
IF OBJECT_ID('tbl_parametre', 'U') IS NOT NULL
BEGIN
    INSERT INTO tbl_parametre (Grup, Kod, Deger, Etiket, Sira, Aktif, Varsayilan)
    SELECT v.Grup, v.Kod, v.Deger, v.Etiket, v.Sira, v.Aktif, v.Varsayilan
    FROM (VALUES
        ('durum_sozlesme', '1', 'success', 'Aktif', 1, 1, 1),
        ('durum_sozlesme', '2', 'secondary', 'Pasif', 2, 1, 0),
        ('durum_sozlesme', '3', 'danger', N'İptal', 3, 1, 0)
    ) v(Grup, Kod, Deger, Etiket, Sira, Aktif, Varsayilan)
    WHERE NOT EXISTS (
        SELECT 1 FROM tbl_parametre t
        WHERE t.Grup = v.Grup AND t.Kod = v.Kod AND t.Sil = 0
    );
END
GO

-- Varsayılan durum parametreleri (Teminat)
IF OBJECT_ID('tbl_parametre', 'U') IS NOT NULL
BEGIN
    INSERT INTO tbl_parametre (Grup, Kod, Deger, Etiket, Sira, Aktif, Varsayilan)
    SELECT v.Grup, v.Kod, v.Deger, v.Etiket, v.Sira, v.Aktif, v.Varsayilan
    FROM (VALUES
        ('durum_teminat', '1', 'warning', 'Bekliyor', 1, 1, 1),
        ('durum_teminat', '2', 'info', N'İade Edildi', 2, 1, 0),
        ('durum_teminat', '3', 'success', 'Tahsil Edildi', 3, 1, 0),
        ('durum_teminat', '4', 'danger', N'Yandı', 4, 1, 0)
    ) v(Grup, Kod, Deger, Etiket, Sira, Aktif, Varsayilan)
    WHERE NOT EXISTS (
        SELECT 1 FROM tbl_parametre t
        WHERE t.Grup = v.Grup AND t.Kod = v.Kod AND t.Sil = 0
    );
END
GO

-- Varsayılan genel parametreler
IF OBJECT_ID('tbl_parametre', 'U') IS NOT NULL
BEGIN
    INSERT INTO tbl_parametre (Grup, Kod, Deger, Etiket, Sira, Aktif, Varsayilan)
    SELECT v.Grup, v.Kod, v.Deger, v.Etiket, v.Sira, v.Aktif, v.Varsayilan
    FROM (VALUES
        ('genel', 'pagination_default', '10', N'Sayfa Başına Kayıt', 1, 1, 1),
        ('genel', 'teminat_termin_hatirlatma_gun', '7', N'Teminat Termin Tarihi Öncesi Hatırlatma Günü', 2, 1, 0)
    ) v(Grup, Kod, Deger, Etiket, Sira, Aktif, Varsayilan)
    WHERE NOT EXISTS (
        SELECT 1 FROM tbl_parametre t
        WHERE t.Grup = v.Grup AND t.Kod = v.Kod AND t.Sil = 0
    );
END
GO
