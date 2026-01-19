-- =============================================
-- Permission Seed Data
-- =============================================
-- Her modul icin CRUD permissionlari
-- Format: {modul}.{aksiyon}

-- Mevcut permissionlari temizle (Sil=1 yap)
UPDATE tnm_permission SET Sil = 1 WHERE Sil = 0;
GO

-- Permission ekleme fonksiyonu
-- Not: MSSQL'de MERGE kullaniyoruz (upsert)
DECLARE @SeedUserId INT = 1;
DECLARE @Simdi DATETIME2(0) = SYSUTCDATETIME();

-- =============================================
-- MODUL: users (Kullanicilar)
-- =============================================
INSERT INTO tnm_permission (Guid, EklemeZamani, EkleyenUserId, DegisiklikZamani, DegistirenUserId, Sil, PermissionKodu, ModulAdi, Aksiyon, Aciklama, Aktif)
VALUES 
    (NEWID(), @Simdi, @SeedUserId, @Simdi, @SeedUserId, 0, 'users.create', 'users', 'create', 'Kullanici olusturma yetkisi', 1),
    (NEWID(), @Simdi, @SeedUserId, @Simdi, @SeedUserId, 0, 'users.read', 'users', 'read', 'Kullanici listeleme ve goruntuleme yetkisi', 1),
    (NEWID(), @Simdi, @SeedUserId, @Simdi, @SeedUserId, 0, 'users.update', 'users', 'update', 'Kullanici guncelleme yetkisi', 1),
    (NEWID(), @Simdi, @SeedUserId, @Simdi, @SeedUserId, 0, 'users.delete', 'users', 'delete', 'Kullanici silme yetkisi', 1);

-- =============================================
-- MODUL: roles (Roller)
-- =============================================
INSERT INTO tnm_permission (Guid, EklemeZamani, EkleyenUserId, DegisiklikZamani, DegistirenUserId, Sil, PermissionKodu, ModulAdi, Aksiyon, Aciklama, Aktif)
VALUES 
    (NEWID(), @Simdi, @SeedUserId, @Simdi, @SeedUserId, 0, 'roles.create', 'roles', 'create', 'Rol olusturma yetkisi', 1),
    (NEWID(), @Simdi, @SeedUserId, @Simdi, @SeedUserId, 0, 'roles.read', 'roles', 'read', 'Rol listeleme yetkisi', 1),
    (NEWID(), @Simdi, @SeedUserId, @Simdi, @SeedUserId, 0, 'roles.update', 'roles', 'update', 'Rol guncelleme yetkisi', 1),
    (NEWID(), @Simdi, @SeedUserId, @Simdi, @SeedUserId, 0, 'roles.delete', 'roles', 'delete', 'Rol silme yetkisi', 1);

-- =============================================
-- MODUL: customers (Musteriler)
-- =============================================
INSERT INTO tnm_permission (Guid, EklemeZamani, EkleyenUserId, DegisiklikZamani, DegistirenUserId, Sil, PermissionKodu, ModulAdi, Aksiyon, Aciklama, Aktif)
VALUES 
    (NEWID(), @Simdi, @SeedUserId, @Simdi, @SeedUserId, 0, 'customers.create', 'customers', 'create', 'Musteri olusturma yetkisi', 1),
    (NEWID(), @Simdi, @SeedUserId, @Simdi, @SeedUserId, 0, 'customers.read', 'customers', 'read', 'Musteri listeleme yetkisi', 1),
    (NEWID(), @Simdi, @SeedUserId, @Simdi, @SeedUserId, 0, 'customers.update', 'customers', 'update', 'Musteri guncelleme yetkisi', 1),
    (NEWID(), @Simdi, @SeedUserId, @Simdi, @SeedUserId, 0, 'customers.delete', 'customers', 'delete', 'Musteri silme yetkisi', 1);

-- =============================================
-- MODUL: invoices (Faturalar)
-- =============================================
INSERT INTO tnm_permission (Guid, EklemeZamani, EkleyenUserId, DegisiklikZamani, DegistirenUserId, Sil, PermissionKodu, ModulAdi, Aksiyon, Aciklama, Aktif)
VALUES 
    (NEWID(), @Simdi, @SeedUserId, @Simdi, @SeedUserId, 0, 'invoices.create', 'invoices', 'create', 'Fatura olusturma yetkisi', 1),
    (NEWID(), @Simdi, @SeedUserId, @Simdi, @SeedUserId, 0, 'invoices.read', 'invoices', 'read', 'Fatura listeleme yetkisi', 1),
    (NEWID(), @Simdi, @SeedUserId, @Simdi, @SeedUserId, 0, 'invoices.update', 'invoices', 'update', 'Fatura guncelleme yetkisi', 1),
    (NEWID(), @Simdi, @SeedUserId, @Simdi, @SeedUserId, 0, 'invoices.delete', 'invoices', 'delete', 'Fatura silme yetkisi', 1);

