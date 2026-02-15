# CHANGELOG — NbtProject Code Review & Hardening

## Özet

Kapsamlı kod incelemesi ve güçlendirme çalışması. 6 aşamada yürütüldü:
keşif → dosya analizi → mimari düzeltmeler → derin inceleme → testler → rota testleri.

**Sonuç:** 24 birim testi + 47 rota testi — tamamı başarılı.

---

## 🔴 KRİTİK Güvenlik Düzeltmeleri

### SQL Injection (5 konum)
- **BaseRepository.php** — Sayfalama LIMIT/OFFSET parametreleri `(int)` cast ile güvenceye alındı
- **UserRepository.php** — Arama sorgusu parametreleştirildi
- **LogController.php** — Filtre parametreleri PDO bound parameters ile değiştirildi
- **AlarmController.php** — Sayfalama parametreleri güvenli hale getirildi

### Dosya Yükleme Güvenliği
- **FileController.php** — 23 tehlikeli uzantı (php, exe, sh, svg, html vb.) engelleyen blocklist eklendi
- **UploadValidator.php** — Tüm izin verilen uzantılar için MIME tipi doğrulaması eklendi (sadece PDF değil, doc/docx/xlsx vb. dahil)

### Dosya İndirme Güvenliği
- **DownloadHelper.php** — Path traversal koruması, header injection önleme, dosya varlık kontrolü eklendi
- **FileController.php** — `download()` metodu ham header yerine `DownloadHelper::outputFile()` kullanacak şekilde güncellendi

### Token Güvenliği
- **Token.php** — Sabit kodlanmış geliştirme anahtarı `'development-only-key-not-secure'` yerine her süreç için `bin2hex(random_bytes(32))` ile oluşturulan rastgele anahtar

### Tahmin Edilebilir Tanımlayıcılar
- **10 konum** — `uniqid()` → `bin2hex(random_bytes(16))` (FileController, PaymentController, GuaranteeController, ContractController, OfferController, StampTaxController)
- **3 konum** — `mt_rand()` GUID → `random_bytes(16)` (InvoiceRepository, DbLogger)

---

## 🟠 YÜKSEK Öncelikli Düzeltmeler

### Eksik Transaction Sarmalama
- **MeetingController.php** — `store()` → `Transaction::wrap()` (CalendarService çağrısı dahil)
- **ContactController.php** — `store()` ve `delete()` → `Transaction::wrap()`
- **TakvimController.php** — `store()` ve `delete()` → `Transaction::wrap()`
- **StampTaxController.php** — `store()` (CalendarService dahil) ve `delete()` → `Transaction::wrap()`

### Eksik Varlık Kontrolü (Mutation Öncesi)
- **MeetingController** — `update()` ve `delete()` 404 kontrolü eklendi
- **ContactController** — `update()` ve `delete()` 404 kontrolü eklendi
- **TakvimController** — `update()` ve `delete()` 404 kontrolü eklendi
- **StampTaxController** — `update()` ve `delete()` 404 kontrolü eklendi
- **ProjectController** — `update()` ve `delete()` 404 kontrolü eklendi

### Veri Doğruluğu
- **PaymentController** + **GuaranteeController** — `empty()` kontrolü sıfır `Tutar` değerini reddediyordu, koşul düzeltildi
- **InvoiceController** — `update()` içinde takvim kaydı oluşturulurken eksik `Durum` alanı eklendi
- **PaymentController** — `update()` içinde gereksiz ikinci `$Repo->bul($Id)` çağrısı kaldırıldı

### Eksik Response Metodları
- **Response.php** — CalendarController'ın çağırdığı fakat mevcut olmayan `unauthorized()` ve `badRequest()` metodları eklendi (ölümcül PHP hatası önlendi)

---

## 🟡 Mimari İyileştirmeler

