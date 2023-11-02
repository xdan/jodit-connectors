FROM chialab/php:8.2-fpm

#ADD docker/php.ini /usr/local/etc/php/php.ini
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
RUN echo "\nexpose_php = Off\n" >> "$PHP_INI_DIR/php.ini"
RUN echo "\nupload_max_filesize=10G\npost_max_size=10G\nmemory_limit=512M\n" >> "$PHP_INI_DIR/php.ini"

WORKDIR /var/www

COPY ./*.php /var/www/
COPY ./*.json /var/www/
COPY ./*.lock /var/www/

RUN composer install

RUN chown -R www-data:www-data /var/www