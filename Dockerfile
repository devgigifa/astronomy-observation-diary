FROM php:8.2-apache

# Instala a extensão PDO MySQL
RUN docker-php-ext-install pdo pdo_mysql

# Habilita mod_rewrite do Apache (caso precise futuramente)
RUN a2enmod rewrite

# Ajuste de permissão para a pasta de uploads
RUN mkdir -p /var/www/html/uploads && chmod 777 /var/www/html/uploads
