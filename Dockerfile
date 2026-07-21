# Dev-only image for running the test suite locally. Not referenced by
# composer.json and has no bearing on the published package.
FROM php:8.4-cli

# pdo_sqlite, mbstring, dom and tokenizer ship enabled by default in the
# official php-cli images; only unzip (needed by Composer to extract dist
# archives) and Composer itself are missing.
RUN apt-get update \
    && apt-get install -y --no-install-recommends unzip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

CMD ["composer", "--version"]