-- =============================================
-- MODUL: payments (Odemeler)
-- =============================================
INSERT INTO tnm_permission (Guid, EklemeZamani, EkleyenUserId, DegisiklikZamani, DegistirenUserId, Sil, PermissionKodu, ModulAdi, Aksiyon, Aciklama, Aktif)
VALUES 
    (NEWID(), @Simdi, @SeedUserId, @Simdi, @SeedUserId, 0, 'payments.create', 'payments', 'create', 'Odeme olusturma yetkisi', 1),
    (NEWID(), @Simdi, @SeedUserId, @Simdi, @SeedUserId, 0, 'payments.read', 'payments', 'read', 'Odeme listeleme yetkisi', 1),
    (NEWID(), @Simdi, @SeedUserId, @Simdi, @SeedUserId, 0, 'payments.update', 'payments', 'update', 'Odeme guncelleme yetkisi', 1),
    (NEWID(), @Simdi, @SeedUserId, @Simdi, @SeedUserId, 0, 'payments.delete', 'payments', 'delete', 'Odeme silme yetkisi', 1);

-- =============================================
-- MODUL: projects (Projeler)
-- =============================================
INSERT INTO tnm_permission (Guid, EklemeZamani, EkleyenUserId, DegisiklikZamani, DegistirenUserId, Sil, PermissionKodu, ModulAdi, Aksiyon, Aciklama, Aktif)
VALUES 
    (NEWID(), @Simdi, @SeedUserId, @Simdi, @SeedUserId, 0, 'projects.create', 'projects', 'create', 'Proje olusturma yetkisi', 1),
    (NEWID(), @Simdi, @SeedUserId, @Simdi, @SeedUserId, 0, 'projects.read', 'projects', 'read', 'Proje listeleme yetkisi', 1),
    (NEWID(), @Simdi, @SeedUserId, @Simdi, @SeedUserId, 0, 'projects.update', 'projects', 'update', 'Proje guncelleme yetkisi', 1),
    (NEWID(), @Simdi, @SeedUserId, @Simdi, @SeedUserId, 0, 'projects.delete', 'projects', 'delete', 'Proje silme yetkisi', 1);

-- =============================================
-- MODUL: offers (Teklifler)
-- =============================================
INSERT INTO tnm_permission (Guid, EklemeZamani, EkleyenUserId, DegisiklikZamani, DegistirenUserId, Sil, PermissionKodu, ModulAdi, Aksiyon, Aciklama, Aktif)
VALUES 
    (NEWID(), @Simdi, @SeedUserId, @Simdi, @SeedUserId, 0, 'offers.create', 'offers', 'create', 'Teklif olusturma yetkisi', 1),
    (NEWID(), @Simdi, @SeedUserId, @Simdi, @SeedUserId, 0, 'offers.read', 'offers', 'read', 'Teklif listeleme yetkisi', 1),
    (NEWID(), @Simdi, @SeedUserId, @Simdi, @SeedUserId, 0, 'offers.update', 'offers', 'update', 'Teklif guncelleme yetkisi', 1),
    (NEWID(), @Simdi, @SeedUserId, @Simdi, @SeedUserId, 0, 'offers.delete', 'offers', 'delete', 'Teklif silme yetkisi', 1);

-- =============================================
-- MODUL: contracts (Sozlesmeler)
-- =============================================
INSERT INTO tnm_permission (Guid, EklemeZamani, EkleyenUserId, DegisiklikZamani, DegistirenUserId, Sil, PermissionKodu, ModulAdi, Aksiyon, Aciklama, Aktif)
VALUES 
    (NEWID(), @Simdi, @SeedUserId, @Simdi, @SeedUserId, 0, 'contracts.create', 'contracts', 'create', 'Sozlesme olusturma yetkisi', 1),
    (NEWID(), @Simdi, @SeedUserId, @Simdi, @SeedUserId, 0, 'contracts.read', 'contracts', 'read', 'Sozlesme listeleme yetkisi', 1),
    (NEWID(), @Simdi, @SeedUserId, @Simdi, @SeedUserId, 0, 'contracts.update', 'contracts', 'update', 'Sozlesme guncelleme yetkisi', 1),
    (NEWID(), @Simdi, @SeedUserId, @Simdi, @SeedUserId, 0, 'contracts.delete', 'contracts', 'delete', 'Sozlesme silme yetkisi', 1);

