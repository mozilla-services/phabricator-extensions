FROM mozilla/mozphab:latest
ARG EXTENSIONS_PATH=/app/phabricator/src/extensions
COPY differential ${EXTENSIONS_PATH}/differential
COPY conduit ${EXTENSIONS_PATH}/conduit
COPY auth ${EXTENSIONS_PATH}/auth
VOLUME ["/app"]
