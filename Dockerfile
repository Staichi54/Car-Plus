# Dockerfile - PHP + Apache + Microsoft ODBC + pdo_sqlsrv/sqlsrv
FROM php:8.2-apache

# Variables (ajusta si quieres otra zona horaria)
ENV DEBIAN_FRONTEND=noninteractive \
    TZ=America/Bogota

# Instalar dependencias, MS ODBC repo, msodbcsql17 y herramientas necesarias
RUN apt-get update && apt-get install -y --no-install-recommends \
    apt-transport-https \
    ca-certificates \
    gnupg2 \
    curl \
    build-essential \
    autoconf \
    pkg-config \
    unixodbc-dev \
    locales \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Añadir repositorio de Microsoft y actualizar
RUN curl -sSL https://packages.microsoft.com/keys/microsoft.asc | apt-key add - \
 && curl -sSL https://packages.microsoft.com/config/debian/12/prod.list > /etc/apt/sources.list.d/mssql-release.list \
 && apt-get update \
 && ACCEPT_EULA=Y apt-get install -y msodbcsql17 mssql-tools

# Instalar herramientas necesarias para compilar las extensiones PECL
RUN apt-get update && apt-get install -y --no-install-recommends \
    g++ \
    make \
    && rm -rf /var/lib/apt/lists/*

# Instalar extensiones de PHP necesarias (opcional: pdo, mbstring, xml)
RUN docker-php-ext-install pdo pdo_mysql mbstring xml

# Instalar sqlsrv y pdo_sqlsrv via PECL
RUN pecl install sqlsrv pdo_sqlsrv \
 && docker-php-ext-enable sqlsrv pdo_sqlsrv

# Copiar la aplicación al directorio web de Apache
# Asume que tu proyecto (Index.php, Vehiculo.php, ...) está en la misma carpeta que este Dockerfile
COPY . /var/www/html/

# Ajustes de permisos (si es necesario)
RUN chown -R www-data:www-data /var/www/html \
 && find /var/www/html -type d -exec chmod 755 {} \; \
 && find /var/www/html -type f -exec chmod 644 {} \;

# Habilitar mod_rewrite (si lo necesitas)
RUN a2enmod rewrite headers

# Exponer puerto 80
EXPOSE 80

# Comando por defecto (ya lo trae la imagen)
CMD ["apache2-foreground"]
