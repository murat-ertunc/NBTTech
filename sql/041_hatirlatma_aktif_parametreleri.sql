-- =============================================
-- HATIRLATMA AKTIF/PASIF PARAMETRELERI
-- Takvim entegrasyonu için hatırlatma aktif/pasif parametreleri
-- =============================================

-- Görüşme hatırlatma aktif/pasif
IF NOT EXISTS (SELECT 1 FROM tbl_parametre WHERE Grup = 'genel' AND Kod = 'gorusme_hatirlatma_aktif')
BEGIN
    INSERT INTO tbl_parametre (Grup, Kod, Deger, Etiket, Sira, Aktif, Varsayilan) 
    VALUES ('genel', 'gorusme_hatirlatma_aktif', '1', N'Görüşme Hatırlatma Aktif', 13, 1, 0);
END
GO

-- Teklif geçerlilik hatırlatma aktif/pasif
IF NOT EXISTS (SELECT 1 FROM tbl_parametre WHERE Grup = 'genel' AND Kod = 'teklif_gecerlilik_hatirlatma_aktif')
BEGIN
    INSERT INTO tbl_parametre (Grup, Kod, Deger, Etiket, Sira, Aktif, Varsayilan) 
    VALUES ('genel', 'teklif_gecerlilik_hatirlatma_aktif', '1', N'Teklif Geçerlilik Hatırlatma Aktif', 14, 1, 0);
END
GO

-- Sözleşme tarihi hatırlatma aktif/pasif
IF NOT EXISTS (SELECT 1 FROM tbl_parametre WHERE Grup = 'genel' AND Kod = 'sozlesme_hatirlatma_aktif')
BEGIN
    INSERT INTO tbl_parametre (Grup, Kod, Deger, Etiket, Sira, Aktif, Varsayilan) 
    VALUES ('genel', 'sozlesme_hatirlatma_aktif', '1', N'Sözleşme Hatırlatma Aktif', 15, 1, 0);
END
GO

-- Damga vergisi tarihi hatırlatma aktif/pasif
IF NOT EXISTS (SELECT 1 FROM tbl_parametre WHERE Grup = 'genel' AND Kod = 'damgavergisi_hatirlatma_aktif')
BEGIN
    INSERT INTO tbl_parametre (Grup, Kod, Deger, Etiket, Sira, Aktif, Varsayilan) 
    VALUES ('genel', 'damgavergisi_hatirlatma_aktif', '1', N'Damga Vergisi Hatırlatma Aktif', 16, 1, 0);
END
GO

-- Teminat termin hatırlatma aktif/pasif
IF NOT EXISTS (SELECT 1 FROM tbl_parametre WHERE Grup = 'genel' AND Kod = 'teminat_termin_hatirlatma_aktif')
BEGIN
    INSERT INTO tbl_parametre (Grup, Kod, Deger, Etiket, Sira, Aktif, Varsayilan) 
    VALUES ('genel', 'teminat_termin_hatirlatma_aktif', '1', N'Teminat Termin Hatırlatma Aktif', 17, 1, 0);
END
GO

-- Fatura tarihi hatırlatma aktif/pasif
IF NOT EXISTS (SELECT 1 FROM tbl_parametre WHERE Grup = 'genel' AND Kod = 'fatura_hatirlatma_aktif')
BEGIN
    INSERT INTO tbl_parametre (Grup, Kod, Deger, Etiket, Sira, Aktif, Varsayilan) 
    VALUES ('genel', 'fatura_hatirlatma_aktif', '1', N'Fatura Hatırlatma Aktif', 18, 1, 0);
END
GO

-- Ödeme tarihi hatırlatma aktif/pasif
IF NOT EXISTS (SELECT 1 FROM tbl_parametre WHERE Grup = 'genel' AND Kod = 'odeme_hatirlatma_aktif')
BEGIN
    INSERT INTO tbl_parametre (Grup, Kod, Deger, Etiket, Sira, Aktif, Varsayilan) 
    VALUES ('genel', 'odeme_hatirlatma_aktif', '1', N'Ödeme Hatırlatma Aktif', 19, 1, 0);
END
GO
