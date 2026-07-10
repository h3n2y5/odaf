#!/bin/sh
# ODAF app container entrypoint.
# Menyiapkan environment secara idempoten lalu menjalankan server.
set -e

cd /var/www/html

# 1. Pastikan .env ada.
if [ ! -f .env ]; then
    echo "[odaf] .env tidak ditemukan, menyalin dari .env.example"
    cp .env.example .env
fi

# 2. Pasang dependensi bila vendor belum ada.
if [ ! -d vendor ]; then
    echo "[odaf] menjalankan composer install..."
    composer install --no-interaction --prefer-dist --no-progress
fi

# 3. Generate APP_KEY bila belum di-set.
if ! grep -q "^APP_KEY=base64:" .env 2>/dev/null; then
    echo "[odaf] generate APP_KEY..."
    php artisan key:generate --force || true
fi

echo "[odaf] menjalankan http server di :8000"
exec php artisan serve --host=0.0.0.0 --port=8000
