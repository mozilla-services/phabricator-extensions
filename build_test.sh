#!/bin/sh
apk --update --no-cache add bash g++ make git
cat << ONE > /app/.arcconfig
{
  "load": [
    "/app/phabricator/src"
  ]
}
ONE
    cat << TWO > /app/.arcunit
{
  "engines": {
    "phutil": {
      "type": "phutil",
      "include": "(\\.php$)"
    }
  }
}
TWO
