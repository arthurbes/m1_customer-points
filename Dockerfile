FROM php:8.5-cli

ENTRYPOINT []

RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    unzip \
    libzip-dev \
    bash \
    && docker-php-ext-install zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

WORKDIR /app

# Copy source code
COPY . .

# Install dependencies
RUN composer install --no-interaction --prefer-dist --optimize-autoloader

# Disable Xdebug for performance (not strictly required)
RUN phpdismod xdebug 2>/dev/null || true

CMD ["bash"]