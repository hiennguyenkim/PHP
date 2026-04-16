FROM php:8.3-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends libonig-dev \
    && docker-php-ext-install pdo_mysql mbstring \
    && a2enmod rewrite \
    && sed -ri 's!/var/www/html!/var/www/html/Project!g' /etc/apache2/sites-available/000-default.conf \
    && printf "<Directory /var/www/html/Project>\n    Options Indexes FollowSymLinks\n    AllowOverride All\n    Require all granted\n</Directory>\n" > /etc/apache2/conf-available/project.conf \
    && a2enconf project \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

COPY Project/ /var/www/html/Project/

EXPOSE 80
