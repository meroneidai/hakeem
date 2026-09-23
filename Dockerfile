# syntax=docker/dockerfile:1.7
# First-release image: Node 22 builds Vite assets, then PHP-FPM + nginx serve Laravel.
# Prefer ./setup.sh --resetup for wipe + no-cache rebuild + migrate/seed.

FROM node:22-bookworm-slim AS assets
WORKDIR /app

COPY package.json package-lock.json ./
RUN node -v \
    && npm -v \
    && if [ -f package-lock.json ]; then \
        npm ci --no-fund --no-audit; \
    else \
        npm install --no-fund --no-audit; \
    fi

COPY vite.config.js ./
COPY resources ./resources
COPY public ./public
RUN npm run build \
    && test -f public/build/manifest.json \
    && echo "Vite manifest ready"

FROM php:8.4-fpm-bookworm AS app

ARG WWWUSER=33
ARG WWWGROUP=33

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    APP_RUNNING_IN_CONSOLE=0

RUN apt-get update && apt-get install -y --no-install-recommends \
        git \
        curl \
        unzip \
        nginx \
        supervisor \
        libpq-dev \
        libicu-dev \
        libzip-dev \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
        libonig-dev \
        libxml2-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_pgsql \
        pgsql \
        intl \
        zip \
        gd \
        bcmath \
        pcntl \
        opcache \
        mbstring \
        exif \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY --from=assets /usr/local/bin/node /usr/local/bin/node
COPY --from=assets /usr/local/lib/node_modules /usr/local/lib/node_modules

RUN ln -sfn /usr/local/lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm \
    && ln -sfn /usr/local/lib/node_modules/npm/bin/npx-cli.js /usr/local/bin/npx \
    && node -v \
    && npm -v

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install \
        --no-dev \
        --no-scripts \
        --no-interaction \
        --prefer-dist \
        --optimize-autoloader

COPY . .
COPY --from=assets /app/public/build ./public/build
COPY --from=assets /app/node_modules ./node_modules

RUN rm -f public/hot \
    && composer dump-autoload --optimize --no-dev --no-interaction \
    && php artisan package:discover --ansi || true

COPY docker/php.ini /usr/local/etc/php/conf.d/hakeem.ini
COPY docker/nginx.conf /etc/nginx/sites-available/default
COPY docker/supervisord.conf /etc/supervisor/conf.d/hakeem.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh

RUN rm -f /etc/nginx/sites-enabled/default \
    && ln -s /etc/nginx/sites-available/default /etc/nginx/sites-enabled/default \
    && sed -i 's/listen = .*/listen = 127.0.0.1:9000/' /usr/local/etc/php-fpm.d/www.conf \
    && chmod +x /usr/local/bin/entrypoint.sh \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-n", "-c", "/etc/supervisor/supervisord.conf"]
