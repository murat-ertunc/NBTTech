-- =============================================
-- HATIRLATMA PARAMETRELERI
-- Takvim entegrasyonu için hatırlatma parametreleri
-- =============================================

-- Mevcut teminat parametresini aktif/pasif yapısına uygun hale getir
-- ve yeni hatırlatma parametrelerini ekle

IF OBJECT_ID('tbl_parametre', 'U') IS NOT NULL
BEGIN
    -- Görüşme hatırlatma parametresi
    IF NOT EXISTS (SELECT 1 FROM tbl_parametre WHERE Grup = 'genel' AND Kod = 'gorusme_hatirlatma_gun' AND Sil = 0)
    BEGIN
        INSERT INTO tbl_parametre (Grup, Kod, Deger, Etiket, Sira, Aktif, Varsayilan) 
        VALUES ('genel', 'gorusme_hatirlatma_gun', '0', N'Görüşme Tarihi Öncesi Hatırlatma Günü', 3, 1, 0);
    END

    -- Teklif geçerlilik hatırlatma parametresi
    IF NOT EXISTS (SELECT 1 FROM tbl_parametre WHERE Grup = 'genel' AND Kod = 'teklif_gecerlilik_hatirlatma_gun' AND Sil = 0)
    BEGIN
        INSERT INTO tbl_parametre (Grup, Kod, Deger, Etiket, Sira, Aktif, Varsayilan) 
        VALUES ('genel', 'teklif_gecerlilik_hatirlatma_gun', '3', N'Teklif Geçerlilik Tarihi Öncesi Hatırlatma Günü', 4, 1, 0);
    END

    -- Sözleşme tarihi hatırlatma parametresi
    IF NOT EXISTS (SELECT 1 FROM tbl_parametre WHERE Grup = 'genel' AND Kod = 'sozlesme_hatirlatma_gun' AND Sil = 0)
    BEGIN
        INSERT INTO tbl_parametre (Grup, Kod, Deger, Etiket, Sira, Aktif, Varsayilan) 
        VALUES ('genel', 'sozlesme_hatirlatma_gun', '0', N'Sözleşme Tarihi Öncesi Hatırlatma Günü', 5, 1, 0);
    END

    -- Damga vergisi tarihi hatırlatma parametresi
    IF NOT EXISTS (SELECT 1 FROM tbl_parametre WHERE Grup = 'genel' AND Kod = 'damgavergisi_hatirlatma_gun' AND Sil = 0)
    BEGIN
        INSERT INTO tbl_parametre (Grup, Kod, Deger, Etiket, Sira, Aktif, Varsayilan) 
        VALUES ('genel', 'damgavergisi_hatirlatma_gun', '0', N'Damga Vergisi Tarihi Öncesi Hatırlatma Günü', 6, 1, 0);
    END

    -- Fatura tarihi hatırlatma parametresi
    IF NOT EXISTS (SELECT 1 FROM tbl_parametre WHERE Grup = 'genel' AND Kod = 'fatura_hatirlatma_gun' AND Sil = 0)
    BEGIN
        INSERT INTO tbl_parametre (Grup, Kod, Deger, Etiket, Sira, Aktif, Varsayilan) 
        VALUES ('genel', 'fatura_hatirlatma_gun', '0', N'Fatura Tarihi Öncesi Hatırlatma Günü', 7, 1, 0);
    END

    -- Ödeme tarihi hatırlatma parametresi
    IF NOT EXISTS (SELECT 1 FROM tbl_parametre WHERE Grup = 'genel' AND Kod = 'odeme_hatirlatma_gun' AND Sil = 0)
    BEGIN
        INSERT INTO tbl_parametre (Grup, Kod, Deger, Etiket, Sira, Aktif, Varsayilan) 
        VALUES ('genel', 'odeme_hatirlatma_gun', '0', N'Ödeme Tarihi Öncesi Hatırlatma Günü', 8, 1, 0);
    END
END
