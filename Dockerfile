FROM php:8.2-apache

# Cài đặt extension pdo_pgsql để kết nối Supabase Cloud
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Bật mod_rewrite của Apache
RUN a2enmod rewrite

# Sao chép mã nguồn SourceCode vào thư mục web server
COPY SourceCode/ /var/www/html/

# Phân quyền cho web server
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
CMD ["apache2-foreground"]
