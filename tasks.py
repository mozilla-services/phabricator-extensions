# This Source Code Form is subject to the terms of the Mozilla Public
# License, v. 2.0. If a copy of the MPL was not distributed with this
# file, You can obtain one at http://mozilla.org/MPL/2.0/.
import json
import os

from invoke import task

DOCKER_IMAGE_NAME = os.getenv('DOCKERHUB_REPO', 'mozilla/phabext')


@task
def version(ctx):
    """Print version information in JSON format."""
    print(json.dumps({
        'commit':
        os.getenv('CIRCLE_SHA1', None),
        'version':
        os.getenv('CIRCLE_SHA1', None),
        'source':
        'https://github.com/%s/%s' % (
            os.getenv('CIRCLE_PROJECT_USERNAME', 'mozilla-conduit'),
            os.getenv('CIRCLE_PROJECT_REPONAME', 'phabricator-extensions')
        ),
        'build':
        os.getenv('CIRCLE_BUILD_URL', None),
    }))


@task
def build(ctx):
    """Build the docker image."""
    ctx.run('docker build --pull -t {image_name} .'.format(
        image_name=DOCKER_IMAGE_NAME
    ))


@task
def imageid(ctx):
    """Print the built docker image ID."""
    ctx.run("docker inspect -f '{format}' {image_name}".format(
        image_name=DOCKER_IMAGE_NAME,
        format='{{.Id}}'
    ))

def link_phutil_map(ctx):
    ctx.run("ln -fs `pwd`/phutil_map /tmp/phutil_map")

@task
def build_test(ctx):
    """Test phabricator extensions."""
    link_phutil_map(ctx)
    ctx.run("docker-compose -f docker-compose.test.yml build phabricator")

@task
def test(ctx):
    """Test phabricator extensions."""
    link_phutil_map(ctx)
    ctx.run("docker-compose -f docker-compose.test.yml "
            "run --rm phabricator test-ext")

@task
def liberate(ctx):
    """Update phutil_map."""
    link_phutil_map(ctx)
    ctx.run("docker-compose -f docker-compose.test.yml "
            "run --rm phabricator arc-liberate")
