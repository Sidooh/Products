FROM composer:2.2 as build

COPY . /app

# TODO: Return --no-dev for production (removed for us to use clockwork in playdooh)
RUN composer install --prefer-dist --optimize-autoloader --no-interaction --ignore-platform-reqs

FROM php:8.1-buster as production

# Install system libraries
RUN apt-get update -y && apt-get install -y \
#    build-essential \
    libicu-dev
#    zlib1g-dev \
#    libmemcached-dev \
#    zip \
#    unzip

# Install docker dependencies
#RUN apt-get install -y \
#    && libc-client-dev libkrb5-dev \
#    && pecl install memcached-3.1.5 \
RUN docker-php-ext-install mysqli \
    && docker-php-ext-install intl \
#    && docker-php-ext-install sockets \
    && docker-php-ext-install pdo_mysql
#    && docker-php-ext-enable memcached

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Download composer
#RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Define working directory
WORKDIR /home/app

# Copy project
#COPY . /home/app
COPY --from=build /app /home/app


# Run composer install && update
#RUN composer install

# Expose the port
EXPOSE 8080

# Start artisan
CMD php artisan serve --host=0.0.0.0 --port=8080
