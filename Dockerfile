FROM php:8.1-fpm

RUN docker-php-ext-install mysqli

COPY ./ /mnt/dts
RUN chmod -R 777 /mnt/dts
COPY ./nginx /nginx
