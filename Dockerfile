############################################################
# Dockerfile to run a php fpm application#
#
############################################################

FROM php:7.1.31-apache

MAINTAINER nelu <user@somedomain.com>

ENV APP_HOME 	/usr/src/app
ENV STORAGE_DIR     $APP_HOME/storage
ENV PATH        $PATH:$APP_HOME


RUN apt-get update && apt-get install -y sudo gosu bash unzip g++ make file re2c autoconf openssl libssl-dev libevent-dev

RUN  docker-php-ext-install sockets \
	&& rm -rf /usr/local/etc/php/conf.d/*sockets.ini && docker-php-ext-enable --ini-name 20-sockets.ini sockets \
	&& pecl install event \
    && docker-php-ext-enable --ini-name 30-event.ini event \
    && pecl install redis \
    && docker-php-ext-enable --ini-name 40-redis.ini redis \
    && docker-php-ext-install pdo_mysql pcntl \
    && rm -rf /usr/local/etc/php/conf.d/*mysql.ini && docker-php-ext-enable --ini-name 20-pdo_mysql.ini pdo_mysql \
    && rm -rf /usr/local/etc/php/conf.d/*pcntl.ini && docker-php-ext-enable --ini-name 10-pcntl.ini pcntl


RUN apt-get remove -y g++ make re2c autoconf libssl-dev libevent-dev libstdc++-8-dev g++-8 \
    && docker-php-source delete
RUN groupadd -g 1000 runuser && useradd -u 1000 -g 1000 -m runuser && usermod -a -G www-data runuser

RUN sh -c 'curl -s https://getcomposer.org/installer | php' \
    && mv composer.phar /usr/local/bin/composer

# copy websocket server project to the image
COPY . $APP_HOME
RUN chmod -R 775 $APP_HOME \
    && chown -R www-data:www-data  "$APP_HOME" \
    && cd "$APP_HOME" && gosu www-data composer install --no-dev

RUN rm -rf /var/www/html \
    && ln -s $APP_HOME/public /var/www/html \
    && ln -s /etc/apache2/mods-available/rewrite.load /etc/apache2/mods-enabled/rewrite.load \
    && ln -s /etc/apache2/mods-available/proxy_wstunnel.load /etc/apache2/mods-enabled/proxy_wstunnel.load \
    && ln -s /etc/apache2/mods-available/proxy.load /etc/apache2/mods-enabled/proxy.load \
    && rm -rf /etc/apache2/sites-enabled/000-default.conf /var/www/html/.htaccess \
    && ln -s "$APP_HOME/entrypoints/vhost.conf" /etc/apache2/sites-enabled/000-default.conf

VOLUME ["$APP_HOME","/var/log"]

WORKDIR $APP_HOME

