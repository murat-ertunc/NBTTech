-- Süper Admin Seeder
-- Kullanıcı Adı: superadmin
-- Parola: Super123!
-- Rol: superadmin

-- Mevcut kullanıcıyı sil ve yeniden oluştur
DELETE FROM tnm_user WHERE KullaniciAdi = 'superadmin';

INSERT INTO tnm_user (KullaniciAdi, Parola, AdSoyad, Aktif, Rol)
VALUES (
    'superadmin',
    '$2y$10$bMnYukw1EYylEvP7voEmpurAaYt59dKUk3V7MqpUeXWkrN1o2.ytW', -- Super123!
    'Sistem Yöneticisi',
    1,
    'superadmin'
);
