FROM php:7.1.8-apache

ENV COMPOSER_ALLOW_SUPERUSER 1

RUN apt-get update && apt-get install -y \
    git zlib1g-dev

RUN docker-php-ext-install zip

RUN docker-php-ext-install -j$(nproc) pdo pdo_mysql

RUN a2enmod rewrite

COPY . /var/www/html
WORKDIR /var/www/html

RUN chmod -R -f 777 /var/www/html
RUN chown -R -f www-data /var/www/html
RUN chgrp -R -f www-data /var/www/html

ENV APACHE_RUN_USER www-data
ENV APACHE_RUN_GROUP www-data
ENV APACHE_LOG_DIR /var/log/apache2
ENV APACHE_LOCK_DIR /var/lock/apache2
ENV APACHE_PID_FILE /var/run/apache2.pid

RUN mkdir /var/www/html/package
RUN mkdir /var/www/html/workfolder

RUN chmod 777 /var/www/html/package
RUN chmod 777 /var/www/html/workfolder

COPY apache-config.conf /etc/apache2/sites-enabled/000-default.conf
CMD /usr/sbin/apache2ctl -D FOREGROUND