### CalendarService Tam Yeniden Yazımı
- `Transaction::wrap()` sarmalama
- Denetim kolonları (`EkleyenUserId`, `DegistirenUserId`, `DegisiklikZamani`)
- Güvenli GUID: `random_bytes(16)` → UUID v4 format
- `GETDATE()` → `SYSUTCDATETIME()`
- `Context::kullaniciId()` entegrasyonu

### AuthorizationService Hata Düzeltmesi
- `tumunuDuzenleyebilirMi()` yanlış izin kodunu kontrol ediyordu → `.edit_all` olarak düzeltildi

### Backup Tablo Şema Senkronizasyonu
- **080_alter_bck_musteri_sehir_ilce.sql** — `bck_tbl_musteri` tablosuna `SehirId`, `IlceId` eklendi
- **081_alter_bck_takvim_kaynak.sql** — `bck_tbl_takvim` tablosuna `KaynakTuru`, `OrijinalKaynakId` eklendi
- **079_bck_tnm_sehir_ilce.sql** — `bck_tnm_sehir`, `bck_tnm_ilce` backup tabloları oluşturuldu
- **BaseRepository.php** — `yedekle()` metodu `KaynakId` kolon çakışmasını yönetecek şekilde güncellendi
- **CityRepository** + **DistrictRepository** — Eksik `yedekle()` çağrıları eklendi

### Cascade Soft-Delete
- **ProjectRepository.php** — Proje silinirken ilişkili kayıtlar (fatura, ödeme, teklif, sözleşme, teminat, görüşme, kişi, dosya, damga vergisi) backup + soft-delete

### Global Tutarlılık Taraması
- **11 konum** — `GETDATE()` → `SYSUTCDATETIME()` (UTC tutarlılığı)
- Tüm üretim kodu `mt_rand()` ve `uniqid()` temizlendi

---

## 🟢 Yeni Altyapı

### Dosya Logger
- **FileLogger.php** — `LoggerInterface` implementasyonu, günlük log dosyaları
- **LoggerFactory.php** — `file` sürücüsü desteği eklendi
- **log.php** — `file_path` yapılandırması eklendi

### Kuyruk Soyutlaması
- **QueueInterface.php** — Kuyruk arayüzü
- **RabbitMqQueue.php** — RabbitMQ implementasyonu
- **NullQueue.php** — Kuyruk olmadığında sessiz düşüş
- **QueueFactory.php** — Yapılandırmaya göre fabrika

### Önbellek Soyutlaması
- **CacheInterface.php** — Önbellek arayüzü
- **Cache.php** — Redis implementasyonu, seri hale getirme/çözme, TTL desteği

---

## ✅ Test Altyapısı

### Birim Test Çerçevesi (Composer-sız)
- **tests/Framework.php** — Özel test runner, renkli çıktı, her test için setUp()/tearDown() izolasyonu
- **tests/run.php** — Test giriş noktası

### Birim Testleri (24 test, tamamı başarılı)
| Sınıf | Test Sayısı | Kapsam |
|---|---|---|
| TokenTest | 4 | İmzalama/doğrulama, süresi dolmuş token, bozuk token, geçersiz format |
| BaseModelTest | 5 | Insert/update/delete standart alanları, GUID benzersizliği ve format |
| ContextTest | 6 | Set/get, varsayılan değerler, null kontrolü |
| DownloadHelperTest | 4 | Dosya adı temizleme, boş fallback, path traversal, izinli dizin |
| CalendarServiceTest | 5 | GUID formatı, hatırlatma tarihi hesaplama, kaynak türleri |

### Rota Testleri (47 test, tamamı başarılı)
- **tests/route_test.php** — cURL tabanlı otomatik rota tester
- 34 endpoint okuma testi (GET + yetki kontrolleri)
- 8 CRUD yaşam döngüsü testi (Müşteri + Proje: oluştur → oku → güncelle → sil)
- 5 validasyon hata testi (boş veri, kısa unvan, geçersiz vergi no, olmayan kaynak)

