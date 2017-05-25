FROM mozilla/mozphab:latest
ARG EXTENSIONS_PATH=/app/phabricator/src/extensions
COPY differential ${EXTENSIONS_PATH}
COPY conduit ${EXTENSIONS_PATH}
COPY auth ${EXTENSIONS_PATH}
VOLUME ["/app"]
