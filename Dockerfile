FROM php:8.5-apache-bookworm

RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev libzip-dev unzip \
    && docker-php-ext-install -j2 pdo_pgsql zip \
    && a2enmod rewrite headers \
    && cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
WORKDIR /var/www/html

# The uploaded release contains the complete application directory tree.
# Accept either a rootless ZIP or a ZIP with one enclosing project folder.
COPY AuraQuotes_Render_Beta.zip /tmp/aura-source.zip
RUN unzip -q /tmp/aura-source.zip -d /tmp/aura-release \
    && source_dir="$(dirname "$(find /tmp/aura-release -maxdepth 3 -type f -name artisan -print -quit)")" \
    && test -f "$source_dir/composer.lock" \
    && cp -a "$source_dir/." /var/www/html/ \
    && rm -rf /tmp/aura-source.zip /tmp/aura-release

RUN mkdir -p bootstrap/cache storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs \
    && composer install --no-dev --no-scripts --classmap-authoritative --prefer-dist --no-interaction --no-progress \
    && php artisan package:discover --ansi \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod 755 docker/entrypoint.sh \
    && cp docker/apache.conf /etc/apache2/sites-available/000-default.conf \
    && cp docker/server.conf /etc/apache2/conf-available/aura.conf \
    && cp docker/php.ini /usr/local/etc/php/conf.d/aura.ini \
    && printf 'Listen 8080\n' > /etc/apache2/ports.conf \
    && a2enconf aura

EXPOSE 8080
ENTRYPOINT ["/var/www/html/docker/entrypoint.sh"]
CMD ["apache2-foreground"]
