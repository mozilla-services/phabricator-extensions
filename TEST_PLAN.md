# Phabricator/BMO Integration Test Plan

### Purpose

This document contains a collection of tests which can be run manually (or
automated in the future) that verify the basic functionality of the
Phabricator/BMO Integration.

These tests will be useful when upgrading Phabricator to newer versions or when
adding new features to the integration to ensure everything works as expected.

- **Update this doc with new tests as features are added/changed.**
- **Use a [FAILING] to denote tests which do not work as expected in the
with the current Phabricator/BMO on production. Ideally link to a bug
with the fix.**
- **See the end of this doc for instructions on how to setup a local test
environment if that is what you need.**

# Tests

#### [Signing up is succssful]

**Test Plan**
- Create a new BMO account.
- Make sure to include an irc nick in the Real Name field.
  - E.g. "John Doe [:jdoe]"
- Go to Phabricator and login as the new user.
- Finish account creation.

**Results**
- On the Phabricator Create a New Account page
  - Username should be prefilled with the irc nick.
  - Real Name is correct and does not contain []
- Clicking register approval completes account creation without error.
  - On local test instances you might have to login as the Phabricator admin
    and approve the account (see the end of this doc for how).
- The account works as expected.

[EDGE CASE] Currently when you revoke the "MozPhab" API key on BMO, there
is no way to create a new one for Phabricator to use.

#### [Creating a revision is successful]

**Test Plan**
- Make sure you have arc properly setup on your machine and have run
  `arc install-certificate` using the Phabricator user you wish to test with.
- Go to BMO and create a new bug as the Bugzilla user that the Phabricator
  account is connected to. (Or use an existing public bug).
  - To create bugs directly and bypass triaging, go to:
    http://bmo.test/enter_bug.cgi?product=Firefox&format=__default__
- Create a new hg repo (see instructions at the bottom).
- Make some change to a file.
- Run `hg commit -A -m 'commit1: New changes`
- Run `arc diff .^`
- Input something for the title, summary, test plan, and the correct bug id.
- Save and exit the file.

**Results**
- arc diff only submitted the 1 newly created commit.
- Visit Phabricator and there should be a new revision with the title.
- The revision should contain the correct diff of the changes that were made
- The "Bugzilla Bug ID" is correct.
  - The Bug number is correct.
  - The link is correct based on the enviroment (bugzilla.mozilla.org in production,
    bmo.test in local testing, etc).
  - Note: If the bug id is short (like 3, 92, etc, which is common on local
    test environments) the link will not work but that is fine because it only
    works on very long bug ids which match what new bugs on production bmo
    will be using.
- Visiting the bug on bugzilla shows an x-phabricator-request attachment.

#### [Updating a revision is successful]

**Test Plan**
- `hg update` to a commit which you previously submitted with arc
- Make a change and `hg commit`
  - If you make a new commit run `arc diff .^^`
  - If you amend the existing commit run `arc diff .^`

**Results**
- arc should automatically detect a revision and ask you if you want to update it.
- The revision is updated with the new diff on Phabricator.
- The bug id and other information remains unchanged.


#### [Creating a secure revision is successful]

**Test Plan**
- Go to bugzilla and create a security bug.
- Create a new hg commit.
- Run `arc diff .^`.
- Enter the title, summary, test plan, and the bug id of the security bug.
- Submit the revision.

**Results**
- The diff and information of the revision are as expected.
- The revision has a "Custom Policy" attached to it.
- The revision added the creator as a subscriber.
- The revision is visible to the user who made it.
- The revision is visible to users belonging to the security groups of the bug.
- The revision is NOT visible to the public without logging in.
- The revision is NOT visible to logged in members without the correct permission.
- There is an x-phabricator-request attachment on the bug in Bugzilla.

#### [Creating a revision checks the bug id]

**Test Plan**
- Create a new hg commit.
- Run `arc diff .^`
- Enter information for the title, summary, and test plan.
- Enter the bug id, repeat for each expected result.

**Result**
- Entering no bug id fails.
- Entering an invalid bug id like "abcd efg", or "$1000", fails.
- Entering the id of a bug that does not exist fails.
- Entering the id of a bug of a secure revision that the user does not have
  access to fails.
- Entering a valid bug id is successful.

#### [Creating multiple revisions with the same bug id is successful]

**Test Plan**
- Create a new hg commit.
- Run `arc diff .^`
- Enter information for the title, summary, and test plan.
- Enter the bug id.
- Repeat and create another revision with the same bug id.

**Result**
- Both revisions are created successfully.
- There are 2 corresponding x-phabricator-request attachments on the bug in bugzilla.- Entering no bug id fails.

#### [Requesting a reviewer on a revision is successful]

