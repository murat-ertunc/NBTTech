-- =============================================
-- Türkiye İlçeleri TAM Seed Data (922 ilçe)
-- Idempotent: Tekrar çalıştırılınca duplike üretmez
-- =============================================

-- Önce mevcut ilçeleri temizle (yeniden seed için)
-- Bu işlem idempotent olması için: varsa sil, yoksa geç
DELETE FROM tnm_ilce WHERE Sil = 0;
GO

-- İlçe ekleme fonksiyonu
-- Her il için ilçelerini ekle

-- ===================== ADANA (01) =====================
DECLARE @AdanaId INT = (SELECT Id FROM tnm_sehir WHERE PlakaKodu = '01');
INSERT INTO tnm_ilce (SehirId, Ad) VALUES
(@AdanaId, 'Aladağ'), (@AdanaId, 'Ceyhan'), (@AdanaId, 'Çukurova'), (@AdanaId, 'Feke'),
(@AdanaId, 'İmamoğlu'), (@AdanaId, 'Karaisalı'), (@AdanaId, 'Karataş'), (@AdanaId, 'Kozan'),
(@AdanaId, 'Pozantı'), (@AdanaId, 'Saimbeyli'), (@AdanaId, 'Sarıçam'), (@AdanaId, 'Seyhan'),
(@AdanaId, 'Tufanbeyli'), (@AdanaId, 'Yumurtalık'), (@AdanaId, 'Yüreğir');
GO

-- ===================== ADIYAMAN (02) =====================
DECLARE @AdiyamanId INT = (SELECT Id FROM tnm_sehir WHERE PlakaKodu = '02');
INSERT INTO tnm_ilce (SehirId, Ad) VALUES
(@AdiyamanId, 'Besni'), (@AdiyamanId, 'Çelikhan'), (@AdiyamanId, 'Gerger'), (@AdiyamanId, 'Gölbaşı'),
(@AdiyamanId, 'Kahta'), (@AdiyamanId, 'Merkez'), (@AdiyamanId, 'Samsat'), (@AdiyamanId, 'Sincik'),
(@AdiyamanId, 'Tut');
GO

-- ===================== AFYONKARAHİSAR (03) =====================
DECLARE @AfyonId INT = (SELECT Id FROM tnm_sehir WHERE PlakaKodu = '03');
INSERT INTO tnm_ilce (SehirId, Ad) VALUES
(@AfyonId, 'Başmakçı'), (@AfyonId, 'Bayat'), (@AfyonId, 'Bolvadin'), (@AfyonId, 'Çay'),
(@AfyonId, 'Çobanlar'), (@AfyonId, 'Dazkırı'), (@AfyonId, 'Dinar'), (@AfyonId, 'Emirdağ'),
(@AfyonId, 'Evciler'), (@AfyonId, 'Hocalar'), (@AfyonId, 'İhsaniye'), (@AfyonId, 'İscehisar'),
(@AfyonId, 'Kızılören'), (@AfyonId, 'Merkez'), (@AfyonId, 'Sandıklı'), (@AfyonId, 'Sinanpaşa'),
(@AfyonId, 'Sultandağı'), (@AfyonId, 'Şuhut');
GO

-- ===================== AĞRI (04) =====================
DECLARE @AgriId INT = (SELECT Id FROM tnm_sehir WHERE PlakaKodu = '04');
INSERT INTO tnm_ilce (SehirId, Ad) VALUES
(@AgriId, 'Diyadin'), (@AgriId, 'Doğubayazıt'), (@AgriId, 'Eleşkirt'), (@AgriId, 'Hamur'),
(@AgriId, 'Merkez'), (@AgriId, 'Patnos'), (@AgriId, 'Taşlıçay'), (@AgriId, 'Tutak');
GO

-- ===================== AMASYA (05) =====================
DECLARE @AmasyaId INT = (SELECT Id FROM tnm_sehir WHERE PlakaKodu = '05');
INSERT INTO tnm_ilce (SehirId, Ad) VALUES
(@AmasyaId, 'Göynücek'), (@AmasyaId, 'Gümüşhacıköy'), (@AmasyaId, 'Hamamözü'), (@AmasyaId, 'Merkez'),
(@AmasyaId, 'Merzifon'), (@AmasyaId, 'Suluova'), (@AmasyaId, 'Taşova');
GO

-- ===================== ANKARA (06) =====================
DECLARE @AnkaraId INT = (SELECT Id FROM tnm_sehir WHERE PlakaKodu = '06');
INSERT INTO tnm_ilce (SehirId, Ad) VALUES
(@AnkaraId, 'Akyurt'), (@AnkaraId, 'Altındağ'), (@AnkaraId, 'Ayaş'), (@AnkaraId, 'Bala'),
(@AnkaraId, 'Beypazarı'), (@AnkaraId, 'Çamlıdere'), (@AnkaraId, 'Çankaya'), (@AnkaraId, 'Çubuk'),
(@AnkaraId, 'Elmadağ'), (@AnkaraId, 'Etimesgut'), (@AnkaraId, 'Evren'), (@AnkaraId, 'Gölbaşı'),
(@AnkaraId, 'Güdül'), (@AnkaraId, 'Haymana'), (@AnkaraId, 'Kahramankazan'), (@AnkaraId, 'Kalecik'),
(@AnkaraId, 'Keçiören'), (@AnkaraId, 'Kızılcahamam'), (@AnkaraId, 'Mamak'), (@AnkaraId, 'Nallıhan'),
(@AnkaraId, 'Polatlı'), (@AnkaraId, 'Pursaklar'), (@AnkaraId, 'Sincan'), (@AnkaraId, 'Şereflikoçhisar'),
(@AnkaraId, 'Yenimahalle');
GO

-- ===================== ANTALYA (07) =====================
DECLARE @AntalyaId INT = (SELECT Id FROM tnm_sehir WHERE PlakaKodu = '07');
INSERT INTO tnm_ilce (SehirId, Ad) VALUES
(@AntalyaId, 'Akseki'), (@AntalyaId, 'Aksu'), (@AntalyaId, 'Alanya'), (@AntalyaId, 'Demre'),
(@AntalyaId, 'Döşemealtı'), (@AntalyaId, 'Elmalı'), (@AntalyaId, 'Finike'), (@AntalyaId, 'Gazipaşa'),
(@AntalyaId, 'Gündoğmuş'), (@AntalyaId, 'İbradı'), (@AntalyaId, 'Kaş'), (@AntalyaId, 'Kemer'),
(@AntalyaId, 'Kepez'), (@AntalyaId, 'Konyaaltı'), (@AntalyaId, 'Korkuteli'), (@AntalyaId, 'Kumluca'),
(@AntalyaId, 'Manavgat'), (@AntalyaId, 'Muratpaşa'), (@AntalyaId, 'Serik');
GO

-- ===================== ARTVİN (08) =====================
DECLARE @ArtvinId INT = (SELECT Id FROM tnm_sehir WHERE PlakaKodu = '08');
INSERT INTO tnm_ilce (SehirId, Ad) VALUES
(@ArtvinId, 'Ardanuç'), (@ArtvinId, 'Arhavi'), (@ArtvinId, 'Borçka'), (@ArtvinId, 'Hopa'),
(@ArtvinId, 'Kemalpaşa'), (@ArtvinId, 'Merkez'), (@ArtvinId, 'Murgul'), (@ArtvinId, 'Şavşat'),
(@ArtvinId, 'Yusufeli');
GO

-- ===================== AYDIN (09) =====================
DECLARE @AydinId INT = (SELECT Id FROM tnm_sehir WHERE PlakaKodu = '09');
INSERT INTO tnm_ilce (SehirId, Ad) VALUES
(@AydinId, 'Bozdoğan'), (@AydinId, 'Buharkent'), (@AydinId, 'Çine'), (@AydinId, 'Didim'),
(@AydinId, 'Efeler'), (@AydinId, 'Germencik'), (@AydinId, 'İncirliova'), (@AydinId, 'Karacasu'),
(@AydinId, 'Karpuzlu'), (@AydinId, 'Koçarlı'), (@AydinId, 'Köşk'), (@AydinId, 'Kuşadası'),
(@AydinId, 'Kuyucak'), (@AydinId, 'Nazilli'), (@AydinId, 'Söke'), (@AydinId, 'Sultanhisar'),
(@AydinId, 'Yenipazar');
GO

