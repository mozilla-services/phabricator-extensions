Mozilla Phabricator extensions

[View on Docker Hub](https://hub.docker.com/r/mozilla/phabext/)


# Development:

After adding, renaming, or moving classes, run `arc liberate` to rebuild the
class map:

  $ invoke liberate

To test changes in code:

  $ invoke test
