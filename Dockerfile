
FROM dunglas/frankenphp:latest

RUN install-php-extensions pdo_mysql mysqli

WORKDIR /app

COPY . /app

COPY Caddyfile /etc/frankenphp/Caddyfile

ENV SERVER_NAME=:8080

EXPOSE 8080