-- ===================== BALIKESİR (10) =====================
DECLARE @BalikesirId INT = (SELECT Id FROM tnm_sehir WHERE PlakaKodu = '10');
INSERT INTO tnm_ilce (SehirId, Ad) VALUES
(@BalikesirId, 'Altıeylül'), (@BalikesirId, 'Ayvalık'), (@BalikesirId, 'Balya'), (@BalikesirId, 'Bandırma'),
(@BalikesirId, 'Bigadiç'), (@BalikesirId, 'Burhaniye'), (@BalikesirId, 'Dursunbey'), (@BalikesirId, 'Edremit'),
(@BalikesirId, 'Erdek'), (@BalikesirId, 'Gömeç'), (@BalikesirId, 'Gönen'), (@BalikesirId, 'Havran'),
(@BalikesirId, 'İvrindi'), (@BalikesirId, 'Karesi'), (@BalikesirId, 'Kepsut'), (@BalikesirId, 'Manyas'),
(@BalikesirId, 'Marmara'), (@BalikesirId, 'Savaştepe'), (@BalikesirId, 'Sındırgı'), (@BalikesirId, 'Susurluk');
GO

-- ===================== BİLECİK (11) =====================
DECLARE @BilecikId INT = (SELECT Id FROM tnm_sehir WHERE PlakaKodu = '11');
INSERT INTO tnm_ilce (SehirId, Ad) VALUES
(@BilecikId, 'Bozüyük'), (@BilecikId, 'Gölpazarı'), (@BilecikId, 'İnhisar'), (@BilecikId, 'Merkez'),
(@BilecikId, 'Osmaneli'), (@BilecikId, 'Pazaryeri'), (@BilecikId, 'Söğüt'), (@BilecikId, 'Yenipazar');
GO

-- ===================== BİNGÖL (12) =====================
DECLARE @BingolId INT = (SELECT Id FROM tnm_sehir WHERE PlakaKodu = '12');
INSERT INTO tnm_ilce (SehirId, Ad) VALUES
(@BingolId, 'Adaklı'), (@BingolId, 'Genç'), (@BingolId, 'Karlıova'), (@BingolId, 'Kiğı'),
(@BingolId, 'Merkez'), (@BingolId, 'Solhan'), (@BingolId, 'Yayladere'), (@BingolId, 'Yedisu');
GO

-- ===================== BİTLİS (13) =====================
DECLARE @BitlisId INT = (SELECT Id FROM tnm_sehir WHERE PlakaKodu = '13');
INSERT INTO tnm_ilce (SehirId, Ad) VALUES
(@BitlisId, 'Adilcevaz'), (@BitlisId, 'Ahlat'), (@BitlisId, 'Güroymak'), (@BitlisId, 'Hizan'),
(@BitlisId, 'Merkez'), (@BitlisId, 'Mutki'), (@BitlisId, 'Tatvan');
GO

-- ===================== BOLU (14) =====================
DECLARE @BoluId INT = (SELECT Id FROM tnm_sehir WHERE PlakaKodu = '14');
INSERT INTO tnm_ilce (SehirId, Ad) VALUES
(@BoluId, 'Dörtdivan'), (@BoluId, 'Gerede'), (@BoluId, 'Göynük'), (@BoluId, 'Kıbrıscık'),
(@BoluId, 'Mengen'), (@BoluId, 'Merkez'), (@BoluId, 'Mudurnu'), (@BoluId, 'Seben'),
(@BoluId, 'Yeniçağa');
GO

-- ===================== BURDUR (15) =====================
DECLARE @BurdurId INT = (SELECT Id FROM tnm_sehir WHERE PlakaKodu = '15');
INSERT INTO tnm_ilce (SehirId, Ad) VALUES
(@BurdurId, 'Ağlasun'), (@BurdurId, 'Altınyayla'), (@BurdurId, 'Bucak'), (@BurdurId, 'Çavdır'),
(@BurdurId, 'Çeltikçi'), (@BurdurId, 'Gölhisar'), (@BurdurId, 'Karamanlı'), (@BurdurId, 'Kemer'),
(@BurdurId, 'Merkez'), (@BurdurId, 'Tefenni'), (@BurdurId, 'Yeşilova');
GO

-- ===================== BURSA (16) =====================
DECLARE @BursaId INT = (SELECT Id FROM tnm_sehir WHERE PlakaKodu = '16');
INSERT INTO tnm_ilce (SehirId, Ad) VALUES
(@BursaId, 'Büyükorhan'), (@BursaId, 'Gemlik'), (@BursaId, 'Gürsu'), (@BursaId, 'Harmancık'),
(@BursaId, 'İnegöl'), (@BursaId, 'İznik'), (@BursaId, 'Karacabey'), (@BursaId, 'Keles'),
(@BursaId, 'Kestel'), (@BursaId, 'Mudanya'), (@BursaId, 'Mustafakemalpaşa'), (@BursaId, 'Nilüfer'),
(@BursaId, 'Orhaneli'), (@BursaId, 'Orhangazi'), (@BursaId, 'Osmangazi'), (@BursaId, 'Yenişehir'),
(@BursaId, 'Yıldırım');
GO

-- ===================== ÇANAKKALE (17) =====================
DECLARE @CanakkaleId INT = (SELECT Id FROM tnm_sehir WHERE PlakaKodu = '17');
INSERT INTO tnm_ilce (SehirId, Ad) VALUES
(@CanakkaleId, 'Ayvacık'), (@CanakkaleId, 'Bayramiç'), (@CanakkaleId, 'Biga'), (@CanakkaleId, 'Bozcaada'),
(@CanakkaleId, 'Çan'), (@CanakkaleId, 'Eceabat'), (@CanakkaleId, 'Ezine'), (@CanakkaleId, 'Gelibolu'),
(@CanakkaleId, 'Gökçeada'), (@CanakkaleId, 'Lapseki'), (@CanakkaleId, 'Merkez'), (@CanakkaleId, 'Yenice');
GO

-- ===================== ÇANKIRI (18) =====================
DECLARE @CankiriId INT = (SELECT Id FROM tnm_sehir WHERE PlakaKodu = '18');
INSERT INTO tnm_ilce (SehirId, Ad) VALUES
(@CankiriId, 'Atkaracalar'), (@CankiriId, 'Bayramören'), (@CankiriId, 'Çerkeş'), (@CankiriId, 'Eldivan'),
(@CankiriId, 'Ilgaz'), (@CankiriId, 'Kızılırmak'), (@CankiriId, 'Korgun'), (@CankiriId, 'Kurşunlu'),
(@CankiriId, 'Merkez'), (@CankiriId, 'Orta'), (@CankiriId, 'Şabanözü'), (@CankiriId, 'Yapraklı');
GO

-- ===================== ÇORUM (19) =====================
DECLARE @CorumId INT = (SELECT Id FROM tnm_sehir WHERE PlakaKodu = '19');
INSERT INTO tnm_ilce (SehirId, Ad) VALUES
(@CorumId, 'Alaca'), (@CorumId, 'Bayat'), (@CorumId, 'Boğazkale'), (@CorumId, 'Dodurga'),
(@CorumId, 'İskilip'), (@CorumId, 'Kargı'), (@CorumId, 'Laçin'), (@CorumId, 'Mecitözü'),
(@CorumId, 'Merkez'), (@CorumId, 'Oğuzlar'), (@CorumId, 'Ortaköy'), (@CorumId, 'Osmancık'),
(@CorumId, 'Sungurlu'), (@CorumId, 'Uğurludağ');
GO

