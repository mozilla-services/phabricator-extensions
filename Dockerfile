FROM mozilla/mozphab:latest
ARG EXTENSIONS_PATH=/app/phabricator/src/extensions
COPY differential ${EXTENSIONS_PATH}/differential
COPY conduit ${EXTENSIONS_PATH}/conduit
COPY auth ${EXTENSIONS_PATH}/auth
COPY bugzilla ${EXTENSIONS_PATH}/bugzilla
COPY doorkeeper ${EXTENSIONS_PATH}/doorkeeper
# Move static resources to phabricator, add files to celerity map array
COPY auth/PhabricatorBMOAuth.css /app/phabricator/webroot/rsrc/css/PhabricatorBMOAuth.css
COPY auth/PhabricatorBMOAuth.js /app/phabricator/webroot/rsrc/js/PhabricatorBMOAuth.js
# Apply customization patches
COPY patches /app/patches
RUN cd /app/phabricator && for i in /app/patches/phabricator/*.patch; do patch -p1 < $i; done
# Update build_url in version.json
COPY phabext.json /app
COPY update_build_url.py /app
RUN /app/update_build_url.py
USER root
RUN chown -R app:app /app
USER app
VOLUME ["/app"]
