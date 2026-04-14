FROM php:8.4-cli

WORKDIR /app

# Cài package hệ thống
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    curl \
    gnupg2 \
    unixodbc-dev \
    apt-transport-https \
    ca-certificates

# Thêm Microsoft repo (cách mới, không dùng apt-key)
RUN curl -fsSL https://packages.microsoft.com/keys/microsoft.asc \
    | gpg --dearmor -o /usr/share/keyrings/microsoft-prod.gpg

RUN echo "deb [arch=amd64 signed-by=/usr/share/keyrings/microsoft-prod.gpg] https://packages.microsoft.com/debian/12/prod bookworm main" \
    > /etc/apt/sources.list.d/mssql-release.list

# Cài ODBC Driver
RUN apt-get update \
    && ACCEPT_EULA=Y apt-get install -y msodbcsql18

# Cài SQL Server extensions cho PHP
RUN pecl install sqlsrv pdo_sqlsrv \
    && docker-php-ext-enable sqlsrv pdo_sqlsrv

# Cài Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy project
COPY . .

# Cài Laravel dependencies
RUN composer install --optimize-autoloader --no-dev

# Set quyền Laravel
RUN chmod -R 777 storage bootstrap/cache

# Chạy từ thư mục public
WORKDIR /app/public

EXPOSE 8000

CMD php -S 0.0.0.0:8000 index.php