-- ===================== DENİZLİ (20) =====================
DECLARE @DenizliId INT = (SELECT Id FROM tnm_sehir WHERE PlakaKodu = '20');
INSERT INTO tnm_ilce (SehirId, Ad) VALUES
(@DenizliId, 'Acıpayam'), (@DenizliId, 'Babadağ'), (@DenizliId, 'Baklan'), (@DenizliId, 'Bekilli'),
(@DenizliId, 'Beyağaç'), (@DenizliId, 'Bozkurt'), (@DenizliId, 'Buldan'), (@DenizliId, 'Çal'),
(@DenizliId, 'Çameli'), (@DenizliId, 'Çardak'), (@DenizliId, 'Çivril'), (@DenizliId, 'Güney'),
(@DenizliId, 'Honaz'), (@DenizliId, 'Kale'), (@DenizliId, 'Merkezefendi'), (@DenizliId, 'Pamukkale'),
(@DenizliId, 'Sarayköy'), (@DenizliId, 'Serinhisar'), (@DenizliId, 'Tavas');
GO

-- ===================== DİYARBAKIR (21) =====================
DECLARE @DiyarbakirId INT = (SELECT Id FROM tnm_sehir WHERE PlakaKodu = '21');
INSERT INTO tnm_ilce (SehirId, Ad) VALUES
(@DiyarbakirId, 'Bağlar'), (@DiyarbakirId, 'Bismil'), (@DiyarbakirId, 'Çermik'), (@DiyarbakirId, 'Çınar'),
(@DiyarbakirId, 'Çüngüş'), (@DiyarbakirId, 'Dicle'), (@DiyarbakirId, 'Eğil'), (@DiyarbakirId, 'Ergani'),
(@DiyarbakirId, 'Hani'), (@DiyarbakirId, 'Hazro'), (@DiyarbakirId, 'Kayapınar'), (@DiyarbakirId, 'Kocaköy'),
(@DiyarbakirId, 'Kulp'), (@DiyarbakirId, 'Lice'), (@DiyarbakirId, 'Silvan'), (@DiyarbakirId, 'Sur'),
(@DiyarbakirId, 'Yenişehir');
GO

-- ===================== EDİRNE (22) =====================
DECLARE @EdirneId INT = (SELECT Id FROM tnm_sehir WHERE PlakaKodu = '22');
INSERT INTO tnm_ilce (SehirId, Ad) VALUES
(@EdirneId, 'Enez'), (@EdirneId, 'Havsa'), (@EdirneId, 'İpsala'), (@EdirneId, 'Keşan'),
(@EdirneId, 'Lalapaşa'), (@EdirneId, 'Meriç'), (@EdirneId, 'Merkez'), (@EdirneId, 'Süloğlu'),
(@EdirneId, 'Uzunköprü');
GO

-- ===================== ELAZIĞ (23) =====================
DECLARE @ElazigId INT = (SELECT Id FROM tnm_sehir WHERE PlakaKodu = '23');
INSERT INTO tnm_ilce (SehirId, Ad) VALUES
(@ElazigId, 'Ağın'), (@ElazigId, 'Alacakaya'), (@ElazigId, 'Arıcak'), (@ElazigId, 'Baskil'),
(@ElazigId, 'Karakoçan'), (@ElazigId, 'Keban'), (@ElazigId, 'Kovancılar'), (@ElazigId, 'Maden'),
(@ElazigId, 'Merkez'), (@ElazigId, 'Palu'), (@ElazigId, 'Sivrice');
GO

-- ===================== ERZİNCAN (24) =====================
DECLARE @ErzincanId INT = (SELECT Id FROM tnm_sehir WHERE PlakaKodu = '24');
INSERT INTO tnm_ilce (SehirId, Ad) VALUES
(@ErzincanId, 'Çayırlı'), (@ErzincanId, 'İliç'), (@ErzincanId, 'Kemah'), (@ErzincanId, 'Kemaliye'),
(@ErzincanId, 'Merkez'), (@ErzincanId, 'Otlukbeli'), (@ErzincanId, 'Refahiye'), (@ErzincanId, 'Tercan'),
(@ErzincanId, 'Üzümlü');
GO

-- ===================== ERZURUM (25) =====================
DECLARE @ErzurumId INT = (SELECT Id FROM tnm_sehir WHERE PlakaKodu = '25');
INSERT INTO tnm_ilce (SehirId, Ad) VALUES
(@ErzurumId, 'Aşkale'), (@ErzurumId, 'Aziziye'), (@ErzurumId, 'Çat'), (@ErzurumId, 'Hınıs'),
(@ErzurumId, 'Horasan'), (@ErzurumId, 'İspir'), (@ErzurumId, 'Karaçoban'), (@ErzurumId, 'Karayazı'),
(@ErzurumId, 'Köprüköy'), (@ErzurumId, 'Narman'), (@ErzurumId, 'Oltu'), (@ErzurumId, 'Olur'),
(@ErzurumId, 'Palandöken'), (@ErzurumId, 'Pasinler'), (@ErzurumId, 'Pazaryolu'), (@ErzurumId, 'Şenkaya'),
(@ErzurumId, 'Tekman'), (@ErzurumId, 'Tortum'), (@ErzurumId, 'Uzundere'), (@ErzurumId, 'Yakutiye');
GO

-- ===================== ESKİŞEHİR (26) =====================
DECLARE @EskisehirId INT = (SELECT Id FROM tnm_sehir WHERE PlakaKodu = '26');
INSERT INTO tnm_ilce (SehirId, Ad) VALUES
(@EskisehirId, 'Alpu'), (@EskisehirId, 'Beylikova'), (@EskisehirId, 'Çifteler'), (@EskisehirId, 'Günyüzü'),
(@EskisehirId, 'Han'), (@EskisehirId, 'İnönü'), (@EskisehirId, 'Mahmudiye'), (@EskisehirId, 'Mihalgazi'),
(@EskisehirId, 'Mihalıççık'), (@EskisehirId, 'Odunpazarı'), (@EskisehirId, 'Sarıcakaya'), (@EskisehirId, 'Seyitgazi'),
(@EskisehirId, 'Sivrihisar'), (@EskisehirId, 'Tepebaşı');
GO

-- ===================== GAZİANTEP (27) =====================
DECLARE @GaziantepId INT = (SELECT Id FROM tnm_sehir WHERE PlakaKodu = '27');
INSERT INTO tnm_ilce (SehirId, Ad) VALUES
(@GaziantepId, 'Araban'), (@GaziantepId, 'İslahiye'), (@GaziantepId, 'Karkamış'), (@GaziantepId, 'Nizip'),
(@GaziantepId, 'Nurdağı'), (@GaziantepId, 'Oğuzeli'), (@GaziantepId, 'Şahinbey'), (@GaziantepId, 'Şehitkamil'),
(@GaziantepId, 'Yavuzeli');
GO

-- ===================== GİRESUN (28) =====================
DECLARE @GiresunId INT = (SELECT Id FROM tnm_sehir WHERE PlakaKodu = '28');
INSERT INTO tnm_ilce (SehirId, Ad) VALUES
(@GiresunId, 'Alucra'), (@GiresunId, 'Bulancak'), (@GiresunId, 'Çamoluk'), (@GiresunId, 'Çanakçı'),
(@GiresunId, 'Dereli'), (@GiresunId, 'Doğankent'), (@GiresunId, 'Espiye'), (@GiresunId, 'Eynesil'),
(@GiresunId, 'Görele'), (@GiresunId, 'Güce'), (@GiresunId, 'Keşap'), (@GiresunId, 'Merkez'),
(@GiresunId, 'Piraziz'), (@GiresunId, 'Şebinkarahisar'), (@GiresunId, 'Tirebolu'), (@GiresunId, 'Yağlıdere');
GO

