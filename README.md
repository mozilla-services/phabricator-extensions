Mozilla Phabricator extensions

# Development:

To update `/app/phabricator/src/__phutil_library_map__.php` in case mozphab
image or `phabricator-extensions` got changed:

  $ invoke liberate

Links `/tmp/phutil_map` to `./phutil_map` which is a 
`/app/phabricator/src/__phutil_library_map__.php` on the container side.

To test changes in code:

  $ invoke test
