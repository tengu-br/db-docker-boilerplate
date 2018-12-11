FROM php:fpm-alpine

#ADD PACKAGES
RUN apk update \
	&& apk add\
	    curl\
        freetds-dev && \
        rm -rf /var/cache/apk/*

#PHP EXTENSIONS
RUN docker-php-ext-configure pdo_dblib \
    && docker-php-ext-install pdo_dblib

#COMPOSER
#RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
#RUN composer require ramsey/uuid
#RUN composer install

#COPY FILES
COPY . ./

CMD ["php-fpm"]