FROM php:8.2-apache

# Instalar dependencias necesarias
RUN apt-get update && apt-get install -y \
    gnupg \
    curl \
    unzip \
    libzip-dev \
    unixodbc-dev \
    && rm -rf /var/lib/apt/lists/*

# Importar la clave y configurar el repo de Microsoft (sin apt-key)
RUN curl -sSL https://packages.microsoft.com/keys/microsoft.asc | gpg --dearmor -o /etc/apt/trusted.gpg.d/microsoft.gpg \
    && echo "deb [arch=amd64] https://packages.microsoft.com/debian/12/prod bookworm main" > /etc/apt/sources.list.d/mssql-release.list

# Instalar ODBC y herramientas SQL
RUN apt-get update && ACCEPT_EULA=Y apt-get install -y \
    msodbcsql17 \
    mssql-tools \
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
COPY . /var/www/html

EXPOSE 80
