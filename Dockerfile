FROM php:8.4-cli

WORKDIR /app

# Cài package hệ thống
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    curl \
    ca-certificates \
    libpng-dev \
    libjpeg-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    && rm -rf /var/lib/apt/lists/*

# Cài PHP extensions cần thiết cho Laravel + MySQL
RUN docker-php-ext-install \
    pdo \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    zip

# Cài Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy project
COPY . .

# Cài Laravel dependencies (production)
RUN composer install --optimize-autoloader --no-dev

# Set quyền Laravel
RUN chmod -R 775 storage bootstrap/cache

EXPOSE 8000

# Dùng biến PORT của Railway (fallback về 8000)
CMD php -S 0.0.0.0:${PORT:-8000} -t public
