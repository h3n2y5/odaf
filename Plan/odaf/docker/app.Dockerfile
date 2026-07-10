# =============================================================================
# ODAF Application Image - PHP 8.4 CLI + Oracle Instant Client + oci8
# =============================================================================
FROM php:8.4-cli-bookworm

ENV ORACLE_HOME=/opt/oracle/instantclient
ENV LD_LIBRARY_PATH=/opt/oracle/instantclient

# --- System dependencies ----------------------------------------------------
RUN apt-get update && apt-get install -y --no-install-recommends \
        libaio1 \
        unzip \
        curl \
        git \
        ca-certificates \
        libzip-dev \
    && rm -rf /var/lib/apt/lists/*

# --- Oracle Instant Client (Basic Lite + SDK) --------------------------------
# Menggunakan permalink "latest" Oracle (lebih stabil daripada versi spesifik).
# Untuk environment offline: letakkan kedua zip di docker/oracle/ dan ganti
# langkah curl dengan COPY.
RUN mkdir -p /opt/oracle \
    && cd /opt/oracle \
    && curl -fsSLO https://download.oracle.com/otn_software/linux/instantclient/instantclient-basiclite-linuxx64.zip \
    && curl -fsSLO https://download.oracle.com/otn_software/linux/instantclient/instantclient-sdk-linuxx64.zip \
    && unzip -o -q instantclient-basiclite-linuxx64.zip \
    && unzip -o -q instantclient-sdk-linuxx64.zip \
    && rm -f instantclient-*.zip \
    && mv instantclient_* instantclient \
    && echo /opt/oracle/instantclient > /etc/ld.so.conf.d/oracle-instantclient.conf \
    && ldconfig

# --- PHP extensions ----------------------------------------------------------
# yajra/laravel-oci8 memerlukan ekstensi oci8 (bukan pdo_oci).
RUN docker-php-ext-configure zip \
    && docker-php-ext-install zip \
    && echo 'instantclient,/opt/oracle/instantclient' | pecl install oci8 \
    && docker-php-ext-enable oci8

# --- Composer ----------------------------------------------------------------
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY docker/php.ini /usr/local/etc/php/conf.d/odaf.ini
COPY docker/entrypoint.sh /usr/local/bin/odaf-entrypoint
RUN chmod +x /usr/local/bin/odaf-entrypoint

WORKDIR /var/www/html

# Entrypoint menyiapkan env + composer install + key:generate lalu serve.
CMD ["odaf-entrypoint"]