-- =============================================
-- MODUL: guarantees (Teminatlar)
-- =============================================
INSERT INTO tnm_permission (Guid, EklemeZamani, EkleyenUserId, DegisiklikZamani, DegistirenUserId, Sil, PermissionKodu, ModulAdi, Aksiyon, Aciklama, Aktif)
VALUES 
    (NEWID(), @Simdi, @SeedUserId, @Simdi, @SeedUserId, 0, 'guarantees.create', 'guarantees', 'create', 'Teminat olusturma yetkisi', 1),
    (NEWID(), @Simdi, @SeedUserId, @Simdi, @SeedUserId, 0, 'guarantees.read', 'guarantees', 'read', 'Teminat listeleme yetkisi', 1),
    (NEWID(), @Simdi, @SeedUserId, @Simdi, @SeedUserId, 0, 'guarantees.update', 'guarantees', 'update', 'Teminat guncelleme yetkisi', 1),
    (NEWID(), @Simdi, @SeedUserId, @Simdi, @SeedUserId, 0, 'guarantees.delete', 'guarantees', 'delete', 'Teminat silme yetkisi', 1);

-- =============================================
-- MODUL: meetings (Gorusmeler)
-- =============================================
INSERT INTO tnm_permission (Guid, EklemeZamani, EkleyenUserId, DegisiklikZamani, DegistirenUserId, Sil, PermissionKodu, ModulAdi, Aksiyon, Aciklama, Aktif)
VALUES 
    (NEWID(), @Simdi, @SeedUserId, @Simdi, @SeedUserId, 0, 'meetings.create', 'meetings', 'create', 'Gorusme olusturma yetkisi', 1),
    (NEWID(), @Simdi, @SeedUserId, @Simdi, @SeedUserId, 0, 'meetings.read', 'meetings', 'read', 'Gorusme listeleme yetkisi', 1),
    (NEWID(), @Simdi, @SeedUserId, @Simdi, @SeedUserId, 0, 'meetings.update', 'meetings', 'update', 'Gorusme guncelleme yetkisi', 1),
    (NEWID(), @Simdi, @SeedUserId, @Simdi, @SeedUserId, 0, 'meetings.delete', 'meetings', 'delete', 'Gorusme silme yetkisi', 1);

-- =============================================
-- MODUL: contacts (Kisiler)
-- =============================================
INSERT INTO tnm_permission (Guid, EklemeZamani, EkleyenUserId, DegisiklikZamani, DegistirenUserId, Sil, PermissionKodu, ModulAdi, Aksiyon, Aciklama, Aktif)
VALUES 
    (NEWID(), @Simdi, @SeedUserId, @Simdi, @SeedUserId, 0, 'contacts.create', 'contacts', 'create', 'Kisi olusturma yetkisi', 1),
    (NEWID(), @Simdi, @SeedUserId, @Simdi, @SeedUserId, 0, 'contacts.read', 'contacts', 'read', 'Kisi listeleme yetkisi', 1),
    (NEWID(), @Simdi, @SeedUserId, @Simdi, @SeedUserId, 0, 'contacts.update', 'contacts', 'update', 'Kisi guncelleme yetkisi', 1),
    (NEWID(), @Simdi, @SeedUserId, @Simdi, @SeedUserId, 0, 'contacts.delete', 'contacts', 'delete', 'Kisi silme yetkisi', 1);

-- =============================================
-- MODUL: files (Dosyalar)
-- =============================================
INSERT INTO tnm_permission (Guid, EklemeZamani, EkleyenUserId, DegisiklikZamani, DegistirenUserId, Sil, PermissionKodu, ModulAdi, Aksiyon, Aciklama, Aktif)
VALUES 
    (NEWID(), @Simdi, @SeedUserId, @Simdi, @SeedUserId, 0, 'files.create', 'files', 'create', 'Dosya yukleme yetkisi', 1),
    (NEWID(), @Simdi, @SeedUserId, @Simdi, @SeedUserId, 0, 'files.read', 'files', 'read', 'Dosya listeleme yetkisi', 1),
    (NEWID(), @Simdi, @SeedUserId, @Simdi, @SeedUserId, 0, 'files.update', 'files', 'update', 'Dosya guncelleme yetkisi', 1),
    (NEWID(), @Simdi, @SeedUserId, @Simdi, @SeedUserId, 0, 'files.delete', 'files', 'delete', 'Dosya silme yetkisi', 1);

