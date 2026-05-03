FROM php:8.3-cli

# System deps + PHP extensions
RUN apt-get update && apt-get install -y \
    git unzip curl \
    libpng-dev libzip-dev libicu-dev libonig-dev libsqlite3-dev libpq-dev \
    && docker-php-ext-install pdo pdo_sqlite pdo_pgsql mbstring zip intl bcmath pcntl gd \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Node 20 (apt default is too old for Vite)
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . /app

# PHP deps
RUN composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction

# Temp .env so artisan works during build (no real secrets needed here)
RUN cp .env.example .env \
    && sed -i 's|^DB_CONNECTION=.*|DB_CONNECTION=sqlite|' .env \
    && php artisan key:generate --force

# Install Turso LibSQL PHP extension directly
RUN LIBSQL_TAG=turso-php-extension-v1.6.2 \
 && LIBSQL_FILE=libsql_php-${LIBSQL_TAG}-php-8.3-nts-x86_64-unknown-linux-gnu.tar.gz \
 && curl -fsSL "https://github.com/tursodatabase/turso-client-php/releases/download/${LIBSQL_TAG}/${LIBSQL_FILE}" \
      -o /tmp/libsql.tar.gz \
 && tar -xzf /tmp/libsql.tar.gz -C /tmp \
 && SO=$(find /tmp -name "*.so" | head -1) \
 && cp "$SO" "$(php -r "echo ini_get('extension_dir');")/libsql_php.so" \
 && echo "extension=libsql_php.so" > /usr/local/etc/php/conf.d/libsql.ini \
 && php -m | grep -i libsql \
 && rm /tmp/libsql.tar.gz

# Frontend build
RUN npm install --no-audit --no-fund && npm run build

# Remove temp .env (runtime gets real values via Render env vars)
RUN rm -f .env

# Storage dirs
RUN mkdir -p storage/framework/{cache/data,sessions,views,testing} \
             storage/logs storage/app/public bootstrap/cache \
 && chmod -R 775 storage bootstrap/cache

EXPOSE 8080

CMD ["sh", "-c", "\
    php artisan storage:link || true; \
    php artisan migrate --force && \
    php artisan db:seed --force; \
    php artisan config:cache; \
    php artisan route:cache; \
    php artisan view:cache || true; \
    php artisan serve --host=0.0.0.0 --port=${PORT:-8080}"]
