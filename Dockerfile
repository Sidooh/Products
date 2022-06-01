FROM composer:2.2 as build

COPY . /app/

RUN composer install --prefer-dist --no-dev --optimize-autoloader --no-interaction --ignore-platform-reqs

FROM php:8.1-apache-buster as production

ENV APP_ENV=production
ENV APP_DEBUG=false

RUN apt-get update -y && apt-get install -y \
    libicu-dev \
    supervisor

RUN docker-php-ext-configure opcache --enable-opcache \
    && docker-php-ext-install pdo pdo_mysql \
    && docker-php-ext-install intl

COPY docker/php/conf.d/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

COPY docker/000-default.conf /etc/apache2/sites-available/000-default.conf

COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

COPY --from=build /app /var/www/html

RUN chmod 777 -R /var/www/html/storage/ && \
    chown -R www-data:www-data /var/www/ && \
    a2enmod rewrite


#RUN chmod +x docker/run.sh
#
#CMD ["docker/run.sh"]