---

## Değiştirilen Dosyalar

### Düzenlenen (31 dosya)
| Dosya | Değişiklik |
|---|---|
| src/app/Repositories/BaseRepository.php | SQL injection + yedekle() KaynakId çakışması |
| src/app/Repositories/UserRepository.php | SQL injection |
| src/app/Repositories/CityRepository.php | yedekle() eklendi |
| src/app/Repositories/DistrictRepository.php | yedekle() eklendi |
| src/app/Repositories/ProjectRepository.php | Cascade + GETDATE |
| src/app/Repositories/InvoiceRepository.php | GETDATE + mt_rand |
| src/app/Repositories/ParameterRepository.php | GETDATE |
| src/app/Controllers/LogController.php | SQL injection |
| src/app/Controllers/AlarmController.php | SQL injection + GETDATE |
| src/app/Controllers/FileController.php | İndirme güvenliği + uzantı blocklist + uniqid |
| src/app/Controllers/MeetingController.php | Transaction + varlık kontrolü |
| src/app/Controllers/ContactController.php | Transaction + varlık kontrolü |
| src/app/Controllers/TakvimController.php | Transaction + varlık kontrolü |
| src/app/Controllers/StampTaxController.php | Transaction + varlık kontrolü + uniqid |
| src/app/Controllers/ProjectController.php | Varlık kontrolü |
| src/app/Controllers/PaymentController.php | empty() düzeltme + gereksiz sorgu + uniqid |
| src/app/Controllers/GuaranteeController.php | empty() düzeltme + uniqid |
| src/app/Controllers/ContractController.php | uniqid |
| src/app/Controllers/OfferController.php | uniqid |
| src/app/Controllers/InvoiceController.php | GETDATE + mt_rand + eksik Durum |
| src/app/Services/CalendarService.php | Tam yeniden yazım |
| src/app/Services/Authorization/AuthorizationService.php | İzin kodu düzeltme |
| src/app/Services/Logger/LoggerFactory.php | File driver |
| src/app/Services/Logger/DbLogger.php | Güvenli GUID |
| src/app/Core/DownloadHelper.php | Güvenlik yeniden yazımı |
| src/app/Core/Response.php | unauthorized + badRequest |
| src/app/Core/Token.php | Rastgele dev anahtarı |
| src/app/Core/UploadValidator.php | MIME doğrulaması |
| src/config/log.php | file_path yapılandırması |

### Oluşturulan (19 dosya)
| Dosya | Açıklama |
|---|---|
| src/sql/079_bck_tnm_sehir_ilce.sql | Şehir/İlçe backup tabloları |
| src/sql/080_alter_bck_musteri_sehir_ilce.sql | Müşteri backup SehirId/IlceId |
| src/sql/081_alter_bck_takvim_kaynak.sql | Takvim backup KaynakTuru/OrijinalKaynakId |
| src/app/Services/Logger/FileLogger.php | Dosya tabanlı logger |
| src/app/Core/QueueInterface.php | Kuyruk arayüzü |
| src/app/Core/RabbitMqQueue.php | RabbitMQ implementasyonu |
| src/app/Core/NullQueue.php | Null kuyruk |
| src/app/Core/QueueFactory.php | Kuyruk fabrikası |
| src/app/Core/CacheInterface.php | Önbellek arayüzü |
| src/app/Core/Cache.php | Redis önbellek |
| tests/Framework.php | Test çerçevesi |
| tests/run.php | Test runner |
| tests/Unit/TokenTest.php | Token testleri |
| tests/Unit/BaseModelTest.php | BaseModel testleri |
| tests/Unit/ContextTest.php | Context testleri |
| tests/Unit/DownloadHelperTest.php | DownloadHelper testleri |
| tests/Unit/CalendarServiceTest.php | CalendarService testleri |
| tests/route_test.php | Rota testleri |
| CHANGELOG.md | Bu dosya |
