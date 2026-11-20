FROM php:8.3-apache
RUN docker-php-ext-install pdo_mysql \
    && a2enmod rewrite headers \
    && sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf \
    && echo "upload_max_filesize=10M\npost_max_size=50M" > /usr/local/etc/php/conf.d/uploads.ini
COPY . /var/www/html/
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/uploads
# Render asigna el puerto en $PORT
CMD sed -i "s/80/${PORT:-80}/g" /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf && apache2-foreground
