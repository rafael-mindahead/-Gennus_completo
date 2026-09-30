FROM php:8.2-apache
RUN docker-php-ext-install mysqli && a2enmod rewrite \
    && sed -i 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/*.conf \
    && printf '<Directory /var/www/html/public>\nAllowOverride All\nRequire all granted\n</Directory>\n' > /etc/apache2/conf-available/gennus.conf \
    && a2enconf gennus
COPY public/ /var/www/html/public/
COPY app/ /var/www/html/app/
EXPOSE 80
