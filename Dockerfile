FROM chialab/php:7.2-fpm

RUN apt-get update -y \
    && apt-get install -y nginx

#ADD docker/php.ini /usr/local/etc/php/php.ini
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

WORKDIR /var/www

COPY ./*.php /var/www/
COPY ./*.json /var/www/
COPY ./*.lock /var/www/

COPY ./docker/nginx.conf /etc/nginx/sites-enabled/default
COPY ./docker/entrypoint.sh /etc/entrypoint.sh
RUN chmod +x /etc/entrypoint.sh

RUN composer install && composer upgrade

RUN chown -R www-data:www-data /var/www

EXPOSE 80 443

ENTRYPOINT ["/etc/entrypoint.sh"]