FROM chialab/php:7.2-fpm

ARG USER_ID
ARG GROUP_ID

ADD docker /usr/local/etc/php/php.ini

RUN wget https://getcomposer.org/installer -O - -q \
    | php -- --install-dir=/bin --filename=composer --quiet

RUN usermod -u ${USER_ID} www-data && groupmod -g ${GROUP_ID} www-data

WORKDIR /var/www

USER "${USER_ID}:${GROUP_ID}"

ADD ./*.php /var/www
ADD ./*.json /var/www
ADD ./*.lock /var/www

RUN composer install

CMD ["php-fpm"]