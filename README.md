# Proje Yönetim Sistemi (NbtProject)

Bu proje, bir firmanın müşteri ve ilgili süreçlerinin takibini sağlayan Docker tabanlı bir web uygulamasıdır. PHP 7.4 ve MSSQL 2019 altyapısı üzerine kurulmuştur.

## Gereksinimler

*   Docker & Docker Compose
*   (İsteğe bağlı) PHP 7.4 (Lokal geliştirme için)

## Kurulum ve Başlatma

Aşağıdaki komutları proje dizininde çalıştırarak ortamı başlatabilirsiniz:

```bash
docker compose up -d
```

### Veritabanı Kurulumu

Konteynerler ayağa kalktıktan sonra aşağıdaki komut ile veritabanı şemasını ve örnek verileri yükleyebilirsiniz:

```bash
# Veritabanı şeması ve tabloların oluşturulması
docker exec nbt-php74 /bin/sh -c "cd /var/www/html/sql && sqlcmd -S host.docker.internal,1433 -U sa -P 'i&V9WUPOj=27' -i 000_init_all.sql"

# Varsayılan kullanıcıların eklenmesi
docker exec nbt-php74 php /var/www/html/database/seeder.php
```

Kurulumun başarılı olduğunu kontrol etmek için:
`http://localhost:8082/health` adresine istek atabilirsiniz.

## Kullanıcı Bilgileri

Varsayılan tanımlı kullanıcılar:

| Rol | Kullanıcı Adı | Parola | Yetki |
|-----|---------------|--------|-------|
| Süper Admin | `superadmin` | `Super123!` | Tam yetki |
| Demo Kullanıcı | `demo` | `Demo123!` | Sadece kendi verisi |

## Proje Yapısı

*   **app/**: Uygulama çekirdeği, modelleri ve servisleri.
*   **public/**: Web sunucusu giriş noktası.
*   **routes/**: API ve Web rota tanımları.
*   **sql/**: Veritabanı şema ve migration dosyaları.
*   **docker-compose.yml**: Altyapı tanımları (PHP, MSSQL, Redis, RabbitMQ, Nginx).

## Teknik Standartlar

Detaylı teknik standartlar ve veritabanı kuralları için [PROJECT_STANDARDS.md](PROJECT_STANDARDS.md) dosyasını inceleyiniz.

## API Kullanımı

Postman veya benzeri bir araç ile test edilebilir.

*   **Login**: `POST /api/login` (Body: `{ "username": "...", "password": "..." }`)
*   **Müşteri Listesi**: `GET /api/customers` (Header: `Authorization: Bearer <token>`)
*   **Müşteri Ekle**: `POST /api/customers`
*   **Müşteri Güncelle**: `PUT /api/customers/{id}`

## Testler

Geliştirme ortamında birim testlerini çalıştırmak için:

```bash
docker exec nbt-php74 php /var/www/html/tests/token_test.php
```
