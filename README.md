# Mozilla Phabricator Extensions

## Prerequisites

 * `docker`
 * `docker-compose`
 * Firefox, or some other way to connect your browser to a SOCKS proxy.

## Installation

 1. Pull the repository into a separate (e.g. `phabricator-extensions`) directory.
 1. For Phabricator only, from within the `phabricator-extensions` directory run `docker-compose up --build`.
 1. If you want a Bugzilla instance to also start preconfigured to interact with Phabricator, then instead
 do `docker-compose -f docker-compose.yml -f docker-compose.bmo.yml up --build`.
 1. Phabricator-extensions build process requires existence of the `phabext.json`
file. Please add it with the command: `$ echo "{}" > phabricator-extensions/phabext.json`

## Accessing the websites provided by the demo

### Firefox configuration

You can either configure the existing Firefox to use our proxy, or run a
preconfigured Firefox.

**To configure your current browser**:

1. Open `Preferences -> Net Proxy -> Settings`
1. Choose the `Manual Proxy Configuration` radio button
1. Set the `Proxy HTTP Server` to `localhost`, and the `Port` to `1080`.

**To run Firefox with an empty profile**:

1. Please set the environment variable `FIREFOX_CMD` to `/path/to/firefox` if
   your system does not recognize the `firefox` command.
1. In a new terminal, run `firefox-proxy`, or
   `firefox-proxy $(docker-machine ip)` if you are using `docker-machine`.
1. A new browser with an empty profile will open.

### Websites provided by the demo

 * Phabricator - http://phabricator.test
 * Bugzilla (optional) - http://bmo.test

## Preconfigured users:

For performing administration tasks in Phabricator, first log out of
Phabricator and then go to http://phabricator.test/?admin=1

`user:admin`, `password:password123456789!`

For logging in as a normal test user, you will need to use BMO for
auth-delegation. Log out in Phabricator and then click on 'Log In or
Register'. You will be redirected to BMOs login page.

`user:conduit@mozilla.bugs`, `password:password123456789!`

After login, if it complains that you do not have MFA enabled on your
BMO account, click on the 'preferences' link that will allow you to configure
TOTP and then you should be able to login to Phabricator.

For performing administrative tasks on BMO, you will need to log out of BMO
and then login on http://bmo.test/login with the following credentials.

`user:admin@mozilla.bugs`, `password:Te6Oovohch`

## PHP Development:

Install Python dependencies:

`$ pip install -r requirements.txt`

After adding, renaming, or moving classes, run `arc liberate` to rebuild the
class map:

`$ invoke liberate`

To test changes in code:

`$ invoke test`