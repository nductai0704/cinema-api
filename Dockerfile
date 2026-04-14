FROM php:8.4-cli

WORKDIR /app

# Cài thư viện cần thiết
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    zip \
    curl

# Cài extension zip
RUN docker-php-ext-install zip

# Cài composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy project
COPY . .

# Cài Laravel packages
RUN composer install --optimize-autoloader --no-dev

# Set quyền Laravel
RUN chmod -R 777 storage bootstrap/cache

# QUAN TRỌNG: chuyển vào thư mục public
WORKDIR /app/public

EXPOSE 8000

# Chạy từ public/index.php
CMD php -S 0.0.0.0:8000 index.php
