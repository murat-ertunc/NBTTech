-- =============================================
-- Admin (Yonetici) Rolune Tum Yetkileri Ata
-- =============================================
-- Bu script mevcut admin rolune eksik olan tum permission'lari ekler

SET QUOTED_IDENTIFIER ON;
SET ANSI_NULLS ON;
GO

DECLARE @AdminRolId INT;
SELECT @AdminRolId = Id FROM tnm_rol WHERE RolKodu = 'admin' AND Sil = 0;

IF @AdminRolId IS NULL
BEGIN
    PRINT 'Admin rolu bulunamadi!';
    RETURN;
END

-- Admin'e atanmamis tum aktif permission'lari ekle
INSERT INTO tnm_rol_permission (Guid, EklemeZamani, EkleyenUserId, DegisiklikZamani, DegistirenUserId, Sil, RolId, PermissionId)
SELECT NEWID(), SYSUTCDATETIME(), 1, SYSUTCDATETIME(), 1, 0, @AdminRolId, p.Id
FROM tnm_permission p
WHERE p.Sil = 0 AND p.Aktif = 1
  AND NOT EXISTS (
      SELECT 1 FROM tnm_rol_permission rp 
      WHERE rp.RolId = @AdminRolId AND rp.PermissionId = p.Id AND rp.Sil = 0
  );

PRINT 'Admin (Yonetici) rolune tum yetkiler atandi.';

-- Sonucu goster
SELECT 
    r.RolAdi,
    r.RolKodu,
    COUNT(rp.Id) AS ToplamYetkiSayisi
FROM tnm_rol r
LEFT JOIN tnm_rol_permission rp ON r.Id = rp.RolId AND rp.Sil = 0
WHERE r.RolKodu = 'admin' AND r.Sil = 0
GROUP BY r.RolAdi, r.RolKodu;
GO
