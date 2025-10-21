# Use the official PHP image with Apache web server
FROM php:8.2-apache 

# Copy your application files into the web server's root directory
COPY . /var/www/html/

# Expose port 80 (standard HTTP port used by Apache)
EXPOSE 80

# Apache automatically starts, no need for a start command here
# PHP files including index.html will be served
