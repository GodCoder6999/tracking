#!/usr/bin/env bash
set -e

echo "==> Installing Composer deps"
composer install --no-interaction --prefer-dist

echo "==> Installing npm deps"
npm install

echo "==> Preparing .env"
if [ ! -f .env ]; then cp .env.example .env; fi
php artisan key:generate --force

echo "==> Creating SQLite DB"
mkdir -p database
touch database/database.sqlite

echo "==> Running migrations + seeders"
php artisan migrate --force
php artisan db:seed --force

echo "==> Linking storage"
php artisan storage:link || true

echo "==> Building front-end"
npm run build

echo ""
echo "=========================================="
echo " Ready. Start the app with:"
echo "   php artisan serve --host=0.0.0.0 --port=8000"
echo " Then open the forwarded port 8000."
echo ""
echo " Logins:"
echo "   Owner  -> /owner-gate-7k9m2x  owner@tracking.local / ChangeMe!2026"
echo "   Dealer -> /dealer/login       dealer@demo.local / DealerDemo!1"
echo "   Client -> /client/login       client@demo.local / ClientDemo!1"
echo "=========================================="
