FROM php:8.4-cli

WORKDIR /app

# Cài thư viện hệ thống
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    curl \
    gnupg \
    apt-transport-https \
    unixodbc-dev

# Cài Microsoft ODBC Driver cho SQL Server
RUN curl https://packages.microsoft.com/keys/microsoft.asc | apt-key add - \
    && curl https://packages.microsoft.com/config/debian/12/prod.list \
    > /etc/apt/sources.list.d/mssql-release.list \
    && apt-get update \
    && ACCEPT_EULA=Y apt-get install -y msodbcsql18

# Cài sqlsrv extensions
RUN pecl install sqlsrv pdo_sqlsrv \
    && docker-php-ext-enable sqlsrv pdo_sqlsrv

# Cài composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy source
COPY . .

# Cài package Laravel
RUN composer install --optimize-autoloader --no-dev

# Quyền Laravel
RUN chmod -R 777 storage bootstrap/cache

# Chạy từ public
WORKDIR /app/public

EXPOSE 8000

CMD php -S 0.0.0.0:8000 index.php
