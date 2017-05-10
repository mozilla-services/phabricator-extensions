FROM alpine

ARG EXTENSIONS_PATH=/app/phabricator/src/extensions
ARG VERSION_FILE=${EXTENSIONS_PATH}/version.json

RUN apk --update add git

COPY . ${EXTENSIONS_PATH}

RUN cd ${EXTENSIONS_PATH}/.git \
    && version=$(git rev-parse HEAD) \
    && echo "{\"version\":\"${version}\"}" > ${VERSION_FILE}

VOLUME ["/app/phabricator/src/extensions"]

CMD ["/bin/sh"]
