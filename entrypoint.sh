#!/bin/sh
# This Source Code Form is subject to the terms of the Mozilla Public
# License, v. 2.0. If a copy of the MPL was not distributed with this
# file, You can obtain one at http://mozilla.org/MPL/2.0/.
#
# This Source Code Form is "Incompatible With Secondary Licenses", as
# defined by the Mozilla Public License, v. 2.0.

# Configure Phabricator on startup from environment variables.

set -ex

cd phabricator

ln -fs /phabext_map/phutil_map src/__phutil_library_map__.php

test -n "${MYSQL_HOST}" \
  && /app/wait-for-mysql.php \
  && ./bin/config set mysql.host ${MYSQL_HOST}
test -n "${MYSQL_PORT}" \
  && ./bin/config set mysql.port ${MYSQL_PORT}
test -n "${MYSQL_USER}" \
  && ./bin/config set mysql.user ${MYSQL_USER}
set +x
test -n "${MYSQL_USER}" \
  && ./bin/config set mysql.pass ${MYSQL_PASS}
set -x
test -n "${1}" \
  && ARG=$(echo ${1:-start}  | tr [A-Z] [a-z])

case "$ARG" in
  "arc-liberate")
	  cd src
	  /app/arcanist/bin/arc liberate
	  ;;
  "test-ext")
	  # Find all extension tests and call them
	  cd src
	  /app/arcanist/bin/arc unit extensions/*/__tests__/*php extensions/*/*/__tests__/*php
	  ;;
  *)
      exec "$ARG"
      ;;
esac
