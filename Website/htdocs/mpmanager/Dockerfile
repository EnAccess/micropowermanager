FROM php:8.2-apache

WORKDIR /app
COPY . /app

RUN apt-get update && \
    apt-get install -y \
    cron \
    libjpeg-dev \
    libfreetype6-dev \
    libxml2-dev \
    libssl-dev \
    mariadb-client \
    zlib1g-dev \
    bzip2 \
    supervisor \
    libzip-dev \
    vim \
    git \
    zsh

# install ohmyzsh
RUN chsh -s $(which zsh)
RUN sh -c "$(curl -fsSL https://raw.github.com/ohmyzsh/ohmyzsh/master/tools/install.sh)"

# add php artisan alias
RUN echo 'alias "cmd=php artisan"'  >> ~/.zshrc

# remove apt lists
RUN rm -rf /var/lib/apt/lists/*

# install php extentions
RUN docker-php-ext-install gd mysqli zip pdo pdo_mysql soap ftp opcache bcmath pcntl
RUN docker-php-ext-configure gd --with-freetype --with-jpeg\
&& docker-php-ext-configure pcntl --enable-pcntl

RUN touch /usr/local/etc/php/conf.d/xdebug.ini; \
    echo xdebug.remote_enable=1 >> /usr/local/etc/php/conf.d/xdebug.ini; \
    echo xdebug.remote_autostart=0 >> /usr/local/etc/php/conf.d/xdebug.ini; \
    echo xdebug.remote_connephpct_back=1 >> /usr/local/etc/php/conf.d/xdebug.ini; \
    echo xdebug.remote_port=9000 >> /usr/local/etc/php/conf.d/xdebug.ini; \
    echo xdebug.remote_log=/tmp/php5-xdebug.log >> /usr/local/etc/php/conf.d/xdebug.ini;log=/tmp/php5-xdebug.log >> /usr/local/etc/php/conf.d/xdebug.ini;

RUN php composer.phar install

EXPOSE 80

COPY vhost.conf /etc/apache2/sites-available/000-default.conf

RUN chown -R www-data:www-data /app \
    && a2enmod rewrite

