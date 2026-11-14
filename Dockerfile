
FROM dunglas/frankenphp:latest

RUN install-php-extensions pdo_mysql

WORKDIR /app

COPY . /app

COPY Caddyfile /etc/frankenphp/Caddyfile

ENV SERVER_NAME=:8080
ENV SERVER_ROOT=/app

EXPOSE 8080
