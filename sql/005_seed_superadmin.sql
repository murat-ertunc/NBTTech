-- Süper Admin Seeder
-- Kullanıcı Adı: superadmin
-- Parola: Super123!
-- Rol: superadmin

IF NOT EXISTS (SELECT 1 FROM tnm_user WHERE KullaniciAdi = 'superadmin')
BEGIN
    INSERT INTO tnm_user (KullaniciAdi, Parola, AdSoyad, Aktif, Rol)
    VALUES (
        'superadmin',
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- Super123!
        'Sistem Yöneticisi',
        1,
        'superadmin'
    );
END
