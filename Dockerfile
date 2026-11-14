
FROM dunglas/frankenphp:latest

RUN install-php-extensions pdo_mysql

ENV SERVER_NAME=:8080

EXPOSE 8080

WORKDIR /app

COPY . /app
