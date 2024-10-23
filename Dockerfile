# syntax=docker/dockerfile:1

# This file is expected to be run by github actions after ESMira has already been built
# If you use it locally, you have to make sure there is a working dist/ directory by running:
# npm install
# npm run prod

FROM php:8.3.10-apache
ADD --chmod=0755 https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN install-php-extensions zip

#RUN apt-get update
#RUN apt-get install -y php8.0-zip

# Copy app files from the app directory.
COPY --chown=www-data:www-data ./dist /var/www/html

# Enable mod rewrite
RUN a2enmod rewrite
RUN service apache2 restart

# Use the default production configuration for PHP runtime arguments, see
# https://github.com/docker-library/docs/tree/master/php#configuration
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Set permissions:
RUN chown -R www-data:www-data /var/www/html


# Define volumes:
VOLUME /var/www/html/backend/config/
VOLUME /var/www/html/esmira_data/



# Setup entry script:
COPY ./docker-entrypoint.sh /
RUN chmod +x /docker-entrypoint.sh


## Switch to a non-privileged user (defined in the base image) that the app will run under.
## See https://docs.docker.com/go/dockerfile-user-best-practices/
#USER www-data


ENTRYPOINT ["/docker-entrypoint.sh"]
CMD ["apache2-foreground"]