**Test Plan**
- Ensure that you have 2 phabricator accounts that login via BMO ready to go.
  - If not, create a new bugzilla user and the phabricator account for it.
- Create a commit, run arc diff.
- Input the title, summary, test plan, and bug id of a public bug.
- For the reviewers field enter the Phabricator user name of the other account.

**Results**
- The revision is created as normal.
- The phabricator attachment on bugzilla is present.
- Phabricator shows the reviewer on the Revision.


#### [BROKEN] [Review Status is propogated to Bugzilla]

This test depends on unfinished changes to BMO.

**Test Plan**
- Login to Phabricator as the Reviewer on a Revision
- Accept the Revision

**Results**
- The attachment on the bug in Bugzilla should have

## How to setup a local test environment
- Install arcanist on your machine: https://secure.phabricator.com/book/phabricator/article/arcanist/#installing-arcanist
- Delete all docker containers and images from a previous instance of this test
  environment. Or delete EVERYTHING with:
  - `docker rm $(docker ps -a -q)`
  - `docker rmi $(docker images -q)`
  - `docker volume rm $(docker volume ls)`
- Clone the following repos.
  - https://github.com/mozilla-services/phabricator-extensions
  - https://github.com/mozilla-conduit/bmo-extensions
  - https://github.com/mozilla-conduit/docker-bmo
    - We are making a local clone of docker-bmo because it doesn't update the
      image on DockerHub unless someone commits something on the master branch.
      This could cause situations where changes merged in upstream BMO aren't
      reflected in the local image. We are going to build the image ourselves
      locally.
- In second to last line (i.e. ~63) of the docker-bmo Dockerfile add:
- `RUN chown -R $BUGZILLA_USER.$BUGZILLA_USER /var/www/html/bmo`
- In the docker-compose file in bmo-extensions replace the bmo.test build:
  ```yml
  build:
    context: ../docker-bmo
    dockerfile: ./Dockerfile
  ```
- Follow the instructions at https://github.com/mozilla-conduit/bmo-extensions/blob/master/HACKING.md
  - **\*WHILE DOING SO KEEP IN MIND:\***
  - Make sure to put an empty {} in the `version.json` when you make it.
  - If there are any custom changes you want to test in the phabricator-extensions
    repo, create a new git branch and merge the changes.
  - If there are any custom changes you want to test in for the bmo extensions,
    follow the instructions until you get to the point where you can enter inside
    the bmo container. At that point, modify the files directly. wget is inside
    the container to easily download patch files.
      - BMO files are at `/var/www/html/bmo/*`, or something close to that.
      - You will need to stop the container and run `docker-compose up` again
        to restart it with the new changes.
  - You do not need to add the test repo if you use the recommendations below.
  - The instructions to "proxy your port to 1090" mean to add a proxy config
    in the Firefox browser you are using to test with. Options -> Network Proxy
    -> Enter localhost (or ip of the thing running docker), port 1090.
  - You might have to run `cd phabricator && ./bin/storage upgrade` inside the
    phabricator container after the very first time you start it.
  - See the login for Phabricator below for when you have to edit the configuration.
    - Don't forget to change the bmo url to `http://bmo.test`
    - For the bmo api key you will have to generate one by logging into bmo.test
      as the phab-bot@bmo.tld user (one should already be made though).
  - Once you've been able to login through bmo auth (preferably with the new
    unprevileged bmo account you created [details below]), you will have to
    "Approve" it. Login as the Phabricator admin again and go to
    http://phabricator.test/people/query/approval/ and approve the new user.
- **[Recommended]** Create a dummy hg repo on bitbucket.org to use for testing.
- **[Recommended]** Setup Phabricator to use that repository. Create a new one
at http://phabricator.test/diffusion/edit/form/default/. Set repo URL to
observe mode.
- **[Recommended]** Add `apk add openssh` in the Phabricator Dockerfile, rebuld.
Then inside the container create a new ssh key, add it to your bitbucket account,
and run hg clone <repo>. Also run `hg config --edit`.
- **[Recommended]** Inside the Phabricator container alias arc with
`alias arc='/app/arcanist/bin/arc'`.
- **[Recommended]**: Login as admin@mozilla.bugs and create a new unprivileged
user at http://bmo.test/editusers.cgi?action=add. Then test with that user.
(Then login to phabricator.test with the new user and re-run
`arc install-certificate` so that arc uses the new user's credentials).
- **[TIP]** Logins that come preinstalled:
  - Bugzilla
    - User: admin@mozilla.bugs, Password: password
    - User: phab-bot@bmo.tld, Password: password
  - Phabricator:
    - Append a `?admin` to the login URL to show the hidden admin login form.
    - User: admin, Password: admin
