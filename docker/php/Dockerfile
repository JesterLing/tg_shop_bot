FROM node:latest AS node

RUN mkdir -p /home/frontend
WORKDIR /home/frontend
COPY ./client ./
RUN npm install
RUN npm run prod

FROM php:8.0-fpm

RUN apt-get update && apt-get install -y --no-install-recommends \
    zip \
    unzip \
	libzip-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libwebp-dev \
    libfreetype6-dev \
    openssl

RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp

# Enable php-exts
RUN docker-php-ext-install \
    mysqli \
    pdo_mysql \
    zip \
    gd 

# Get compiled frontend
COPY ./public /var/www/public
COPY --from=node /home/public/dist /var/www/public/dist

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Get backend and install deps
COPY .env /var/www
RUN mkdir -p /var/www/server
WORKDIR /var/www/server
COPY ./server ./
RUN composer install

# Generate self-signed SSL
WORKDIR /var/www
RUN mkdir -p ssl
ARG APP_URL
RUN openssl req -newkey rsa:2048 -sha256 -nodes -keyout ./ssl/private.key -x509 -days 365 -out ./ssl/public.pem -subj "/CN=$(echo ${APP_URL} | awk -F[/:] '{print $4}')"