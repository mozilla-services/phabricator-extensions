FROM mozilla/mozphab:bb4e6d183893c8ec441a0193b06be24f6c06d22c as base

COPY extensions /app/moz-extensions

# Move static resources to phabricator, add files to celerity map array
COPY extensions/src/motd/css/MozillaMOTD.css /app/phabricator/webroot/rsrc/css/MozillaMOTD.css
COPY extensions/src/auth/PhabricatorBMOAuth.css /app/phabricator/webroot/rsrc/css/PhabricatorBMOAuth.css
COPY extensions/src/auth/PhabricatorBMOAuth.js /app/phabricator/webroot/rsrc/js/PhabricatorBMOAuth.js

USER root

# Install dependencies
ENV COMPOSER_ALLOW_SUPERUSER 1
ENV COMPOSER_VENDOR_DIR /app/phabricator/externals/extensions
RUN \
    curl -sS https://getcomposer.org/installer | php && \
    php composer.phar require sentry/sentry php-http/curl-client http-interop/http-factory-guzzle

# Apply customization patches
# Phabricator
COPY patches /app/patches
RUN \
    cd /app/phabricator && \
    for i in /app/patches/phabricator/*.patch; do patch -p1 < $i; done

# Configure Phabricator to use moz-extensions library
RUN \
    mkdir /app/phabricator/conf/custom/ && \
    echo custom/moz-extensions > /app/phabricator/conf/local/ENVIRONMENT
COPY moz-extensions.conf.php /app/phabricator/conf/custom/

# Update build_url in version.json
COPY phabext.json /app
COPY update_build_url.py /app
RUN chmod +x /app/update_build_url.py && /app/update_build_url.py

RUN chown -R app:app /app
RUN chmod +x /app/moz-extensions/bin/*

FROM base AS production
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
VOLUME ["/app"]

FROM base as development
RUN apk add --no-cache $PHPIZE_DEPS \
    && pecl install xdebug-2.9.0 \
    && docker-php-ext-enable xdebug
RUN { \
        echo '[xdebug]'; \
        echo 'xdebug.remote_enable=1'; \
    } | tee /usr/local/etc/php/conf.d/xdebug.ini
USER app
VOLUME ["/app"]