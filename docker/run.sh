#!/bin/sh

cd /home/app || exit

# php artisan migrate:fresh --seed
php artisan cache:clear
php artisan route:cache
php artisan serve

/usr/bin/supervisord -c /etc/supervisord.conf
