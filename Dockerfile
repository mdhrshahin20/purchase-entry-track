FROM php:8.2-apache-bookworm

# PDO MySQL + Apache rewrite (clean URLs via root .htaccess)
RUN docker-php-ext-install pdo pdo_mysql \
    && a2enmod rewrite headers \
    && sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

COPY docker/apache-vhost.conf /etc/apache2/sites-available/000-default.conf
COPY docker/entrypoint.sh /usr/local/bin/app-entrypoint.sh
RUN chmod +x /usr/local/bin/app-entrypoint.sh \
    && sed -i 's/\r$//' /usr/local/bin/app-entrypoint.sh

WORKDIR /var/www/html

# Image build includes the app (no host bind-mount required at runtime)
COPY . /var/www/html

RUN chown -R www-data:www-data /var/www/html/config \
    && chmod -R u+rwX /var/www/html/config

EXPOSE 80

ENTRYPOINT ["app-entrypoint.sh"]
CMD ["apache2-foreground"]
