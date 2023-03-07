FROM php:5.4-fpm

RUN docker-php-ext-install mysqli && docker-php-ext-install mbstring

COPY ./ /mnt/dts
RUN chmod -R 777 /mnt/dts
COPY ./nginx /nginx
