FROM mozilla/mozphab:latest
ARG EXTENSIONS_PATH=/app/phabricator/src/extensions
COPY differential ${EXTENSIONS_PATH}/differential
COPY conduit ${EXTENSIONS_PATH}/conduit
COPY auth ${EXTENSIONS_PATH}/auth
# Update build_url in version.json
COPY phabext.json /app
COPY update_build_url.py /app
RUN /app/update_build_url.py
VOLUME ["/app"]
