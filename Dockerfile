
FROM dunglas/frankenphp:latest

RUN install-php-extensions pdo_mysql

WORKDIR /app

COPY . /app

ENV SERVER_NAME=:8080
ENV DOCUMENT_ROOT=/app

EXPOSE 8080
