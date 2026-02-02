-- =============================================
-- Türkiye İl ve İlçeleri Seed Data
-- =============================================

-- Önce mevcut verileri kontrol et
IF NOT EXISTS (SELECT 1 FROM tnm_sehir WHERE Sil = 0)
BEGIN
    PRINT 'Il verileri ekleniyor...';

    -- İller (81 il)
    INSERT INTO tnm_sehir (PlakaKodu, Ad, Bolge) VALUES
    ('01', 'Adana', 'Akdeniz'),
    ('02', 'Adıyaman', 'Güneydoğu Anadolu'),
    ('03', 'Afyonkarahisar', 'Ege'),
    ('04', 'Ağrı', 'Doğu Anadolu'),
    ('05', 'Amasya', 'Karadeniz'),
    ('06', 'Ankara', 'İç Anadolu'),
    ('07', 'Antalya', 'Akdeniz'),
    ('08', 'Artvin', 'Karadeniz'),
    ('09', 'Aydın', 'Ege'),
    ('10', 'Balıkesir', 'Marmara'),
    ('11', 'Bilecik', 'Marmara'),
    ('12', 'Bingöl', 'Doğu Anadolu'),
    ('13', 'Bitlis', 'Doğu Anadolu'),
    ('14', 'Bolu', 'Karadeniz'),
    ('15', 'Burdur', 'Akdeniz'),
    ('16', 'Bursa', 'Marmara'),
    ('17', 'Çanakkale', 'Marmara'),
    ('18', 'Çankırı', 'İç Anadolu'),
    ('19', 'Çorum', 'Karadeniz'),
    ('20', 'Denizli', 'Ege'),
    ('21', 'Diyarbakır', 'Güneydoğu Anadolu'),
    ('22', 'Edirne', 'Marmara'),
    ('23', 'Elazığ', 'Doğu Anadolu'),
    ('24', 'Erzincan', 'Doğu Anadolu'),
    ('25', 'Erzurum', 'Doğu Anadolu'),
    ('26', 'Eskişehir', 'İç Anadolu'),
    ('27', 'Gaziantep', 'Güneydoğu Anadolu'),
    ('28', 'Giresun', 'Karadeniz'),
    ('29', 'Gümüşhane', 'Karadeniz'),
    ('30', 'Hakkari', 'Doğu Anadolu'),
    ('31', 'Hatay', 'Akdeniz'),
    ('32', 'Isparta', 'Akdeniz'),
    ('33', 'Mersin', 'Akdeniz'),
    ('34', 'İstanbul', 'Marmara'),
    ('35', 'İzmir', 'Ege'),
    ('36', 'Kars', 'Doğu Anadolu'),
    ('37', 'Kastamonu', 'Karadeniz'),
    ('38', 'Kayseri', 'İç Anadolu'),
    ('39', 'Kırklareli', 'Marmara'),
    ('40', 'Kırşehir', 'İç Anadolu'),
    ('41', 'Kocaeli', 'Marmara'),
    ('42', 'Konya', 'İç Anadolu'),
    ('43', 'Kütahya', 'Ege'),
    ('44', 'Malatya', 'Doğu Anadolu'),
    ('45', 'Manisa', 'Ege'),
    ('46', 'Kahramanmaraş', 'Akdeniz'),
    ('47', 'Mardin', 'Güneydoğu Anadolu'),
    ('48', 'Muğla', 'Ege'),
    ('49', 'Muş', 'Doğu Anadolu'),
    ('50', 'Nevşehir', 'İç Anadolu'),
    ('51', 'Niğde', 'İç Anadolu'),
    ('52', 'Ordu', 'Karadeniz'),
    ('53', 'Rize', 'Karadeniz'),
    ('54', 'Sakarya', 'Marmara'),
    ('55', 'Samsun', 'Karadeniz'),
    ('56', 'Siirt', 'Güneydoğu Anadolu'),
    ('57', 'Sinop', 'Karadeniz'),
    ('58', 'Sivas', 'İç Anadolu'),
    ('59', 'Tekirdağ', 'Marmara'),
    ('60', 'Tokat', 'Karadeniz'),
    ('61', 'Trabzon', 'Karadeniz'),
    ('62', 'Tunceli', 'Doğu Anadolu'),
    ('63', 'Şanlıurfa', 'Güneydoğu Anadolu'),
    ('64', 'Uşak', 'Ege'),
    ('65', 'Van', 'Doğu Anadolu'),
    ('66', 'Yozgat', 'İç Anadolu'),
    ('67', 'Zonguldak', 'Karadeniz'),
    ('68', 'Aksaray', 'İç Anadolu'),
    ('69', 'Bayburt', 'Karadeniz'),
    ('70', 'Karaman', 'İç Anadolu'),
    ('71', 'Kırıkkale', 'İç Anadolu'),
    ('72', 'Batman', 'Güneydoğu Anadolu'),
    ('73', 'Şırnak', 'Güneydoğu Anadolu'),
    ('74', 'Bartın', 'Karadeniz'),
    ('75', 'Ardahan', 'Doğu Anadolu'),
    ('76', 'Iğdır', 'Doğu Anadolu'),
    ('77', 'Yalova', 'Marmara'),
    ('78', 'Karabük', 'Karadeniz'),
    ('79', 'Kilis', 'Güneydoğu Anadolu'),
    ('80', 'Osmaniye', 'Akdeniz'),
    ('81', 'Düzce', 'Karadeniz');

    PRINT '81 il eklendi.';
