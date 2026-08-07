# syntax=docker/dockerfile:1

# Node is used only in this build stage to compile the React frontend; the
# runtime image below runs no permanent Node service (ICD_PROTOTYPE_DEVELOPMENT_BRIEF.md, Section 17).
FROM node:22-alpine AS frontend-build
WORKDIR /app/frontend
COPY frontend/package.json frontend/package-lock.json* ./
RUN npm ci
COPY frontend/ ./
RUN npm run build

FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock* ./
RUN composer install --no-dev --no-interaction --optimize-autoloader --no-scripts --no-progress

FROM php:8.4-apache AS runtime
RUN docker-php-ext-install pdo_mysql \
    && a2enmod rewrite \
    && rm -f /etc/apache2/sites-enabled/000-default.conf

COPY docker/apache-vhost.conf /etc/apache2/sites-enabled/000-default.conf

WORKDIR /var/www/html
COPY --from=vendor /app/vendor ./vendor
COPY src ./src
COPY public/index.php public/.htaccess ./public/
COPY --from=frontend-build /app/public/. ./public/

EXPOSE 80
