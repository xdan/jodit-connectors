FROM php:7.4-fpm
RUN apt-get update && apt-get install -y \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Install Composer.
ENV PATH=$PATH:/root/composer2/vendor/bin:/root/composer1/vendor/bin \
  COMPOSER_ALLOW_SUPERUSER=1 \
  COMPOSER_HOME=/root/composer2 \
  COMPOSER1_HOME=/root/composer1

RUN cd /opt \
  # Download installer and check for its integrity.
  && curl -sSL https://getcomposer.org/installer > composer-setup.php \
  # Install Composer 2 and expose `composer` as a symlink to it.
  && php composer-setup.php --install-dir=/usr/local/bin --filename=composer2 --2 \
  && ln -s /usr/local/bin/composer2 /usr/local/bin/composer \
  # Remove installer files.
  && rm /opt/composer-setup.php


WORKDIR /var/www

COPY ./*.php /var/www/
COPY ./*.json /var/www/
COPY ./*.lock /var/www/

RUN composer install && composer upgrade

CMD ["php-fpm"]

RUN chown -R www-data:www-data /var/www