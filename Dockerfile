FROM chialab/php:7.2-fpm

ARG USER_ID
ARG GROUP_ID

ADD docker /usr/local/etc/php/php.ini

WORKDIR /var/www

ADD ./*.php /var/www
ADD ./*.json /var/www
ADD ./*.lock /var/www

RUN composer install

CMD ["php-fpm"]