-- ===================== GÜMÜŞHANE (29) =====================
DECLARE @GumushaneId INT = (SELECT Id FROM tnm_sehir WHERE PlakaKodu = '29');
INSERT INTO tnm_ilce (SehirId, Ad) VALUES
(@GumushaneId, 'Kelkit'), (@GumushaneId, 'Köse'), (@GumushaneId, 'Kürtün'), (@GumushaneId, 'Merkez'),
(@GumushaneId, 'Şiran'), (@GumushaneId, 'Torul');
GO

-- ===================== HAKKARİ (30) =====================
DECLARE @HakkariId INT = (SELECT Id FROM tnm_sehir WHERE PlakaKodu = '30');
INSERT INTO tnm_ilce (SehirId, Ad) VALUES
(@HakkariId, 'Çukurca'), (@HakkariId, 'Merkez'), (@HakkariId, 'Şemdinli'), (@HakkariId, 'Yüksekova');
GO

-- ===================== HATAY (31) =====================
DECLARE @HatayId INT = (SELECT Id FROM tnm_sehir WHERE PlakaKodu = '31');
INSERT INTO tnm_ilce (SehirId, Ad) VALUES
(@HatayId, 'Altınözü'), (@HatayId, 'Antakya'), (@HatayId, 'Arsuz'), (@HatayId, 'Belen'),
(@HatayId, 'Defne'), (@HatayId, 'Dörtyol'), (@HatayId, 'Erzin'), (@HatayId, 'Hassa'),
(@HatayId, 'İskenderun'), (@HatayId, 'Kırıkhan'), (@HatayId, 'Kumlu'), (@HatayId, 'Payas'),
(@HatayId, 'Reyhanlı'), (@HatayId, 'Samandağ'), (@HatayId, 'Yayladağı');
GO

-- ===================== ISPARTA (32) =====================
DECLARE @IspartaId INT = (SELECT Id FROM tnm_sehir WHERE PlakaKodu = '32');
INSERT INTO tnm_ilce (SehirId, Ad) VALUES
(@IspartaId, 'Aksu'), (@IspartaId, 'Atabey'), (@IspartaId, 'Eğirdir'), (@IspartaId, 'Gelendost'),
(@IspartaId, 'Gönen'), (@IspartaId, 'Keçiborlu'), (@IspartaId, 'Merkez'), (@IspartaId, 'Senirkent'),
(@IspartaId, 'Sütçüler'), (@IspartaId, 'Şarkikaraağaç'), (@IspartaId, 'Uluborlu'), (@IspartaId, 'Yalvaç'),
(@IspartaId, 'Yenişarbademli');
GO

-- ===================== MERSİN (33) =====================
DECLARE @MersinId INT = (SELECT Id FROM tnm_sehir WHERE PlakaKodu = '33');
INSERT INTO tnm_ilce (SehirId, Ad) VALUES
(@MersinId, 'Akdeniz'), (@MersinId, 'Anamur'), (@MersinId, 'Aydıncık'), (@MersinId, 'Bozyazı'),
(@MersinId, 'Çamlıyayla'), (@MersinId, 'Erdemli'), (@MersinId, 'Gülnar'), (@MersinId, 'Mezitli'),
(@MersinId, 'Mut'), (@MersinId, 'Silifke'), (@MersinId, 'Tarsus'), (@MersinId, 'Toroslar'),
(@MersinId, 'Yenişehir');
GO

-- ===================== İSTANBUL (34) =====================
DECLARE @IstanbulId INT = (SELECT Id FROM tnm_sehir WHERE PlakaKodu = '34');
INSERT INTO tnm_ilce (SehirId, Ad) VALUES
(@IstanbulId, 'Adalar'), (@IstanbulId, 'Arnavutköy'), (@IstanbulId, 'Ataşehir'), (@IstanbulId, 'Avcılar'),
(@IstanbulId, 'Bağcılar'), (@IstanbulId, 'Bahçelievler'), (@IstanbulId, 'Bakırköy'), (@IstanbulId, 'Başakşehir'),
(@IstanbulId, 'Bayrampaşa'), (@IstanbulId, 'Beşiktaş'), (@IstanbulId, 'Beykoz'), (@IstanbulId, 'Beylikdüzü'),
(@IstanbulId, 'Beyoğlu'), (@IstanbulId, 'Büyükçekmece'), (@IstanbulId, 'Çatalca'), (@IstanbulId, 'Çekmeköy'),
(@IstanbulId, 'Esenler'), (@IstanbulId, 'Esenyurt'), (@IstanbulId, 'Eyüpsultan'), (@IstanbulId, 'Fatih'),
(@IstanbulId, 'Gaziosmanpaşa'), (@IstanbulId, 'Güngören'), (@IstanbulId, 'Kadıköy'), (@IstanbulId, 'Kağıthane'),
(@IstanbulId, 'Kartal'), (@IstanbulId, 'Küçükçekmece'), (@IstanbulId, 'Maltepe'), (@IstanbulId, 'Pendik'),
(@IstanbulId, 'Sancaktepe'), (@IstanbulId, 'Sarıyer'), (@IstanbulId, 'Silivri'), (@IstanbulId, 'Sultanbeyli'),
(@IstanbulId, 'Sultangazi'), (@IstanbulId, 'Şile'), (@IstanbulId, 'Şişli'), (@IstanbulId, 'Tuzla'),
(@IstanbulId, 'Ümraniye'), (@IstanbulId, 'Üsküdar'), (@IstanbulId, 'Zeytinburnu');
GO

-- ===================== İZMİR (35) =====================
DECLARE @IzmirId INT = (SELECT Id FROM tnm_sehir WHERE PlakaKodu = '35');
INSERT INTO tnm_ilce (SehirId, Ad) VALUES
(@IzmirId, 'Aliağa'), (@IzmirId, 'Balçova'), (@IzmirId, 'Bayındır'), (@IzmirId, 'Bayraklı'),
(@IzmirId, 'Bergama'), (@IzmirId, 'Beydağ'), (@IzmirId, 'Bornova'), (@IzmirId, 'Buca'),
(@IzmirId, 'Çeşme'), (@IzmirId, 'Çiğli'), (@IzmirId, 'Dikili'), (@IzmirId, 'Foça'),
(@IzmirId, 'Gaziemir'), (@IzmirId, 'Güzelbahçe'), (@IzmirId, 'Karabağlar'), (@IzmirId, 'Karaburun'),
(@IzmirId, 'Karşıyaka'), (@IzmirId, 'Kemalpaşa'), (@IzmirId, 'Kınık'), (@IzmirId, 'Kiraz'),
(@IzmirId, 'Konak'), (@IzmirId, 'Menderes'), (@IzmirId, 'Menemen'), (@IzmirId, 'Narlıdere'),
(@IzmirId, 'Ödemiş'), (@IzmirId, 'Seferihisar'), (@IzmirId, 'Selçuk'), (@IzmirId, 'Tire'),
(@IzmirId, 'Torbalı'), (@IzmirId, 'Urla');
GO

-- ===================== KARS (36) =====================
DECLARE @KarsId INT = (SELECT Id FROM tnm_sehir WHERE PlakaKodu = '36');
INSERT INTO tnm_ilce (SehirId, Ad) VALUES
(@KarsId, 'Akyaka'), (@KarsId, 'Arpaçay'), (@KarsId, 'Digor'), (@KarsId, 'Kağızman'),
(@KarsId, 'Merkez'), (@KarsId, 'Sarıkamış'), (@KarsId, 'Selim'), (@KarsId, 'Susuz');
GO

-- ===================== KASTAMONU (37) =====================
DECLARE @KastamonuId INT = (SELECT Id FROM tnm_sehir WHERE PlakaKodu = '37');
INSERT INTO tnm_ilce (SehirId, Ad) VALUES
(@KastamonuId, 'Abana'), (@KastamonuId, 'Ağlı'), (@KastamonuId, 'Araç'), (@KastamonuId, 'Azdavay'),
(@KastamonuId, 'Bozkurt'), (@KastamonuId, 'Cide'), (@KastamonuId, 'Çatalzeytin'), (@KastamonuId, 'Daday'),
(@KastamonuId, 'Devrekani'), (@KastamonuId, 'Doğanyurt'), (@KastamonuId, 'Hanönü'), (@KastamonuId, 'İhsangazi'),
(@KastamonuId, 'İnebolu'), (@KastamonuId, 'Küre'), (@KastamonuId, 'Merkez'), (@KastamonuId, 'Pınarbaşı'),
(@KastamonuId, 'Seydiler'), (@KastamonuId, 'Şenpazar'), (@KastamonuId, 'Taşköprü'), (@KastamonuId, 'Tosya');
GO

