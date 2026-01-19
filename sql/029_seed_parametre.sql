-- =============================================
-- PARAMETRE SEED DATA
-- Varsayılan parametreler
-- =============================================

-- Varsayılan döviz parametreleri
INSERT INTO tbl_parametre (Grup, Kod, Deger, Etiket, Sira, Aktif, Varsayilan) VALUES
('doviz', 'TRY', N'₺', N'Türk Lirası', 1, 1, 1),
('doviz', 'USD', '$', N'ABD Doları', 2, 1, 0),
('doviz', 'EUR', N'€', 'Euro', 3, 1, 0),
('doviz', 'GBP', N'£', N'İngiliz Sterlini', 4, 0, 0);
GO

-- Varsayılan durum parametreleri (Proje)
INSERT INTO tbl_parametre (Grup, Kod, Deger, Etiket, Sira, Aktif, Varsayilan) VALUES
('durum_proje', '1', 'success', 'Aktif', 1, 1, 1),
('durum_proje', '2', 'warning', 'Beklemede', 2, 1, 0),
('durum_proje', '3', 'danger', N'İptal', 3, 1, 0),
('durum_proje', '4', 'secondary', N'Tamamlandı', 4, 1, 0);
GO

-- Varsayılan durum parametreleri (Teklif)
INSERT INTO tbl_parametre (Grup, Kod, Deger, Etiket, Sira, Aktif, Varsayilan) VALUES
('durum_teklif', '0', 'warning', 'Bekliyor', 1, 1, 1),
('durum_teklif', '1', 'danger', 'Red', 2, 1, 0),
('durum_teklif', '2', 'success', N'Onaylı', 3, 1, 0),
('durum_teklif', '3', 'info', 'Gelsin', 4, 1, 0);
GO

-- Varsayılan durum parametreleri (Sözleşme)
INSERT INTO tbl_parametre (Grup, Kod, Deger, Etiket, Sira, Aktif, Varsayilan) VALUES
('durum_sozlesme', '1', 'success', 'Aktif', 1, 1, 1),
('durum_sozlesme', '2', 'secondary', 'Pasif', 2, 1, 0),
('durum_sozlesme', '3', 'danger', N'İptal', 3, 1, 0);
GO

-- Varsayılan durum parametreleri (Teminat)
INSERT INTO tbl_parametre (Grup, Kod, Deger, Etiket, Sira, Aktif, Varsayilan) VALUES
('durum_teminat', '1', 'warning', 'Bekliyor', 1, 1, 1),
('durum_teminat', '2', 'info', N'İade Edildi', 2, 1, 0),
('durum_teminat', '3', 'success', 'Tahsil Edildi', 3, 1, 0),
('durum_teminat', '4', 'danger', N'Yandı', 4, 1, 0);
GO

-- Varsayılan genel parametreler
INSERT INTO tbl_parametre (Grup, Kod, Deger, Etiket, Sira, Aktif, Varsayilan) VALUES
('genel', 'pagination_default', '10', N'Sayfa Başına Kayıt', 1, 1, 1),
('genel', 'termin_hatirlatma_gun', '7', N'Termin Tarihi Öncesi Hatırlatma Günü', 2, 1, 0);
GO
