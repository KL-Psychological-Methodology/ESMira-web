FROM php:7.4-cli
ADD --chmod=0755 https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN apt-get update && install-php-extensions zip && install-php-extensions gd && install-php-extensions xdebug