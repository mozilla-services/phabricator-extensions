FROM mozilla/mozphab:latest
ARG EXTENSIONS_PATH=/app/phabricator/src/extensions
COPY . ${EXTENSIONS_PATH}
VOLUME ["/app"]
CMD ["/bin/sh"]
