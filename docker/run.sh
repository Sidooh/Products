#!/bin/sh

set -e

cd /home/app || exit

# php artisan migrate:fresh --seed
php artisan cache:clear
php artisan route:cache
php artisan serve --host=0.0.0.0 --port=8080

/usr/bin/supervisord -c /etc/supervisord.conf
