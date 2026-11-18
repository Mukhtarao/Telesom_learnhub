
FROM dunglas/frankenphp:latest

# Install MySQL extensions
RUN install-php-extensions pdo_mysql mysqli

# Application directory
WORKDIR /app

# Copy application files
COPY . /app

# Install the custom Caddy configuration
COPY Caddyfile /etc/frankenphp/Caddyfile
