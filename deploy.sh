#!/bin/bash
# =============================================================
#  PDFkrub — VPS Deploy Script
#  Usage: ./deploy.sh [--first-time]
#  Author: PDFkrub Team
# =============================================================
set -e

APP_DIR="/var/www/pdfkrub"
PHP="php8.4"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

echo ""
echo "╔══════════════════════════════════════╗"
echo "║   🚀  PDFkrub Deploy — $TIMESTAMP   ║"
echo "╚══════════════════════════════════════╝"
echo ""

cd $APP_DIR

# ─── First-time setup flag ──────────────────────────────────
FIRST_TIME=false
if [ "$1" == "--first-time" ]; then
    FIRST_TIME=true
    echo "📦 First-time setup mode"
fi

# ─── 1. Maintenance Mode ────────────────────────────────────
echo "⏸  Enabling maintenance mode..."
$PHP artisan down --secret="bypass-$(date +%s)" 2>/dev/null || true

# ─── 2. Pull Latest Code ────────────────────────────────────
if git rev-parse --git-dir > /dev/null 2>&1; then
    echo "📥 Pulling latest code from Git..."
    git pull origin main --quiet
fi

# ─── 3. PHP Dependencies ────────────────────────────────────
echo "📚 Installing PHP dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction --quiet

# ─── 4. Build Frontend ──────────────────────────────────────
echo "🎨 Building frontend assets..."
npm ci --silent
npm run build --silent
echo "   ✅ Assets built: $(ls public/build/ | wc -l) files"

# ─── 5. Database ────────────────────────────────────────────
echo "🗄️  Running migrations..."
$PHP artisan migrate --force --quiet

if [ "$FIRST_TIME" = true ]; then
    echo "🌱 Seeding database..."
    $PHP artisan db:seed --class=PlanSeeder --force --quiet
    $PHP artisan db:seed --class=AdminUserSeeder --force --quiet
fi

# ─── 6. Cache ───────────────────────────────────────────────
echo "⚡ Optimizing..."
$PHP artisan optimize:clear --quiet
$PHP artisan config:cache --quiet
$PHP artisan route:cache --quiet
$PHP artisan view:cache --quiet

# ─── 7. Permissions ─────────────────────────────────────────
echo "🔐 Fixing permissions..."
sudo chown -R pdfkrub:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

# ─── 8. Queue Workers ───────────────────────────────────────
echo "⚙️  Restarting queue workers..."
$PHP artisan queue:restart --quiet
sudo supervisorctl restart pdfkrub-worker:* > /dev/null 2>&1 || true

# ─── 9. Health Check ────────────────────────────────────────
echo "🏥 Running health check..."
HEALTH=$(curl -s -o /dev/null -w "%{http_code}" http://localhost/api/v1/health 2>/dev/null || echo "000")
if [ "$HEALTH" == "200" ]; then
    echo "   ✅ API Health: OK"
else
    echo "   ⚠️  API Health: $HEALTH (will be OK after maintenance off)"
fi

# ─── 10. Bring Back Online ──────────────────────────────────
echo "▶️  Disabling maintenance mode..."
$PHP artisan up --quiet

echo ""
echo "╔══════════════════════════════════════╗"
echo "║   ✅  Deploy complete!               ║"
echo "╚══════════════════════════════════════╝"
echo ""
echo "📊 Quick stats:"
echo "   App URL:    $(grep APP_URL .env | cut -d= -f2)"
echo "   DB:         $(grep DB_DATABASE .env | cut -d= -f2)"
echo "   Queue:      $(sudo supervisorctl status pdfkrub-worker:* 2>/dev/null | head -1 | awk '{print $2}')"
echo "   Log:        tail -f storage/logs/laravel.log"
echo ""
