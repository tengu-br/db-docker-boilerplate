FROM php:fpm-alpine

#ADD PACKAGES
RUN apk update \
	&& apk add\
        freetds-dev \
        openldap-dev && \
        rm -rf /var/cache/apk/*

#PHP EXTENSIONS
RUN docker-php-ext-configure pdo_dblib \
    && docker-php-ext-install pdo_dblib

#COPY FILES
COPY . ./

CMD ["php-fpm"]