-- =============================================
-- MODUL: calendar (Takvim)
-- =============================================
INSERT INTO tnm_permission (Guid, EklemeZamani, EkleyenUserId, DegisiklikZamani, DegistirenUserId, Sil, PermissionKodu, ModulAdi, Aksiyon, Aciklama, Aktif)
VALUES 
    (NEWID(), @Simdi, @SeedUserId, @Simdi, @SeedUserId, 0, 'calendar.create', 'calendar', 'create', 'Takvim kaydi olusturma yetkisi', 1),
    (NEWID(), @Simdi, @SeedUserId, @Simdi, @SeedUserId, 0, 'calendar.read', 'calendar', 'read', 'Takvim listeleme yetkisi', 1),
    (NEWID(), @Simdi, @SeedUserId, @Simdi, @SeedUserId, 0, 'calendar.update', 'calendar', 'update', 'Takvim guncelleme yetkisi', 1),
    (NEWID(), @Simdi, @SeedUserId, @Simdi, @SeedUserId, 0, 'calendar.delete', 'calendar', 'delete', 'Takvim silme yetkisi', 1);

-- =============================================
-- MODUL: stamp_taxes (Damga Vergisi)
-- =============================================
INSERT INTO tnm_permission (Guid, EklemeZamani, EkleyenUserId, DegisiklikZamani, DegistirenUserId, Sil, PermissionKodu, ModulAdi, Aksiyon, Aciklama, Aktif)
VALUES 
    (NEWID(), @Simdi, @SeedUserId, @Simdi, @SeedUserId, 0, 'stamp_taxes.create', 'stamp_taxes', 'create', 'Damga vergisi olusturma yetkisi', 1),
    (NEWID(), @Simdi, @SeedUserId, @Simdi, @SeedUserId, 0, 'stamp_taxes.read', 'stamp_taxes', 'read', 'Damga vergisi listeleme yetkisi', 1),
    (NEWID(), @Simdi, @SeedUserId, @Simdi, @SeedUserId, 0, 'stamp_taxes.update', 'stamp_taxes', 'update', 'Damga vergisi guncelleme yetkisi', 1),
    (NEWID(), @Simdi, @SeedUserId, @Simdi, @SeedUserId, 0, 'stamp_taxes.delete', 'stamp_taxes', 'delete', 'Damga vergisi silme yetkisi', 1);

-- =============================================
-- MODUL: logs (Islem Kayitlari - Sadece Read)
-- =============================================
INSERT INTO tnm_permission (Guid, EklemeZamani, EkleyenUserId, DegisiklikZamani, DegistirenUserId, Sil, PermissionKodu, ModulAdi, Aksiyon, Aciklama, Aktif)
VALUES 
    (NEWID(), @Simdi, @SeedUserId, @Simdi, @SeedUserId, 0, 'logs.read', 'logs', 'read', 'Islem kayitlarini goruntuleme yetkisi', 1);

-- =============================================
-- MODUL: parameters (Tanimlamalar)
-- =============================================
INSERT INTO tnm_permission (Guid, EklemeZamani, EkleyenUserId, DegisiklikZamani, DegistirenUserId, Sil, PermissionKodu, ModulAdi, Aksiyon, Aciklama, Aktif)
VALUES 
    (NEWID(), @Simdi, @SeedUserId, @Simdi, @SeedUserId, 0, 'parameters.create', 'parameters', 'create', 'Parametre olusturma yetkisi', 1),
    (NEWID(), @Simdi, @SeedUserId, @Simdi, @SeedUserId, 0, 'parameters.read', 'parameters', 'read', 'Parametre listeleme yetkisi', 1),
    (NEWID(), @Simdi, @SeedUserId, @Simdi, @SeedUserId, 0, 'parameters.update', 'parameters', 'update', 'Parametre guncelleme yetkisi', 1),
    (NEWID(), @Simdi, @SeedUserId, @Simdi, @SeedUserId, 0, 'parameters.delete', 'parameters', 'delete', 'Parametre silme yetkisi', 1);

-- =============================================
-- MODUL: dashboard (Ana Sayfa)
-- =============================================
INSERT INTO tnm_permission (Guid, EklemeZamani, EkleyenUserId, DegisiklikZamani, DegistirenUserId, Sil, PermissionKodu, ModulAdi, Aksiyon, Aciklama, Aktif)
VALUES 
    (NEWID(), @Simdi, @SeedUserId, @Simdi, @SeedUserId, 0, 'dashboard.read', 'dashboard', 'read', 'Dashboard goruntuleme yetkisi', 1);

-- =============================================
-- MODUL: alarms (Alarmlar)
-- =============================================
INSERT INTO tnm_permission (Guid, EklemeZamani, EkleyenUserId, DegisiklikZamani, DegistirenUserId, Sil, PermissionKodu, ModulAdi, Aksiyon, Aciklama, Aktif)
VALUES 
    (NEWID(), @Simdi, @SeedUserId, @Simdi, @SeedUserId, 0, 'alarms.read', 'alarms', 'read', 'Alarm listeleme yetkisi', 1);

PRINT 'Permission seed data eklendi.';
GO
