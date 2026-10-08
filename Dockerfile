FROM php:8.5-cli

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Composer must resolve over IPv4: in some networks IPv6 to repo.packagist.org
# is unreachable and installs hang on a timeout.
ENV COMPOSER_IPRESOLVE=4

# bash with pipefail: a failing command in a pipe fails the build instead of hiding.
SHELL ["/bin/bash", "-o", "pipefail", "-c"]

RUN set -eux; \
	apt-get update; \
	apt-get install -y --no-install-recommends git unzip; \
	rm -rf /var/lib/apt/lists/*

WORKDIR /app

# Install dependencies in a layer cached on the manifests alone.
COPY composer.json composer.lock* ./
RUN composer install --no-interaction --no-progress --no-scripts

COPY . .

CMD ["vendor/bin/phpunit"]
