# Conteneur « app » : Apache + PHP 8.4 + Composer (même base que LaravelBase)
FROM php:8.4-apache

# intl : formats de dates et de nombres en français ; zip/unzip : Composer
RUN apt-get update \
    && apt-get install -y --no-install-recommends git zip unzip libicu-dev default-mysql-client \
    && docker-php-ext-install pdo_mysql intl opcache \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

# Apache sert le dossier public/ du projet Symfony.
# Le fichier public/.htaccess (symfony/apache-pack) envoie chaque URL vers public/index.php.
RUN sed -i 's#/var/www/html#/workspace/projetSymfony/public#g' /etc/apache2/sites-available/000-default.conf \
    && printf '<Directory /workspace/projetSymfony/public>\n    AllowOverride All\n    Require all granted\n    FallbackResource /index.php\n</Directory>\nServerName localhost\n' \
       > /etc/apache2/conf-available/symfony.conf \
    && a2enconf symfony

# Droits : Apache (www-data) doit pouvoir écrire dans var/ (cache et journaux).
# Le script attend que le projet soit présent, puis corrige les droits.
# Le bit setgid fait hériter le groupe www-data aux fichiers créés ensuite,
# et umask 0002 (terminal root) les laisse modifiables par ce groupe :
# un « php bin/console … » lancé au terminal ne bloque plus Apache.
RUN printf '#!/bin/bash\n\
while [ ! -f /workspace/projetSymfony/bin/console ]; do sleep 1; done\n\
cd /workspace/projetSymfony\n\
mkdir -p var\n\
chown -R www-data:www-data var\n\
chmod -R ug+rwX var\n\
find var -type d -exec chmod g+s {} +\n' > /usr/local/bin/symfony-permissions \
    && chmod +x /usr/local/bin/symfony-permissions \
    && echo 'umask 0002' >> /root/.bashrc

WORKDIR /workspace/projetSymfony

CMD ["sh", "-c", "/usr/local/bin/symfony-permissions & exec apache2-foreground"]
