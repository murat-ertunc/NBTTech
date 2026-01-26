FROM php:7.4-apache

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends \
        ca-certificates \
        curl \
        gnupg \
        apt-transport-https \
        unixodbc-dev; \
    curl -sSL https://packages.microsoft.com/keys/microsoft.asc | gpg --dearmor -o /usr/share/keyrings/microsoft-prod.gpg; \
    echo "deb [arch=amd64,arm64 signed-by=/usr/share/keyrings/microsoft-prod.gpg] https://packages.microsoft.com/debian/11/prod bullseye main" > /etc/apt/sources.list.d/mssql-release.list; \
    apt-get update; \
    ACCEPT_EULA=Y apt-get install -y --no-install-recommends msodbcsql18; \
    pecl channel-update pecl.php.net; \
    pecl install sqlsrv-5.10.1 pdo_sqlsrv-5.10.1; \
    docker-php-ext-enable sqlsrv pdo_sqlsrv; \
    docker-php-ext-install pdo; \
    a2enmod rewrite; \
    echo '<Directory /var/www/html/public>\nAllowOverride All\n</Directory>' > /etc/apache2/conf-available/allowoverride.conf; \
    a2enconf allowoverride; \
    sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf; \
    rm -rf /var/lib/apt/lists/*

COPY . /var/www/html

RUN set -eux; \
    mkdir -p /var/www/html/storage; \
    chown -R www-data:www-data /var/www/html/storage

EXPOSE 80
