#!/bin/sh

set -e

# php artisan migrate:fresh --seed
#php artisan cache:clear
#php artisan route:cache
#php artisan optimize

a2enmod rewrite

/usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
