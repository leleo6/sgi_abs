FROM php:8.2-apache

# Metadatos
LABEL maintainer="Institución Educativa ABS"
LABEL description="Contenedor Docker para el Sistema de Gestión Institucional y Académica (SGI-ABS)"

# Instalar instalador oficial de extensiones PHP para Docker
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/

# Instalar utilidades y extensiones de PHP requeridas
RUN apt-get update && apt-get install -y --no-install-recommends \
    zip \
    unzip \
    git \
    default-mysql-client \
    && install-php-extensions gd pdo_mysql zip mbstring fileinfo \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Habilitar módulos esenciales de Apache
RUN a2enmod rewrite headers

# Instalar Composer desde la imagen oficial
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Configurar Apache VirtualHost y configuración personalizada de PHP
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/custom.ini

# Establecer directorio de trabajo
WORKDIR /var/www/html

# Copiar el código fuente de la aplicación
COPY . /var/www/html/

# Instalar dependencias de Composer si existe composer.json
RUN if [ -f composer.json ]; then \
        composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader; \
    fi

# Preparar carpetas y permisos
RUN mkdir -p /var/www/html/uploads \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/uploads

# Configurar script de inicio (entrypoint)
COPY docker/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 80

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2-foreground"]
