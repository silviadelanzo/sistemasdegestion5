# PHP-Apache with OCR deps
FROM php:8.1-apache

# System deps: tesseract (spa), poppler-utils (pdftotext) and locales
RUN apt-get update && apt-get install -y \
    tesseract-ocr \
    tesseract-ocr-spa \
    poppler-utils \
    locales \
    && rm -rf /var/lib/apt/lists/*

# Enable Apache mods
RUN a2enmod rewrite

# Set locale to es_AR.UTF-8
RUN sed -i 's/# es_AR.UTF-8 UTF-8/es_AR.UTF-8 UTF-8/' /etc/locale.gen \
    && locale-gen es_AR.UTF-8
ENV LANG=es_AR.UTF-8 \
    LANGUAGE=es_AR:es \
    LC_ALL=es_AR.UTF-8

# Copy app
WORKDIR /var/www/html
COPY . /var/www/html

# Permissions for writable dirs
RUN mkdir -p assets/uploads assets/scanner_input assets/scanner_processed \
    && chown -R www-data:www-data assets \
    && find assets -type d -exec chmod 775 {} + \
    && find assets -type f -exec chmod 664 {} +

# Expose
EXPOSE 80

# Healthcheck
HEALTHCHECK --interval=30s --timeout=5s --retries=3 \
 CMD curl -fsS http://localhost/healthcheck.php || exit 1
