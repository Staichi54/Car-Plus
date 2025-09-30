FROM php:8.2-apache

# Instalar dependencias necesarias
RUN apt-get update && apt-get install -y \
    gnupg2 \
    apt-transport-https \
    software-properties-common \
    curl \
    unzip \
    libzip-dev \
    && rm -rf /var/lib/apt/lists/*

# Importar la clave y el repo de Microsoft
RUN curl -sSL https://packages.microsoft.com/keys/microsoft.asc | apt-key add - \
    && curl -sSL https://packages.microsoft.com/config/debian/12/prod.list > /etc/apt/sources.list.d/mssql-release.list

# Instalar ODBC y herramientas SQL
RUN apt-get update && ACCEPT_EULA=Y apt-get install -y \
    msodbcsql17 \
    mssql-tools \
    unixodbc-dev \
    && rm -rf /var/lib/apt/lists/*

# Agregar mssql-tools al PATH
ENV PATH="$PATH:/opt/mssql-tools/bin"

# Instalar extensiones necesarias de PHP
RUN docker-php-ext-install pdo pdo_mysql zip

# Instalar SQLSRV y PDO_SQLSRV desde PECL
RUN pecl install sqlsrv pdo_sqlsrv \
    && docker-php-ext-enable sqlsrv pdo_sqlsrv

# Habilitar mod_rewrite
RUN a2enmod rewrite

# Copiar tu aplicación
COPY ./src /var/www/html

EXPOSE 80