-- ===================== KAYSERİ (38) =====================
DECLARE @KayseriId INT = (SELECT Id FROM tnm_sehir WHERE PlakaKodu = '38');
INSERT INTO tnm_ilce (SehirId, Ad) VALUES
(@KayseriId, 'Akkışla'), (@KayseriId, 'Bünyan'), (@KayseriId, 'Develi'), (@KayseriId, 'Felahiye'),
(@KayseriId, 'Hacılar'), (@KayseriId, 'İncesu'), (@KayseriId, 'Kocasinan'), (@KayseriId, 'Melikgazi'),
(@KayseriId, 'Özvatan'), (@KayseriId, 'Pınarbaşı'), (@KayseriId, 'Sarıoğlan'), (@KayseriId, 'Sarız'),
(@KayseriId, 'Talas'), (@KayseriId, 'Tomarza'), (@KayseriId, 'Yahyalı'), (@KayseriId, 'Yeşilhisar');
GO

-- ===================== KIRKLARELİ (39) =====================
DECLARE @KirklareliId INT = (SELECT Id FROM tnm_sehir WHERE PlakaKodu = '39');
INSERT INTO tnm_ilce (SehirId, Ad) VALUES
(@KirklareliId, 'Babaeski'), (@KirklareliId, 'Demirköy'), (@KirklareliId, 'Kofçaz'), (@KirklareliId, 'Lüleburgaz'),
(@KirklareliId, 'Merkez'), (@KirklareliId, 'Pehlivanköy'), (@KirklareliId, 'Pınarhisar'), (@KirklareliId, 'Vize');
GO

-- ===================== KIRŞEHİR (40) =====================
DECLARE @KirsehirId INT = (SELECT Id FROM tnm_sehir WHERE PlakaKodu = '40');
INSERT INTO tnm_ilce (SehirId, Ad) VALUES
(@KirsehirId, 'Akçakent'), (@KirsehirId, 'Akpınar'), (@KirsehirId, 'Boztepe'), (@KirsehirId, 'Çiçekdağı'),
(@KirsehirId, 'Kaman'), (@KirsehirId, 'Merkez'), (@KirsehirId, 'Mucur');
GO

-- ===================== KOCAELİ (41) =====================
DECLARE @KocaeliId INT = (SELECT Id FROM tnm_sehir WHERE PlakaKodu = '41');
INSERT INTO tnm_ilce (SehirId, Ad) VALUES
(@KocaeliId, 'Başiskele'), (@KocaeliId, 'Çayırova'), (@KocaeliId, 'Darıca'), (@KocaeliId, 'Derince'),
(@KocaeliId, 'Dilovası'), (@KocaeliId, 'Gebze'), (@KocaeliId, 'Gölcük'), (@KocaeliId, 'İzmit'),
(@KocaeliId, 'Kandıra'), (@KocaeliId, 'Karamürsel'), (@KocaeliId, 'Kartepe'), (@KocaeliId, 'Körfez');
GO

-- ===================== KONYA (42) =====================
DECLARE @KonyaId INT = (SELECT Id FROM tnm_sehir WHERE PlakaKodu = '42');
INSERT INTO tnm_ilce (SehirId, Ad) VALUES
(@KonyaId, 'Ahırlı'), (@KonyaId, 'Akören'), (@KonyaId, 'Akşehir'), (@KonyaId, 'Altınekin'),
(@KonyaId, 'Beyşehir'), (@KonyaId, 'Bozkır'), (@KonyaId, 'Cihanbeyli'), (@KonyaId, 'Çeltik'),
(@KonyaId, 'Çumra'), (@KonyaId, 'Derbent'), (@KonyaId, 'Derebucak'), (@KonyaId, 'Doğanhisar'),
(@KonyaId, 'Emirgazi'), (@KonyaId, 'Ereğli'), (@KonyaId, 'Güneysınır'), (@KonyaId, 'Hadim'),
(@KonyaId, 'Halkapınar'), (@KonyaId, 'Hüyük'), (@KonyaId, 'Ilgın'), (@KonyaId, 'Kadınhanı'),
(@KonyaId, 'Karapınar'), (@KonyaId, 'Karatay'), (@KonyaId, 'Kulu'), (@KonyaId, 'Meram'),
(@KonyaId, 'Sarayönü'), (@KonyaId, 'Selçuklu'), (@KonyaId, 'Seydişehir'), (@KonyaId, 'Taşkent'),
(@KonyaId, 'Tuzlukçu'), (@KonyaId, 'Yalıhüyük'), (@KonyaId, 'Yunak');
GO

-- ===================== KÜTAHYA (43) =====================
DECLARE @KutahyaId INT = (SELECT Id FROM tnm_sehir WHERE PlakaKodu = '43');
INSERT INTO tnm_ilce (SehirId, Ad) VALUES
(@KutahyaId, 'Altıntaş'), (@KutahyaId, 'Aslanapa'), (@KutahyaId, 'Çavdarhisar'), (@KutahyaId, 'Domaniç'),
(@KutahyaId, 'Dumlupınar'), (@KutahyaId, 'Emet'), (@KutahyaId, 'Gediz'), (@KutahyaId, 'Hisarcık'),
(@KutahyaId, 'Merkez'), (@KutahyaId, 'Pazarlar'), (@KutahyaId, 'Şaphane'), (@KutahyaId, 'Simav'),
(@KutahyaId, 'Tavşanlı');
GO

-- ===================== MALATYA (44) =====================
DECLARE @MalatyaId INT = (SELECT Id FROM tnm_sehir WHERE PlakaKodu = '44');
INSERT INTO tnm_ilce (SehirId, Ad) VALUES
(@MalatyaId, 'Akçadağ'), (@MalatyaId, 'Arapgir'), (@MalatyaId, 'Arguvan'), (@MalatyaId, 'Battalgazi'),
(@MalatyaId, 'Darende'), (@MalatyaId, 'Doğanşehir'), (@MalatyaId, 'Doğanyol'), (@MalatyaId, 'Hekimhan'),
(@MalatyaId, 'Kale'), (@MalatyaId, 'Kuluncak'), (@MalatyaId, 'Pütürge'), (@MalatyaId, 'Yazıhan'),
(@MalatyaId, 'Yeşilyurt');
GO

-- ===================== MANİSA (45) =====================
DECLARE @ManisaId INT = (SELECT Id FROM tnm_sehir WHERE PlakaKodu = '45');
INSERT INTO tnm_ilce (SehirId, Ad) VALUES
(@ManisaId, 'Ahmetli'), (@ManisaId, 'Akhisar'), (@ManisaId, 'Alaşehir'), (@ManisaId, 'Demirci'),
(@ManisaId, 'Gölmarmara'), (@ManisaId, 'Gördes'), (@ManisaId, 'Kırkağaç'), (@ManisaId, 'Köprübaşı'),
(@ManisaId, 'Kula'), (@ManisaId, 'Salihli'), (@ManisaId, 'Sarıgöl'), (@ManisaId, 'Saruhanlı'),
(@ManisaId, 'Selendi'), (@ManisaId, 'Soma'), (@ManisaId, 'Şehzadeler'), (@ManisaId, 'Turgutlu'),
(@ManisaId, 'Yunusemre');
GO

