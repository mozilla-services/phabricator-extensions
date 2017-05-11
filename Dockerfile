FROM alpine

ARG EXTENSIONS_PATH=/app/phabricator/src/extensions

COPY . ${EXTENSIONS_PATH}

VOLUME ["/app/phabricator/src/extensions"]

CMD ["/bin/sh"]
