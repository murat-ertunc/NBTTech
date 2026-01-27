-- =============================================
-- Permission Seed Data
-- =============================================
-- Her modul icin CRUD permissionlari
-- Format: {modul}.{aksiyon}

IF OBJECT_ID('tnm_permission', 'U') IS NOT NULL
BEGIN
    DECLARE @SeedUserId INT = 1;
    DECLARE @Simdi DATETIME2(0) = SYSUTCDATETIME();

    MERGE tnm_permission AS target
    USING (VALUES
        ('users.create', 'users', 'create', 'Kullanici olusturma yetkisi', 1),
        ('users.read', 'users', 'read', 'Kullanici listeleme ve goruntuleme yetkisi', 1),
        ('users.update', 'users', 'update', 'Kullanici guncelleme yetkisi', 1),
        ('users.delete', 'users', 'delete', 'Kullanici silme yetkisi', 1),
        ('roles.create', 'roles', 'create', 'Rol olusturma yetkisi', 1),
        ('roles.read', 'roles', 'read', 'Rol listeleme yetkisi', 1),
        ('roles.update', 'roles', 'update', 'Rol guncelleme yetkisi', 1),
        ('roles.delete', 'roles', 'delete', 'Rol silme yetkisi', 1),
        ('customers.create', 'customers', 'create', 'Musteri olusturma yetkisi', 1),
        ('customers.read', 'customers', 'read', 'Musteri listeleme yetkisi', 1),
        ('customers.update', 'customers', 'update', 'Musteri guncelleme yetkisi', 1),
        ('customers.delete', 'customers', 'delete', 'Musteri silme yetkisi', 1),
        ('invoices.create', 'invoices', 'create', 'Fatura olusturma yetkisi', 1),
        ('invoices.read', 'invoices', 'read', 'Fatura listeleme yetkisi', 1),
        ('invoices.update', 'invoices', 'update', 'Fatura guncelleme yetkisi', 1),
        ('invoices.delete', 'invoices', 'delete', 'Fatura silme yetkisi', 1),
        ('payments.create', 'payments', 'create', 'Odeme olusturma yetkisi', 1),
        ('payments.read', 'payments', 'read', 'Odeme listeleme yetkisi', 1),
        ('payments.update', 'payments', 'update', 'Odeme guncelleme yetkisi', 1),
        ('payments.delete', 'payments', 'delete', 'Odeme silme yetkisi', 1),
        ('projects.create', 'projects', 'create', 'Proje olusturma yetkisi', 1),
        ('projects.read', 'projects', 'read', 'Proje listeleme yetkisi', 1),
        ('projects.update', 'projects', 'update', 'Proje guncelleme yetkisi', 1),
        ('projects.delete', 'projects', 'delete', 'Proje silme yetkisi', 1),
        ('offers.create', 'offers', 'create', 'Teklif olusturma yetkisi', 1),
        ('offers.read', 'offers', 'read', 'Teklif listeleme yetkisi', 1),
        ('offers.update', 'offers', 'update', 'Teklif guncelleme yetkisi', 1),
        ('offers.delete', 'offers', 'delete', 'Teklif silme yetkisi', 1),
        ('contracts.create', 'contracts', 'create', 'Sozlesme olusturma yetkisi', 1),
        ('contracts.read', 'contracts', 'read', 'Sozlesme listeleme yetkisi', 1),
        ('contracts.update', 'contracts', 'update', 'Sozlesme guncelleme yetkisi', 1),
        ('contracts.delete', 'contracts', 'delete', 'Sozlesme silme yetkisi', 1),
        ('guarantees.create', 'guarantees', 'create', 'Teminat olusturma yetkisi', 1),
        ('guarantees.read', 'guarantees', 'read', 'Teminat listeleme yetkisi', 1),
        ('guarantees.update', 'guarantees', 'update', 'Teminat guncelleme yetkisi', 1),
        ('guarantees.delete', 'guarantees', 'delete', 'Teminat silme yetkisi', 1),
        ('meetings.create', 'meetings', 'create', 'Gorusme olusturma yetkisi', 1),
        ('meetings.read', 'meetings', 'read', 'Gorusme listeleme yetkisi', 1),
        ('meetings.update', 'meetings', 'update', 'Gorusme guncelleme yetkisi', 1),
        ('meetings.delete', 'meetings', 'delete', 'Gorusme silme yetkisi', 1),
        ('contacts.create', 'contacts', 'create', 'Kisi olusturma yetkisi', 1),
        ('contacts.read', 'contacts', 'read', 'Kisi listeleme yetkisi', 1),
        ('contacts.update', 'contacts', 'update', 'Kisi guncelleme yetkisi', 1),
        ('contacts.delete', 'contacts', 'delete', 'Kisi silme yetkisi', 1),
        ('files.create', 'files', 'create', 'Dosya yukleme yetkisi', 1),
        ('files.read', 'files', 'read', 'Dosya listeleme yetkisi', 1),
        ('files.update', 'files', 'update', 'Dosya guncelleme yetkisi', 1),
        ('files.delete', 'files', 'delete', 'Dosya silme yetkisi', 1),
        ('calendar.create', 'calendar', 'create', 'Takvim kaydi olusturma yetkisi', 1),
        ('calendar.read', 'calendar', 'read', 'Takvim listeleme yetkisi', 1),
        ('calendar.update', 'calendar', 'update', 'Takvim guncelleme yetkisi', 1),
        ('calendar.delete', 'calendar', 'delete', 'Takvim silme yetkisi', 1),
        ('stamp_taxes.create', 'stamp_taxes', 'create', 'Damga vergisi olusturma yetkisi', 1),
        ('stamp_taxes.read', 'stamp_taxes', 'read', 'Damga vergisi listeleme yetkisi', 1),
        ('stamp_taxes.update', 'stamp_taxes', 'update', 'Damga vergisi guncelleme yetkisi', 1),
        ('stamp_taxes.delete', 'stamp_taxes', 'delete', 'Damga vergisi silme yetkisi', 1),
        ('logs.read', 'logs', 'read', 'Islem kayitlarini goruntuleme yetkisi', 1),
        ('parameters.create', 'parameters', 'create', 'Parametre olusturma yetkisi', 1),
        ('parameters.read', 'parameters', 'read', 'Parametre listeleme yetkisi', 1),
        ('parameters.update', 'parameters', 'update', 'Parametre guncelleme yetkisi', 1),
        ('parameters.delete', 'parameters', 'delete', 'Parametre silme yetkisi', 1),
        ('dashboard.read', 'dashboard', 'read', 'Dashboard goruntuleme yetkisi', 1),
        ('alarms.read', 'alarms', 'read', 'Alarm listeleme yetkisi', 1)
    ) AS src(PermissionKodu, ModulAdi, Aksiyon, Aciklama, Aktif)
    ON target.PermissionKodu = src.PermissionKodu
    WHEN MATCHED THEN
        UPDATE SET
            target.ModulAdi = src.ModulAdi,
            target.Aksiyon = src.Aksiyon,
            target.Aciklama = src.Aciklama,
            target.Aktif = src.Aktif,
            target.Sil = 0,
            target.DegisiklikZamani = @Simdi,
            target.DegistirenUserId = @SeedUserId
    WHEN NOT MATCHED THEN
        INSERT (Guid, EklemeZamani, EkleyenUserId, DegisiklikZamani, DegistirenUserId, Sil, PermissionKodu, ModulAdi, Aksiyon, Aciklama, Aktif)
        VALUES (NEWID(), @Simdi, @SeedUserId, @Simdi, @SeedUserId, 0, src.PermissionKodu, src.ModulAdi, src.Aksiyon, src.Aciklama, src.Aktif);
END
ELSE
BEGIN
    PRINT 'Permission tablosu bulunamadi, seed atlandi.';
END
GO
