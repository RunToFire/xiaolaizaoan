FROM php:8.3-cli-alpine

ARG TZ=Asia/Shanghai

WORKDIR /opt/www

RUN apk add --no-cache \
        bash \
        ca-certificates \
        curl-dev \
        curl \
        git \
        icu-dev \
        libjpeg-turbo-dev \
        libpng-dev \
        libwebp-dev \
        freetype-dev \
        font-noto-cjk \
        libstdc++ \
        libzip-dev \
        linux-headers \
        mysql-client \
        oniguruma-dev \
        openssl-dev \
        tzdata \
        unzip \
        zlib-dev \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && cp /usr/share/zoneinfo/${TZ} /etc/localtime \
    && echo "${TZ}" > /etc/timezone \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) \
        bcmath \
        curl \
        gd \
        intl \
        mbstring \
        mysqli \
        pcntl \
        pdo_mysql \
        sockets \
        zip \
    && pecl install redis swoole \
    && docker-php-ext-enable redis swoole \
    && apk del .build-deps \
    && rm -rf /tmp/pear ~/.pearrc

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-hyperf.ini
COPY docker/php/entrypoint.sh /usr/local/bin/hyperf-entrypoint

RUN chmod +x /usr/local/bin/hyperf-entrypoint

EXPOSE 9501

ENTRYPOINT ["hyperf-entrypoint"]
CMD ["php", "bin/hyperf.php", "start"]
