# Utilise une image PHP officielle comme base
FROM php:8.2-fpm

# Installe les dépendances système
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libjpeg-dev \
    libzip-dev \
    zip \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Installe les extensions PHP nécessaires pour Laravel
RUN docker-php-ext-install pdo_mysql exif pcntl bcmath gd

# Installe Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Définis le répertoire de travail
WORKDIR /var/www/html

# Copie le code de ton application
COPY . .

# Installe les dépendances Composer
RUN composer install --no-dev --optimize-autoloader

# Crée les permissions nécessaires
RUN chown -R www-data:www-data /var/www/html/storage \
    /var/www/html/bootstrap/cache

# Copie un fichier de configuration Nginx par défaut (ou un autre serveur web)
# Tu n'en auras peut-être pas besoin si Render gère le serveur web pour toi
# COPY ./docker/nginx/nginx.conf /etc/nginx/conf.d/default.conf

# Commande pour lancer le serveur
CMD ["php-fpm"]