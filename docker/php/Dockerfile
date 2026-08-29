FROM php:8.2-apache

RUN docker-php-ext-install pdo pdo_mysql \
    && a2enmod rewrite

# Apache should serve the repo root (index.html + api/ + assets/) directly
WORKDIR /var/www/html
COPY . /var/www/html

# private_storage must never be web-servable — belt-and-suspenders on top
# of its own .htaccess, in case mod_rewrite/AllowOverride differs by host
RUN mkdir -p /var/www/private_storage/resumes \
    && chown -R www-data:www-data /var/www/html /var/www/private_storage

