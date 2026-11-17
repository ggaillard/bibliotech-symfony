#!/usr/bin/env bash
# Préparation du projet après la création du Codespace.
# IDEMPOTENT : peut être relancé à tout moment (bash .devcontainer/post-create.sh).
set -e
umask 0002
cd /workspace/projetSymfony

echo "▶ Dépendances PHP (composer install)…"
composer install --no-interaction --prefer-dist --no-progress

echo "▶ Attente du serveur MySQL…"
for i in $(seq 1 60); do
  if php -r 'new PDO("mysql:host=mysql;port=3306", "root", "root");' 2>/dev/null; then break; fi
  sleep 2
done

echo "▶ Création des tables (migrations) et des données de démonstration (fixtures)…"
php bin/console doctrine:database:create --if-not-exists --no-interaction
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration
# Les données de démonstration ne sont chargées que dans une base vide :
# relancer ce script n'efface pas les livres que vous avez ajoutés.
NB_LIVRES=$(php bin/console dbal:run-sql "SELECT COUNT(*) AS n FROM livre" 2>/dev/null | grep -Eo '[0-9]+' | tail -1 || true)
if [ "${NB_LIVRES:-0}" = "0" ]; then
  php bin/console doctrine:fixtures:load --no-interaction
else
  echo "  (la base contient déjà $NB_LIVRES livres : données de démonstration non rechargées)"
fi

# Apache (www-data) doit pouvoir écrire dans var/ (cache et journaux)
mkdir -p var
chown -R www-data:www-data var
chmod -R ug+rwX var
find var -type d -exec chmod g+s {} +

echo ""
echo "✅ BiblioTech est prêt : onglet PORTS → port 8000 (application), 8080 (phpMyAdmin)."
echo "   Vérification : php bin/phpunit --group=socle"
