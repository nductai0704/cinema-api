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

# Copy project vào container
COPY . .

# Cài dependencies Laravel
RUN composer install --optimize-autoloader --no-dev

# Port chạy Laravel
EXPOSE 8000

# Chạy Laravel
CMD php artisan serve --host=0.0.0.0 --port=8000