-- ===================== KAHRAMANMARAŞ (46) =====================
DECLARE @KahramanmarasId INT = (SELECT Id FROM tnm_sehir WHERE PlakaKodu = '46');
INSERT INTO tnm_ilce (SehirId, Ad) VALUES
(@KahramanmarasId, 'Afşin'), (@KahramanmarasId, 'Andırın'), (@KahramanmarasId, 'Çağlayancerit'), (@KahramanmarasId, 'Dulkadiroğlu'),
(@KahramanmarasId, 'Ekinözü'), (@KahramanmarasId, 'Elbistan'), (@KahramanmarasId, 'Göksun'), (@KahramanmarasId, 'Nurhak'),
(@KahramanmarasId, 'Onikişubat'), (@KahramanmarasId, 'Pazarcık'), (@KahramanmarasId, 'Türkoğlu');
GO

-- ===================== MARDİN (47) =====================
DECLARE @MardinId INT = (SELECT Id FROM tnm_sehir WHERE PlakaKodu = '47');
INSERT INTO tnm_ilce (SehirId, Ad) VALUES
(@MardinId, 'Artuklu'), (@MardinId, 'Dargeçit'), (@MardinId, 'Derik'), (@MardinId, 'Kızıltepe'),
(@MardinId, 'Mazıdağı'), (@MardinId, 'Midyat'), (@MardinId, 'Nusaybin'), (@MardinId, 'Ömerli'),
(@MardinId, 'Savur'), (@MardinId, 'Yeşilli');
GO

-- ===================== MUĞLA (48) =====================
DECLARE @MuglaId INT = (SELECT Id FROM tnm_sehir WHERE PlakaKodu = '48');
INSERT INTO tnm_ilce (SehirId, Ad) VALUES
(@MuglaId, 'Bodrum'), (@MuglaId, 'Dalaman'), (@MuglaId, 'Datça'), (@MuglaId, 'Fethiye'),
(@MuglaId, 'Kavaklıdere'), (@MuglaId, 'Köyceğiz'), (@MuglaId, 'Marmaris'), (@MuglaId, 'Menteşe'),
(@MuglaId, 'Milas'), (@MuglaId, 'Ortaca'), (@MuglaId, 'Seydikemer'), (@MuglaId, 'Ula'),
(@MuglaId, 'Yatağan');
GO

-- ===================== MUŞ (49) =====================
DECLARE @MusId INT = (SELECT Id FROM tnm_sehir WHERE PlakaKodu = '49');
INSERT INTO tnm_ilce (SehirId, Ad) VALUES
(@MusId, 'Bulanık'), (@MusId, 'Hasköy'), (@MusId, 'Korkut'), (@MusId, 'Malazgirt'),
(@MusId, 'Merkez'), (@MusId, 'Varto');
GO

-- ===================== NEVŞEHİR (50) =====================
DECLARE @NevsehirId INT = (SELECT Id FROM tnm_sehir WHERE PlakaKodu = '50');
INSERT INTO tnm_ilce (SehirId, Ad) VALUES
(@NevsehirId, 'Acıgöl'), (@NevsehirId, 'Avanos'), (@NevsehirId, 'Derinkuyu'), (@NevsehirId, 'Gülşehir'),
(@NevsehirId, 'Hacıbektaş'), (@NevsehirId, 'Kozaklı'), (@NevsehirId, 'Merkez'), (@NevsehirId, 'Ürgüp');
GO

-- ===================== NİĞDE (51) =====================
DECLARE @NigdeId INT = (SELECT Id FROM tnm_sehir WHERE PlakaKodu = '51');
INSERT INTO tnm_ilce (SehirId, Ad) VALUES
(@NigdeId, 'Altunhisar'), (@NigdeId, 'Bor'), (@NigdeId, 'Çamardı'), (@NigdeId, 'Çiftlik'),
(@NigdeId, 'Merkez'), (@NigdeId, 'Ulukışla');
GO

-- ===================== ORDU (52) =====================
DECLARE @OrduId INT = (SELECT Id FROM tnm_sehir WHERE PlakaKodu = '52');
INSERT INTO tnm_ilce (SehirId, Ad) VALUES
(@OrduId, 'Akkuş'), (@OrduId, 'Altınordu'), (@OrduId, 'Aybastı'), (@OrduId, 'Çamaş'),
(@OrduId, 'Çatalpınar'), (@OrduId, 'Çaybaşı'), (@OrduId, 'Fatsa'), (@OrduId, 'Gölköy'),
(@OrduId, 'Gülyalı'), (@OrduId, 'Gürgentepe'), (@OrduId, 'İkizce'), (@OrduId, 'Kabadüz'),
(@OrduId, 'Kabataş'), (@OrduId, 'Korgan'), (@OrduId, 'Kumru'), (@OrduId, 'Mesudiye'),
(@OrduId, 'Perşembe'), (@OrduId, 'Ulubey'), (@OrduId, 'Ünye');
GO

-- ===================== RİZE (53) =====================
DECLARE @RizeId INT = (SELECT Id FROM tnm_sehir WHERE PlakaKodu = '53');
INSERT INTO tnm_ilce (SehirId, Ad) VALUES
(@RizeId, 'Ardeşen'), (@RizeId, 'Çamlıhemşin'), (@RizeId, 'Çayeli'), (@RizeId, 'Derepazarı'),
(@RizeId, 'Fındıklı'), (@RizeId, 'Güneysu'), (@RizeId, 'Hemşin'), (@RizeId, 'İkizdere'),
(@RizeId, 'İyidere'), (@RizeId, 'Kalkandere'), (@RizeId, 'Merkez'), (@RizeId, 'Pazar');
GO

-- ===================== SAKARYA (54) =====================
DECLARE @SakaryaId INT = (SELECT Id FROM tnm_sehir WHERE PlakaKodu = '54');
INSERT INTO tnm_ilce (SehirId, Ad) VALUES
(@SakaryaId, 'Adapazarı'), (@SakaryaId, 'Akyazı'), (@SakaryaId, 'Arifiye'), (@SakaryaId, 'Erenler'),
(@SakaryaId, 'Ferizli'), (@SakaryaId, 'Geyve'), (@SakaryaId, 'Hendek'), (@SakaryaId, 'Karapürçek'),
(@SakaryaId, 'Karasu'), (@SakaryaId, 'Kaynarca'), (@SakaryaId, 'Kocaali'), (@SakaryaId, 'Pamukova'),
(@SakaryaId, 'Sapanca'), (@SakaryaId, 'Serdivan'), (@SakaryaId, 'Söğütlü'), (@SakaryaId, 'Taraklı');
GO

-- ===================== SAMSUN (55) =====================
DECLARE @SamsunId INT = (SELECT Id FROM tnm_sehir WHERE PlakaKodu = '55');
INSERT INTO tnm_ilce (SehirId, Ad) VALUES
(@SamsunId, 'Alaçam'), (@SamsunId, 'Asarcık'), (@SamsunId, 'Atakum'), (@SamsunId, 'Ayvacık'),
(@SamsunId, 'Bafra'), (@SamsunId, 'Canik'), (@SamsunId, 'Çarşamba'), (@SamsunId, 'Havza'),
(@SamsunId, 'İlkadım'), (@SamsunId, 'Kavak'), (@SamsunId, 'Ladik'), (@SamsunId, 'Ondokuzmayıs'),
(@SamsunId, 'Salıpazarı'), (@SamsunId, 'Tekkeköy'), (@SamsunId, 'Terme'), (@SamsunId, 'Vezirköprü'),
(@SamsunId, 'Yakakent');
GO

-- ===================== SİİRT (56) =====================
DECLARE @SiirtId INT = (SELECT Id FROM tnm_sehir WHERE PlakaKodu = '56');
INSERT INTO tnm_ilce (SehirId, Ad) VALUES
(@SiirtId, 'Baykan'), (@SiirtId, 'Eruh'), (@SiirtId, 'Kurtalan'), (@SiirtId, 'Merkez'),
(@SiirtId, 'Pervari'), (@SiirtId, 'Şirvan'), (@SiirtId, 'Tillo');
GO

