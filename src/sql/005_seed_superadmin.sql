-- Süper Admin Seeder
-- Kullanıcı Adı: superadmin
-- Parola: Super123!
-- Rol: superadmin

IF OBJECT_ID('tnm_user', 'U') IS NOT NULL
BEGIN
    DECLARE @Simdi DATETIME2(0) = SYSUTCDATETIME();

    IF EXISTS (SELECT 1 FROM tnm_user WHERE KullaniciAdi = 'superadmin' AND Sil = 0)
    BEGIN
        UPDATE tnm_user
        SET Parola = '$2y$10$bMnYukw1EYylEvP7voEmpurAaYt59dKUk3V7MqpUeXWkrN1o2.ytW',
            AdSoyad = 'Sistem Yöneticisi',
            Aktif = 1,
            Rol = 'superadmin',
            DegisiklikZamani = @Simdi
        WHERE KullaniciAdi = 'superadmin' AND Sil = 0;
    END
    ELSE
    BEGIN
        INSERT INTO tnm_user (KullaniciAdi, Parola, AdSoyad, Aktif, Rol)
        VALUES (
            'superadmin',
            '$2y$10$bMnYukw1EYylEvP7voEmpurAaYt59dKUk3V7MqpUeXWkrN1o2.ytW', -- Super123!
            'Sistem Yöneticisi',
            1,
            'superadmin'
        );
    END
END
