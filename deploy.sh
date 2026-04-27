#!/usr/bin/env bash
#
# Script de déploiement / mise à jour de PDP SALTI sur O2switch (ou serveur interne).
#
# Usage : à exécuter sur le serveur après un git pull
#   bash deploy.sh
#
# Pré-requis : PHP 8.3+, Composer, accès en écriture aux dossiers storage/ et bootstrap/cache/
#

set -e

cd "$(dirname "$0")"

echo "================================================"
echo "  Déploiement PDP SALTI"
echo "================================================"

# 1. Mise à jour du code (si lancé après un git clone, sinon le pull est déjà fait)
if [ -d .git ]; then
    echo ""
    echo "→ Pulling latest code..."
    git pull --ff-only
fi

# 2. Dépendances PHP
echo ""
echo "→ Installation des dépendances Composer..."
composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

# Manuellement : artisan package:discover (skip dans --no-scripts)
php artisan package:discover --ansi 2>/dev/null || true

# 3. Configuration
if [ ! -f .env ]; then
    echo ""
    echo "⚠ Fichier .env manquant — création depuis .env.example"
    cp .env.example .env
    php artisan key:generate
    echo ""
    echo "🛑 Arrêt : éditez le .env (BDD, mail) puis relancez deploy.sh"
    exit 1
fi

# 4. Migrations
echo ""
echo "→ Migrations BDD..."
php artisan migrate --force

# 5. Premier seed si la table users est vide
SEED_NEEDED=$(php artisan tinker --execute="echo App\\Models\\User::count();" 2>/dev/null | tail -1)
if [ "$SEED_NEEDED" = "0" ]; then
    echo ""
    echo "→ Seed initial (comptes QSE + agences de démo)..."
    php artisan db:seed --force
fi

# 6. Lien symbolique pour le storage public
if [ ! -L public/storage ]; then
    php artisan storage:link
fi

# 7. Caches Laravel
echo ""
echo "→ Génération des caches Laravel..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 8. Permissions
echo ""
echo "→ Permissions storage/ et bootstrap/cache/..."
chmod -R 775 storage bootstrap/cache 2>/dev/null || true

echo ""
echo "================================================"
echo "  ✓ Déploiement terminé"
echo "================================================"
echo ""
echo "Prochaines étapes :"
echo "  - Pointer le domaine sur ./public"
echo "  - Tester la connexion : qse@salti.fr / changeme"
echo "  - Changer immédiatement les mots de passe par défaut"
echo ""
