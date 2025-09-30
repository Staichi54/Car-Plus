FROM php:8.2-apache

# Instalar dependencias necesarias
RUN apt-get update && apt-get install -y \
    gnupg \
    apt-transport-https \
    software-properties-common \
    curl \
    unzip \
    libzip-dev \
    unixodbc-dev \
    && rm -rf /var/lib/apt/lists/*

# Importar clave y repo de Microsoft para ODBC
RUN curl -sSL https://packages.microsoft.com/keys/microsoft.asc | apt-key add - \
    && curl -sSL https://packages.microsoft.com/config/debian/12/prod.list > /etc/apt/sources.list.d/mssql-release.list

# Instalar ODBC y herramientas SQL
RUN apt-get update && ACCEPT_EULA=Y apt-get install -y \
    msodbcsql17 \
    mssql-tools \
    && rm -rf /var/lib/apt/lists/*

# Agregar mssql-tools al PATH
ENV PATH="$PATH:/opt/mssql-tools/bin"

# Instalar extensiones PHP necesarias
RUN docker-php-ext-install pdo pdo_mysql zip

# Instalar y habilitar drivers de SQL Server
RUN pecl install sqlsrv pdo_sqlsrv \
    && docker-php-ext-enable sqlsrv pdo_sqlsrv

# Habilitar mod_rewrite
RUN a2enmod rewrite

# Configuración extra de Apache para permitir acceso
RUN echo '<Directory "/var/www/html">\n\
    Options Indexes FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' > /etc/apache2/conf-available/docker.conf \
    && a2enconf docker

# Copiar la aplicación
COPY . /var/www/html

# Dar permisos correctos a la app
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

EXPOSE 80
