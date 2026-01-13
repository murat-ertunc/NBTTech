FROM php:7.4-fpm

# Install system dependencies and Microsoft ODBC driver for SQL Server
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        apt-transport-https \
        gnupg \
        unixodbc \
        unixodbc-dev \
        curl \
        libxml2-dev \
        libzip-dev \
        libssl-dev \
        libonig-dev \
        zip \
        git \
    && curl https://packages.microsoft.com/keys/microsoft.asc | apt-key add - \
    && curl https://packages.microsoft.com/config/debian/10/prod.list > /etc/apt/sources.list.d/mssql-release.list \
    && apt-get update \
    && ACCEPT_EULA=Y apt-get install -y --no-install-recommends msodbcsql17 mssql-tools \
    && ln -s /opt/mssql-tools/bin/sqlcmd /usr/bin/sqlcmd \
    && ln -s /opt/mssql-tools/bin/bcp /usr/bin/bcp \
    # Clean up
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo \
    && pecl install sqlsrv-5.10.1 pdo_sqlsrv-5.10.1 redis-5.3.7 \
    && docker-php-ext-enable sqlsrv pdo_sqlsrv redis

# Set working directory
WORKDIR /var/www/html

# Copy project
COPY . /var/www/html

# Permissions for storage (if needed later)
RUN chown -R www-data:www-data /var/www/html/storage || true

CMD ["php-fpm"]
