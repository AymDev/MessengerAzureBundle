# syntax=docker/dockerfile:1.3.1

ARG PHP_VERSION=7.3.30
ARG ALPINE_LINUX_VERSION=3.13
FROM composer:2.1.12 AS composer
FROM mlocati/php-extension-installer:1.4.2 AS php_ext_installer

FROM php:${PHP_VERSION}-cli-alpine${ALPINE_LINUX_VERSION}
WORKDIR /srv/app
ENV TERM xterm-256color

COPY --from=composer            /usr/bin/composer               /usr/bin/
COPY --from=php_ext_installer   /usr/bin/install-php-extensions /usr/bin/
