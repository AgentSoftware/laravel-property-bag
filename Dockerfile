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

# PCOV gives fast code-coverage generation (vendor/bin/phpunit --coverage-*).
# $PHPIZE_DEPS is purged again after the extension is built so the image
# stays lean.
RUN apt-get update \
    && apt-get install -y --no-install-recommends $PHPIZE_DEPS \
    && pecl install pcov \
    && docker-php-ext-enable pcov \
    && apt-get purge -y --auto-remove $PHPIZE_DEPS \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app

CMD ["composer", "--version"]
