#!/bin/sh
cd /var/www/html/mpmanager
#download composer
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php -r "if (hash_file('sha384', 'composer-setup.php') === 'dac665fdc30fdd8ec78b38b9800061b4150413ff2e3b6f88543c636f7cd84f6db9189d43a81e5503cda447da73c7e5b6') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
php composer-setup.php
ls -la
php -r "unlink('composer-setup.php');"
mv composer.phar /usr/local/bin/composer

composer install

echo "Executing command: $@"

# the main image's CMD arguments are somehow not passed to this script
# so we need to check if there are any arguments and if not, execute apache2-foreground which is the default CMD of the main image
if [ -z "$@" ]; then
    echo "No arguments supplied, executing apache2-foreground..."
    exec apache2-foreground
else
    exec "$@"
fi