-- ===================== SİNOP (57) =====================
DECLARE @SinopId INT = (SELECT Id FROM tnm_sehir WHERE PlakaKodu = '57');
INSERT INTO tnm_ilce (SehirId, Ad) VALUES
(@SinopId, 'Ayancık'), (@SinopId, 'Boyabat'), (@SinopId, 'Dikmen'), (@SinopId, 'Durağan'),
(@SinopId, 'Erfelek'), (@SinopId, 'Gerze'), (@SinopId, 'Merkez'), (@SinopId, 'Saraydüzü'),
(@SinopId, 'Türkeli');
GO

-- ===================== SİVAS (58) =====================
DECLARE @SivasId INT = (SELECT Id FROM tnm_sehir WHERE PlakaKodu = '58');
INSERT INTO tnm_ilce (SehirId, Ad) VALUES
(@SivasId, 'Akıncılar'), (@SivasId, 'Altınyayla'), (@SivasId, 'Divriği'), (@SivasId, 'Doğanşar'),
(@SivasId, 'Gemerek'), (@SivasId, 'Gölova'), (@SivasId, 'Hafik'), (@SivasId, 'İmranlı'),
(@SivasId, 'Kangal'), (@SivasId, 'Koyulhisar'), (@SivasId, 'Merkez'), (@SivasId, 'Suşehri'),
(@SivasId, 'Şarkışla'), (@SivasId, 'Ulaş'), (@SivasId, 'Yıldızeli'), (@SivasId, 'Zara');
GO

-- ===================== TEKİRDAĞ (59) =====================
DECLARE @TekirdagId INT = (SELECT Id FROM tnm_sehir WHERE PlakaKodu = '59');
INSERT INTO tnm_ilce (SehirId, Ad) VALUES
(@TekirdagId, 'Çerkezköy'), (@TekirdagId, 'Çorlu'), (@TekirdagId, 'Ergene'), (@TekirdagId, 'Hayrabolu'),
(@TekirdagId, 'Kapaklı'), (@TekirdagId, 'Malkara'), (@TekirdagId, 'Marmaraereğlisi'), (@TekirdagId, 'Muratlı'),
(@TekirdagId, 'Saray'), (@TekirdagId, 'Süleymanpaşa'), (@TekirdagId, 'Şarköy');
GO

-- ===================== TOKAT (60) =====================
DECLARE @TokatId INT = (SELECT Id FROM tnm_sehir WHERE PlakaKodu = '60');
INSERT INTO tnm_ilce (SehirId, Ad) VALUES
(@TokatId, 'Almus'), (@TokatId, 'Artova'), (@TokatId, 'Başçiftlik'), (@TokatId, 'Erbaa'),
(@TokatId, 'Merkez'), (@TokatId, 'Niksar'), (@TokatId, 'Pazar'), (@TokatId, 'Reşadiye'),
(@TokatId, 'Sulusaray'), (@TokatId, 'Turhal'), (@TokatId, 'Yeşilyurt'), (@TokatId, 'Zile');
GO

-- ===================== TRABZON (61) =====================
DECLARE @TrabzonId INT = (SELECT Id FROM tnm_sehir WHERE PlakaKodu = '61');
INSERT INTO tnm_ilce (SehirId, Ad) VALUES
(@TrabzonId, 'Akçaabat'), (@TrabzonId, 'Araklı'), (@TrabzonId, 'Arsin'), (@TrabzonId, 'Beşikdüzü'),
(@TrabzonId, 'Çarşıbaşı'), (@TrabzonId, 'Çaykara'), (@TrabzonId, 'Dernekpazarı'), (@TrabzonId, 'Düzköy'),
(@TrabzonId, 'Hayrat'), (@TrabzonId, 'Köprübaşı'), (@TrabzonId, 'Maçka'), (@TrabzonId, 'Of'),
(@TrabzonId, 'Ortahisar'), (@TrabzonId, 'Sürmene'), (@TrabzonId, 'Şalpazarı'), (@TrabzonId, 'Tonya'),
(@TrabzonId, 'Vakfıkebir'), (@TrabzonId, 'Yomra');
GO

-- ===================== TUNCELİ (62) =====================
DECLARE @TunceliId INT = (SELECT Id FROM tnm_sehir WHERE PlakaKodu = '62');
INSERT INTO tnm_ilce (SehirId, Ad) VALUES
(@TunceliId, 'Çemişgezek'), (@TunceliId, 'Hozat'), (@TunceliId, 'Mazgirt'), (@TunceliId, 'Merkez'),
(@TunceliId, 'Nazımiye'), (@TunceliId, 'Ovacık'), (@TunceliId, 'Pertek'), (@TunceliId, 'Pülümür');
GO

-- ===================== ŞANLIURFA (63) =====================
DECLARE @SanliurfaId INT = (SELECT Id FROM tnm_sehir WHERE PlakaKodu = '63');
INSERT INTO tnm_ilce (SehirId, Ad) VALUES
(@SanliurfaId, 'Akçakale'), (@SanliurfaId, 'Birecik'), (@SanliurfaId, 'Bozova'), (@SanliurfaId, 'Ceylanpınar'),
(@SanliurfaId, 'Eyyübiye'), (@SanliurfaId, 'Halfeti'), (@SanliurfaId, 'Haliliye'), (@SanliurfaId, 'Harran'),
(@SanliurfaId, 'Hilvan'), (@SanliurfaId, 'Karaköprü'), (@SanliurfaId, 'Siverek'), (@SanliurfaId, 'Suruç'),
(@SanliurfaId, 'Viranşehir');
GO

-- ===================== UŞAK (64) =====================
DECLARE @UsakId INT = (SELECT Id FROM tnm_sehir WHERE PlakaKodu = '64');
INSERT INTO tnm_ilce (SehirId, Ad) VALUES
(@UsakId, 'Banaz'), (@UsakId, 'Eşme'), (@UsakId, 'Karahallı'), (@UsakId, 'Merkez'),
(@UsakId, 'Sivaslı'), (@UsakId, 'Ulubey');
GO

-- ===================== VAN (65) =====================
DECLARE @VanId INT = (SELECT Id FROM tnm_sehir WHERE PlakaKodu = '65');
INSERT INTO tnm_ilce (SehirId, Ad) VALUES
(@VanId, 'Bahçesaray'), (@VanId, 'Başkale'), (@VanId, 'Çaldıran'), (@VanId, 'Çatak'),
(@VanId, 'Edremit'), (@VanId, 'Erciş'), (@VanId, 'Gevaş'), (@VanId, 'Gürpınar'),
(@VanId, 'İpekyolu'), (@VanId, 'Muradiye'), (@VanId, 'Özalp'), (@VanId, 'Saray'),
(@VanId, 'Tuşba');
GO

-- ===================== YOZGAT (66) =====================
DECLARE @YozgatId INT = (SELECT Id FROM tnm_sehir WHERE PlakaKodu = '66');
INSERT INTO tnm_ilce (SehirId, Ad) VALUES
(@YozgatId, 'Akdağmadeni'), (@YozgatId, 'Aydıncık'), (@YozgatId, 'Boğazlıyan'), (@YozgatId, 'Çandır'),
(@YozgatId, 'Çayıralan'), (@YozgatId, 'Çekerek'), (@YozgatId, 'Kadışehri'), (@YozgatId, 'Merkez'),
(@YozgatId, 'Saraykent'), (@YozgatId, 'Sarıkaya'), (@YozgatId, 'Şefaatli'), (@YozgatId, 'Sorgun'),
(@YozgatId, 'Yenifakılı'), (@YozgatId, 'Yerköy');
GO

-- ===================== ZONGULDAK (67) =====================
DECLARE @ZonguldakId INT = (SELECT Id FROM tnm_sehir WHERE PlakaKodu = '67');
INSERT INTO tnm_ilce (SehirId, Ad) VALUES
(@ZonguldakId, 'Alaplı'), (@ZonguldakId, 'Çaycuma'), (@ZonguldakId, 'Devrek'), (@ZonguldakId, 'Ereğli'),
(@ZonguldakId, 'Gökçebey'), (@ZonguldakId, 'Kilimli'), (@ZonguldakId, 'Kozlu'), (@ZonguldakId, 'Merkez');
GO

