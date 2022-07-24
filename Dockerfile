FROM composer as build

COPY . /app

# TODO: Return --no-dev for production (removed for us to use clockwork in playdooh)
RUN composer install --prefer-dist --optimize-autoloader --no-interaction --ignore-platform-reqs


FROM trafex/php-nginx

COPY --chown=nginx --from=composer /app /var/www/html
