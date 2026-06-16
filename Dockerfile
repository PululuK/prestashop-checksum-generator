# syntax=docker/dockerfile:1

###############################################################################
# Build stage: resolve the (dev-free) autoloader with Composer.
# The application has no runtime Composer dependencies, so "vendor/" only ends
# up containing an optimized, authoritative class map.
###############################################################################
FROM composer:2 AS build

WORKDIR /app

# Copy only the dependency manifests first to maximize Docker layer caching.
COPY app/composer.json app/composer.lock ./
RUN composer install \
        --no-dev \
        --no-scripts \
        --no-autoloader \
        --no-interaction \
        --no-progress

# Copy the source and generate an authoritative, optimized class map.
COPY app/src ./src
COPY app/bin ./bin
RUN composer dump-autoload --no-dev --optimize --classmap-authoritative

###############################################################################
# Runtime stage: a minimal, non-root PHP CLI image.
###############################################################################
FROM php:8.4-cli-alpine AS runtime

LABEL org.opencontainers.image.title="prestashop-checksum-generator" \
      org.opencontainers.image.description="Generate a PrestaShop-compatible checksum XML for a directory tree." \
      org.opencontainers.image.source="https://github.com/PululuK/prestashop-checksum-generator" \
      org.opencontainers.image.licenses="MIT"

# OPcache makes repeated CI runs of the script start faster.
RUN docker-php-ext-enable opcache

# Run as an unprivileged user; CI runners should never need root here.
RUN addgroup -S checksum && adduser -S -G checksum checksum

WORKDIR /app
COPY --from=build /app /app
RUN chmod +x /app/bin/generate-checksum

USER checksum

ENTRYPOINT ["php", "/app/bin/generate-checksum"]
CMD ["--help"]
