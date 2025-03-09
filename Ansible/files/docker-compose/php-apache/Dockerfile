FROM php:8.3-apache

WORKDIR /var/www/html

COPY ./default-ssl.conf /etc/apache2/sites-available/default-ssl.conf
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

RUN sed -ri -e 's!SSLCertificateFile.*!SSLCertificateFile /etc/letsencrypt/live/${HABITAT_DOMAIN}/fullchain.pem!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!SSLCertificateKeyFile.*!SSLCertificateKeyFile /etc/letsencrypt/live/${HABITAT_DOMAIN}/privkey.pem!g' /etc/apache2/sites-available/*.conf

# ini files
COPY ./opcache.ini "$PHP_INI_DIR/conf.d/docker-php-ext-opcache.ini"
COPY ./file_uploads.ini "$PHP_INI_DIR/conf.d/file_uploads.ini"
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Install packages
RUN apt-get -y update && apt-get install -y libicu-dev libzip-dev zip libjpeg-dev libpng-dev libfreetype6-dev git nodejs npm
RUN docker-php-ext-configure intl
RUN docker-php-ext-configure gd '--with-jpeg' '--with-freetype'
RUN docker-php-ext-install intl opcache pdo_mysql zip gd exif
RUN a2enmod rewrite

RUN a2enmod ssl
RUN a2dissite 000-default
RUN a2ensite default-ssl

# Install ACPU
RUN pecl install apcu-5.1.22 && docker-php-ext-enable apcu
RUN echo "extension=apcu.so" > /usr/local/etc/php/php.ini
RUN echo "apc.enable_cli=1" > /usr/local/etc/php/php.ini
RUN echo "apc.enable=1" > /usr/local/etc/php/php.ini

# Override docker entrypoint to run app installation
COPY ./docker-php-entrypoint /usr/local/bin/docker-php-entrypoint
RUN chmod +x /usr/local/bin/docker-php-entrypoint