END
ELSE
BEGIN
    PRINT 'Il verileri zaten mevcut.';
END
GO

-- İlçeler (Her ilin merkez ilçesi ekleniyor - detaylı liste için ayrıca genişletilebilir)
IF NOT EXISTS (SELECT 1 FROM tnm_ilce WHERE Sil = 0)
BEGIN
    PRINT 'Ilce verileri ekleniyor...';

    -- Her il için merkez ilçe ve bazı büyük ilçeler
    -- İstanbul ilçeleri
    DECLARE @IstanbulId INT = (SELECT Id FROM tnm_sehir WHERE PlakaKodu = '34');
    INSERT INTO tnm_ilce (SehirId, Ad) VALUES
    (@IstanbulId, 'Adalar'),
    (@IstanbulId, 'Arnavutköy'),
    (@IstanbulId, 'Ataşehir'),
    (@IstanbulId, 'Avcılar'),
    (@IstanbulId, 'Bağcılar'),
    (@IstanbulId, 'Bahçelievler'),
    (@IstanbulId, 'Bakırköy'),
    (@IstanbulId, 'Başakşehir'),
    (@IstanbulId, 'Bayrampaşa'),
    (@IstanbulId, 'Beşiktaş'),
    (@IstanbulId, 'Beykoz'),
    (@IstanbulId, 'Beylikdüzü'),
    (@IstanbulId, 'Beyoğlu'),
    (@IstanbulId, 'Büyükçekmece'),
    (@IstanbulId, 'Çatalca'),
    (@IstanbulId, 'Çekmeköy'),
    (@IstanbulId, 'Esenler'),
    (@IstanbulId, 'Esenyurt'),
    (@IstanbulId, 'Eyüpsultan'),
    (@IstanbulId, 'Fatih'),
    (@IstanbulId, 'Gaziosmanpaşa'),
    (@IstanbulId, 'Güngören'),
    (@IstanbulId, 'Kadıköy'),
    (@IstanbulId, 'Kağıthane'),
    (@IstanbulId, 'Kartal'),
    (@IstanbulId, 'Küçükçekmece'),
    (@IstanbulId, 'Maltepe'),
    (@IstanbulId, 'Pendik'),
    (@IstanbulId, 'Sancaktepe'),
    (@IstanbulId, 'Sarıyer'),
    (@IstanbulId, 'Silivri'),
    (@IstanbulId, 'Sultanbeyli'),
    (@IstanbulId, 'Sultangazi'),
    (@IstanbulId, 'Şile'),
    (@IstanbulId, 'Şişli'),
    (@IstanbulId, 'Tuzla'),
    (@IstanbulId, 'Ümraniye'),
    (@IstanbulId, 'Üsküdar'),
    (@IstanbulId, 'Zeytinburnu');

    -- Ankara ilçeleri
    DECLARE @AnkaraId INT = (SELECT Id FROM tnm_sehir WHERE PlakaKodu = '06');
    INSERT INTO tnm_ilce (SehirId, Ad) VALUES
    (@AnkaraId, 'Akyurt'),
    (@AnkaraId, 'Altındağ'),
    (@AnkaraId, 'Ayaş'),
    (@AnkaraId, 'Bala'),
    (@AnkaraId, 'Beypazarı'),
    (@AnkaraId, 'Çamlıdere'),
    (@AnkaraId, 'Çankaya'),
    (@AnkaraId, 'Çubuk'),
    (@AnkaraId, 'Elmadağ'),
    (@AnkaraId, 'Etimesgut'),
    (@AnkaraId, 'Evren'),
    (@AnkaraId, 'Gölbaşı'),
    (@AnkaraId, 'Güdül'),
    (@AnkaraId, 'Haymana'),
    (@AnkaraId, 'Kalecik'),
    (@AnkaraId, 'Kahramankazan'),
    (@AnkaraId, 'Keçiören'),
    (@AnkaraId, 'Kızılcahamam'),
    (@AnkaraId, 'Mamak'),
    (@AnkaraId, 'Nallıhan'),
    (@AnkaraId, 'Polatlı'),
    (@AnkaraId, 'Pursaklar'),
    (@AnkaraId, 'Sincan'),
    (@AnkaraId, 'Şereflikoçhisar'),
    (@AnkaraId, 'Yenimahalle');

    -- İzmir ilçeleri
    DECLARE @IzmirId INT = (SELECT Id FROM tnm_sehir WHERE PlakaKodu = '35');
    INSERT INTO tnm_ilce (SehirId, Ad) VALUES
    (@IzmirId, 'Aliağa'),
    (@IzmirId, 'Balçova'),
    (@IzmirId, 'Bayındır'),
    (@IzmirId, 'Bayraklı'),
    (@IzmirId, 'Bergama'),
    (@IzmirId, 'Beydağ'),
    (@IzmirId, 'Bornova'),
    (@IzmirId, 'Buca'),
    (@IzmirId, 'Çeşme'),
    (@IzmirId, 'Çiğli'),
    (@IzmirId, 'Dikili'),
    (@IzmirId, 'Foça'),
    (@IzmirId, 'Gaziemir'),
    (@IzmirId, 'Güzelbahçe'),
    (@IzmirId, 'Karabağlar'),
    (@IzmirId, 'Karaburun'),
    (@IzmirId, 'Karşıyaka'),
    (@IzmirId, 'Kemalpaşa'),
    (@IzmirId, 'Kınık'),
    (@IzmirId, 'Kiraz'),
    (@IzmirId, 'Konak'),
    (@IzmirId, 'Menderes'),
    (@IzmirId, 'Menemen'),
    (@IzmirId, 'Narlıdere'),
    (@IzmirId, 'Ödemiş'),
    (@IzmirId, 'Seferihisar'),
    (@IzmirId, 'Selçuk'),
    (@IzmirId, 'Tire'),
    (@IzmirId, 'Torbalı'),
    (@IzmirId, 'Urla');

    -- Bursa ilçeleri
    DECLARE @BursaId INT = (SELECT Id FROM tnm_sehir WHERE PlakaKodu = '16');
    INSERT INTO tnm_ilce (SehirId, Ad) VALUES
    (@BursaId, 'Büyükorhan'),
    (@BursaId, 'Gemlik'),
    (@BursaId, 'Gürsu'),
    (@BursaId, 'Harmancık'),
    (@BursaId, 'İnegöl'),
    (@BursaId, 'İznik'),
    (@BursaId, 'Karacabey'),
    (@BursaId, 'Keles'),
    (@BursaId, 'Kestel'),
    (@BursaId, 'Mudanya'),
    (@BursaId, 'Mustafakemalpaşa'),
    (@BursaId, 'Nilüfer'),
    (@BursaId, 'Orhaneli'),
    (@BursaId, 'Orhangazi'),
    (@BursaId, 'Osmangazi'),
    (@BursaId, 'Yenişehir'),
    (@BursaId, 'Yıldırım');

    -- Antalya ilçeleri
    DECLARE @AntalyaId INT = (SELECT Id FROM tnm_sehir WHERE PlakaKodu = '07');
    INSERT INTO tnm_ilce (SehirId, Ad) VALUES
    (@AntalyaId, 'Akseki'),
    (@AntalyaId, 'Aksu'),
    (@AntalyaId, 'Alanya'),
    (@AntalyaId, 'Demre'),
    (@AntalyaId, 'Döşemealtı'),
    (@AntalyaId, 'Elmalı'),
    (@AntalyaId, 'Finike'),
    (@AntalyaId, 'Gazipaşa'),
    (@AntalyaId, 'Gündoğmuş'),
    (@AntalyaId, 'İbradı'),
    (@AntalyaId, 'Kaş'),
    (@AntalyaId, 'Kemer'),
    (@AntalyaId, 'Kepez'),
    (@AntalyaId, 'Konyaaltı'),
    (@AntalyaId, 'Korkuteli'),
    (@AntalyaId, 'Kumluca'),
    (@AntalyaId, 'Manavgat'),
    (@AntalyaId, 'Muratpaşa'),
    (@AntalyaId, 'Serik');

    -- Diğer iller için merkez ilçe ekleme (kalan 76 il)
    DECLARE @SehirCursor CURSOR;
    DECLARE @SehirId INT;
    DECLARE @SehirAd NVARCHAR(50);

    SET @SehirCursor = CURSOR FOR
        SELECT Id, Ad FROM tnm_sehir
        WHERE PlakaKodu NOT IN ('06', '07', '16', '34', '35') AND Sil = 0;

    OPEN @SehirCursor;
    FETCH NEXT FROM @SehirCursor INTO @SehirId, @SehirAd;

    WHILE @@FETCH_STATUS = 0
    BEGIN
        -- Her il için merkez ilçe ekle
        IF NOT EXISTS (SELECT 1 FROM tnm_ilce WHERE SehirId = @SehirId AND Ad = 'Merkez')
        BEGIN
            INSERT INTO tnm_ilce (SehirId, Ad) VALUES (@SehirId, 'Merkez');
        END

        FETCH NEXT FROM @SehirCursor INTO @SehirId, @SehirAd;
    END

    CLOSE @SehirCursor;
    DEALLOCATE @SehirCursor;

    PRINT 'Ilce verileri eklendi.';
END
ELSE
BEGIN
    PRINT 'Ilce verileri zaten mevcut.';
END
GO

PRINT '======================================';
PRINT 'Il/Ilce seed migration tamamlandi.';
PRINT '======================================';
GO
