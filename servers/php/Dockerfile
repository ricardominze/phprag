FROM php:8.3-cli

# Dependências (Postgres + build do Swoole + pdftotext para PDFs)
RUN apt-get update && apt-get install -y --no-install-recommends \
    git unzip curl ca-certificates \
    libpq-dev \
    libssl-dev \
    zlib1g-dev \
    libcurl4-openssl-dev \
    pkg-config \
    poppler-utils \
  && docker-php-ext-install pdo pdo_pgsql pgsql \
  && pecl install swoole \
  && docker-php-ext-enable swoole \
  && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

CMD ["php", "/app/server.php"]
