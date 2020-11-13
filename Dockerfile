FROM php:7.3.17-fpm-alpine AS base

LABEL maintainer="dkl@mozilla.com"

# These are unlikely to change from version to version of the container
EXPOSE 9000
ENTRYPOINT ["/usr/local/bin/dumb-init", "--"]
CMD ["/app/entrypoint.sh", "start"]

# Git commit SHAs for the build artifacts we want to grab.
# From https://github.com/phacility/phabricator/tree/stable
# Promote 2020 Week 42 plus extra commit
ENV PHABRICATOR_GIT_SHA 86ad69863930ed5f1e63d3d5be00cae9f458496f
# From https://github.com/phacility/arcanist/tree/stable
# Promote 2020 Week 42
ENV ARCANIST_GIT_SHA ac54d61d7af20f5d65ba889974f23a86bfb6cd57
# Should match the phabricator 'repository.default-local-path' setting.
ENV REPOSITORY_LOCAL_PATH /repo
# Explicitly set TMPDIR
ENV TMPDIR /tmp

USER root

# Runtime dependencies
RUN apk --no-cache --update add \
    composer \
    curl \
    freetype \
    g++ \
    git \
    libjpeg-turbo \
    libmcrypt \
    libpng \
    make \
    mariadb-client \
    ncurses \
    procps \
    py-pygments \
    libzip \
    python-dev \
    py-pip

# Build PHP extensions
RUN apk --no-cache add --virtual build-dependencies \
        $PHPIZE_DEPS \
        curl-dev \
        freetype-dev \
        libjpeg-turbo-dev \
        libmcrypt-dev \
        libpng-dev \
        libzip-dev \
        mariadb-dev \
    && docker-php-ext-configure gd \
        --with-freetype-dir=/usr/include \
        --with-jpeg-dir=/usr/include \
        --with-png-dir=/usr/include \
    && docker-php-ext-install -j "$(nproc)" \
        curl \
        gd \
        iconv \
        mbstring \
        mysqli \
        pcntl \
    && pecl install apcu-5.1.17 \
    && docker-php-ext-enable apcu \
    && pecl install mcrypt-1.0.2 \
    && docker-php-ext-enable mcrypt \
    && pecl install zip-1.15.4 \
    && docker-php-ext-enable zip \
    && apk del build-dependencies

RUN wget -O /usr/local/bin/dumb-init https://github.com/Yelp/dumb-init/releases/download/v1.2.1/dumb-init_1.2.1_amd64 \
    && chmod 755 /usr/local/bin/dumb-init

# The container does not log errors by default, so turn them on
RUN { \
        echo 'php_admin_flag[log_errors] = on'; \
        echo 'php_flag[display_errors] = off'; \
    } | tee /usr/local/etc/php-fpm.d/zz-log.conf

# Phabricator recommended settings (skipping these will result in setup warnings
# in the application).
RUN { \
        echo 'always_populate_raw_post_data=-1'; \
        echo 'post_max_size="32M"'; \
    } | tee /usr/local/etc/php/conf.d/phabricator.ini

# add a non-privileged user for installing and running the application
RUN addgroup -g 10001 app && adduser -D -u 10001 -G app -h /app -s /bin/sh app

RUN mkdir $REPOSITORY_LOCAL_PATH
RUN chown app:app $REPOSITORY_LOCAL_PATH

WORKDIR /app
USER app

# Install Phabricator code
RUN curl -fsSL https://github.com/phacility/phabricator/archive/${PHABRICATOR_GIT_SHA}.tar.gz -o phabricator.tar.gz \
    && curl -fsSL https://github.com/phacility/arcanist/archive/${ARCANIST_GIT_SHA}.tar.gz -o arcanist.tar.gz \
    && tar xzf phabricator.tar.gz \
    && tar xzf arcanist.tar.gz \
    && mv phabricator-${PHABRICATOR_GIT_SHA} phabricator \
    && mv arcanist-${ARCANIST_GIT_SHA} arcanist \
    && rm phabricator.tar.gz arcanist.tar.gz \
    && ./arcanist/support/xhpast/build-xhpast.php

ENV COMPOSER_VENDOR_DIR /app/phabricator/externals/extensions
RUN composer global require hirak/prestissimo

# Move static resources to phabricator
COPY moz-extensions/src/motd/css/MozillaMOTD.css /app/phabricator/webroot/rsrc/css/MozillaMOTD.css
COPY moz-extensions/src/auth/PhabricatorBMOAuth.css /app/phabricator/webroot/rsrc/css/PhabricatorBMOAuth.css
COPY moz-extensions/src/auth/PhabricatorBMOAuth.js /app/phabricator/webroot/rsrc/js/PhabricatorBMOAuth.js

# Install Python dependencies
COPY --chown=app requirements.txt ./
USER root
RUN pip install --require-hashes -r requirements.txt
USER app

# Install PHP dependencies
COPY --chown=app composer.json composer.lock ./
RUN composer install --no-dev

COPY --chown=app patches patches

# Apply customization patches
# Phabricator
RUN \
    # Tell the shell to fail early if a command in the loop has a non-zero exit code.
    set -e && \
    cd /app/phabricator && \
    for i in /app/patches/phabricator/*.patch; do patch -p1 < $i; done

# Configure Phabricator to use moz-extensions library
RUN \
    mkdir /app/phabricator/conf/custom/ && \
    echo custom/moz-extensions > /app/phabricator/conf/local/ENVIRONMENT
COPY moz-extensions.conf.php /app/phabricator/conf/custom/

COPY --chown=app entrypoint.sh LICENSE phabext.json update_version_json.py wait-for-mysql.php ./
COPY --chown=app nginx/ nginx/
# Update version.json
RUN chmod +x /app/update_version_json.py /app/entrypoint.sh /app/wait-for-mysql.php && /app/update_version_json.py

FROM base AS production

USER root
RUN docker-php-ext-install -j "$(nproc)" opcache

# Install opcache recommended settings from
# https://secure.php.net/manual/en/opcache.installation.php
RUN { \
        echo 'opcache.memory_consumption=128'; \
        echo 'opcache.interned_strings_buffer=8'; \
        echo 'opcache.max_accelerated_files=4000'; \
        echo 'opcache.fast_shutdown=1'; \
        echo 'opcache.enable_cli=1'; \
        echo 'opcache.validate_timestamps=0'; \
    } | tee /usr/local/etc/php/conf.d/opcache.ini

USER app
COPY --chown=app moz-extensions moz-extensions
RUN chmod +x /app/moz-extensions/bin/*

FROM base as development

USER root
RUN apk add --no-cache $PHPIZE_DEPS \
    && pecl install xdebug-2.9.0 \
    && docker-php-ext-enable xdebug

RUN { \
        echo '[xdebug]'; \
        echo 'xdebug.remote_enable=1'; \
    } | tee /usr/local/etc/php/conf.d/xdebug.ini

USER app

FROM base AS test

USER root
RUN apk --update --no-cache add \
    bash \
    g++ \
    git \
    make

USER app
COPY --chown=app test-arcconfig /app/.arcconfig
COPY --chown=app moz-extensions moz-extensions
RUN chmod +x /app/moz-extensions/bin/*
