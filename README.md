Mozilla Phabricator extensions

[https://hub.docker.com/r/mozilla/phabext/](View on Docker Hub)


# Development:

After adding, renaming, or moving classes, run `arc liberate` to rebuild the
class map:

  $ invoke liberate

To test changes in code:

  $ invoke test
