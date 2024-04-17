    FROM php:8.2-fpm  
    RUN docker-php-ext-install pdo_mysql mysqli calendar
    RUN mv /usr/local/etc/php/php.ini-production /usr/local/etc/php/php.ini
    RUN sed -ri -e 's!;date.timezone =!date.timezone = "${TIMEZONE}"!g' /usr/local/etc/php/php.ini