-- ===================== AKSARAY (68) =====================
DECLARE @AksarayId INT = (SELECT Id FROM tnm_sehir WHERE PlakaKodu = '68');
INSERT INTO tnm_ilce (SehirId, Ad) VALUES
(@AksarayId, 'Ağaçören'), (@AksarayId, 'Eskil'), (@AksarayId, 'Gülağaç'), (@AksarayId, 'Güzelyurt'),
(@AksarayId, 'Merkez'), (@AksarayId, 'Ortaköy'), (@AksarayId, 'Sarıyahşi'), (@AksarayId, 'Sultanhanı');
GO

-- ===================== BAYBURT (69) =====================
DECLARE @BayburtId INT = (SELECT Id FROM tnm_sehir WHERE PlakaKodu = '69');
INSERT INTO tnm_ilce (SehirId, Ad) VALUES
(@BayburtId, 'Aydıntepe'), (@BayburtId, 'Demirözü'), (@BayburtId, 'Merkez');
GO

-- ===================== KARAMAN (70) =====================
DECLARE @KaramanId INT = (SELECT Id FROM tnm_sehir WHERE PlakaKodu = '70');
INSERT INTO tnm_ilce (SehirId, Ad) VALUES
(@KaramanId, 'Ayrancı'), (@KaramanId, 'Başyayla'), (@KaramanId, 'Ermenek'), (@KaramanId, 'Kazımkarabekir'),
(@KaramanId, 'Merkez'), (@KaramanId, 'Sarıveliler');
GO

-- ===================== KIRIKKALE (71) =====================
DECLARE @KirikkaleId INT = (SELECT Id FROM tnm_sehir WHERE PlakaKodu = '71');
INSERT INTO tnm_ilce (SehirId, Ad) VALUES
(@KirikkaleId, 'Bahşılı'), (@KirikkaleId, 'Balışeyh'), (@KirikkaleId, 'Çelebi'), (@KirikkaleId, 'Delice'),
(@KirikkaleId, 'Karakeçili'), (@KirikkaleId, 'Keskin'), (@KirikkaleId, 'Merkez'), (@KirikkaleId, 'Sulakyurt'),
(@KirikkaleId, 'Yahşihan');
GO

-- ===================== BATMAN (72) =====================
DECLARE @BatmanId INT = (SELECT Id FROM tnm_sehir WHERE PlakaKodu = '72');
INSERT INTO tnm_ilce (SehirId, Ad) VALUES
(@BatmanId, 'Beşiri'), (@BatmanId, 'Gercüş'), (@BatmanId, 'Hasankeyf'), (@BatmanId, 'Kozluk'),
(@BatmanId, 'Merkez'), (@BatmanId, 'Sason');
GO

-- ===================== ŞIRNAK (73) =====================
DECLARE @SirnakId INT = (SELECT Id FROM tnm_sehir WHERE PlakaKodu = '73');
INSERT INTO tnm_ilce (SehirId, Ad) VALUES
(@SirnakId, 'Beytüşşebap'), (@SirnakId, 'Cizre'), (@SirnakId, 'Güçlükonak'), (@SirnakId, 'İdil'),
(@SirnakId, 'Merkez'), (@SirnakId, 'Silopi'), (@SirnakId, 'Uludere');
GO

-- ===================== BARTIN (74) =====================
DECLARE @BartinId INT = (SELECT Id FROM tnm_sehir WHERE PlakaKodu = '74');
INSERT INTO tnm_ilce (SehirId, Ad) VALUES
(@BartinId, 'Amasra'), (@BartinId, 'Kurucaşile'), (@BartinId, 'Merkez'), (@BartinId, 'Ulus');
GO

-- ===================== ARDAHAN (75) =====================
DECLARE @ArdahanId INT = (SELECT Id FROM tnm_sehir WHERE PlakaKodu = '75');
INSERT INTO tnm_ilce (SehirId, Ad) VALUES
(@ArdahanId, 'Çıldır'), (@ArdahanId, 'Damal'), (@ArdahanId, 'Göle'), (@ArdahanId, 'Hanak'),
(@ArdahanId, 'Merkez'), (@ArdahanId, 'Posof');
GO

-- ===================== IĞDIR (76) =====================
DECLARE @IgdirId INT = (SELECT Id FROM tnm_sehir WHERE PlakaKodu = '76');
INSERT INTO tnm_ilce (SehirId, Ad) VALUES
(@IgdirId, 'Aralık'), (@IgdirId, 'Karakoyunlu'), (@IgdirId, 'Merkez'), (@IgdirId, 'Tuzluca');
GO

-- ===================== YALOVA (77) =====================
DECLARE @YalovaId INT = (SELECT Id FROM tnm_sehir WHERE PlakaKodu = '77');
INSERT INTO tnm_ilce (SehirId, Ad) VALUES
(@YalovaId, 'Altınova'), (@YalovaId, 'Armutlu'), (@YalovaId, 'Çiftlikköy'), (@YalovaId, 'Çınarcık'),
(@YalovaId, 'Merkez'), (@YalovaId, 'Termal');
GO

-- ===================== KARABÜK (78) =====================
DECLARE @KarabukId INT = (SELECT Id FROM tnm_sehir WHERE PlakaKodu = '78');
INSERT INTO tnm_ilce (SehirId, Ad) VALUES
(@KarabukId, 'Eflani'), (@KarabukId, 'Eskipazar'), (@KarabukId, 'Merkez'), (@KarabukId, 'Ovacık'),
(@KarabukId, 'Safranbolu'), (@KarabukId, 'Yenice');
GO

-- ===================== KİLİS (79) =====================
DECLARE @KilisId INT = (SELECT Id FROM tnm_sehir WHERE PlakaKodu = '79');
INSERT INTO tnm_ilce (SehirId, Ad) VALUES
(@KilisId, 'Elbeyli'), (@KilisId, 'Merkez'), (@KilisId, 'Musabeyli'), (@KilisId, 'Polateli');
GO

-- ===================== OSMANİYE (80) =====================
DECLARE @OsmaniyeId INT = (SELECT Id FROM tnm_sehir WHERE PlakaKodu = '80');
INSERT INTO tnm_ilce (SehirId, Ad) VALUES
(@OsmaniyeId, 'Bahçe'), (@OsmaniyeId, 'Düziçi'), (@OsmaniyeId, 'Hasanbeyli'), (@OsmaniyeId, 'Kadirli'),
(@OsmaniyeId, 'Merkez'), (@OsmaniyeId, 'Sumbas'), (@OsmaniyeId, 'Toprakkale');
GO

-- ===================== DÜZCE (81) =====================
DECLARE @DuzceId INT = (SELECT Id FROM tnm_sehir WHERE PlakaKodu = '81');
INSERT INTO tnm_ilce (SehirId, Ad) VALUES
(@DuzceId, 'Akçakoca'), (@DuzceId, 'Cumayeri'), (@DuzceId, 'Çilimli'), (@DuzceId, 'Gölyaka'),
(@DuzceId, 'Gümüşova'), (@DuzceId, 'Kaynaşlı'), (@DuzceId, 'Merkez'), (@DuzceId, 'Yığılca');
GO

-- ===================== SONUÇ RAPORU =====================
PRINT '======================================';
PRINT 'Turkiye ilce seed migration tamamlandi.';
DECLARE @IlSayisi INT = (SELECT COUNT(*) FROM tnm_sehir WHERE Sil = 0);
DECLARE @IlceSayisi INT = (SELECT COUNT(*) FROM tnm_ilce WHERE Sil = 0);
PRINT 'Toplam il: ' + CAST(@IlSayisi AS VARCHAR(10));
PRINT 'Toplam ilce: ' + CAST(@IlceSayisi AS VARCHAR(10));
PRINT '======================================';
GO
