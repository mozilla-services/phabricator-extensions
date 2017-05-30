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
def build_test(ctx):
    """Build the docker test image"""
    ctx.run('docker build --pull -t {image_name}:test .'.format(
        image_name=DOCKER_IMAGE_NAME
    ))


@task
def imageid(ctx):
    """Print the built docker image ID."""
    ctx.run("docker inspect -f '{format}' {image_name}".format(
        image_name=DOCKER_IMAGE_NAME,
        format='{{.Id}}'
    ))


@task
def testimageid(ctx):
    """Print the test docker image ID."""
    ctx.run("docker inspect -f '{format}' {image_name}:test".format(
        image_name=DOCKER_IMAGE_NAME,
        format='{{.Id}}'
    ))


@task
def run_tests(ctx):
    """Test test image"""
    ctx.run("docker run --rm {image_name}:test test-ext".format(
        image_name=DOCKER_IMAGE_NAME,
        format='{{.Id}}'
    ))
