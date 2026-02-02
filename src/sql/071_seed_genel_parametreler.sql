-- =============================================
-- GENEL PARAMETRE SEED DATA
-- Sistem genelinde kullanılan parametreler
-- =============================================

-- Genel sistem parametreleri
IF OBJECT_ID('tbl_parametre', 'U') IS NOT NULL
BEGIN
    INSERT INTO tbl_parametre (Grup, Kod, Deger, Etiket, Sira, Aktif, Varsayilan)
    SELECT v.Grup, v.Kod, v.Deger, v.Etiket, v.Sira, v.Aktif, v.Varsayilan
    FROM (VALUES
        -- Sayfalama varsayılanı
        ('genel', 'pagination_default', '10', N'Varsayılan Sayfalama', 2, 1, 0)
    ) v(Grup, Kod, Deger, Etiket, Sira, Aktif, Varsayilan)
    WHERE NOT EXISTS (
        SELECT 1 FROM tbl_parametre t
        WHERE t.Grup = v.Grup AND t.Kod = v.Kod AND t.Sil = 0
    );
END
GO
