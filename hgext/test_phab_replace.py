import json
import os
import re

from mercurial import (
    pycompat,
    registrar,
    templatekw,
)

testedwith = b"5.5.1"

keywords = {}
templatekeyword = registrar.templatekeyword(keywords)


def get_local_repo_callsign(ctx, repo) -> str:
    """Returns the callsign from the local repository's `.arcconfig` file."""
    arcconfig = json.loads(
        repo.filectx(b".arcconfig", changeid=ctx.node()).data()
    )

    return arcconfig["repository.callsign"]


def get_phab_server_callsign(differential_id: int) -> str:
    """Takes a revision of the form `DXXX` and returns the repo callsign for the
    given differential from the Conduit API."""
    raise NotImplementedError("todo")


def extsetup(ui):
    phabricator_uri = os.getenvb(b"PHABRICATOR_URI")
    if not phabricator_uri:
        return

    differential_revision_re = re.compile(
        br"Differential Revision: %sD(?P<differential_id>\d+)" % phabricator_uri
    )

    # Remove the existing `desc` template keyword implementation.
    del templatekw.keywords[b"desc"]

    # Add our new `desc` keyword implementation
    @templatekeyword(b"desc", requires={b"ctx", b"repo"})
    def showdescription_without_differential(context, mapping):
        # Use the default `desc` implementation to get the description.
        desc = templatekw.showdescription(context, mapping)

        # Return if we don't get a match.
        match = differential_revision_re.search(desc)
        if not match:
            return desc

        differential_id = int(match.group("differential_id"))

        ctx = context.resource(mapping, b"ctx")
        repo = context.resource(mapping, b"repo")

        # Grab callsign for current repo and current differential.
        local_callsign = get_local_repo_callsign(ctx, repo)
        phabricator_callsign = get_phab_server_callsign(differential_id)

        # If the callsign of the revision currently doesn't match the current
        # repo callsign, don't display the description in full.
        if local_callsign != phabricator_callsign:
            desc = differential_revision_re.sub(lambda _match: b"", desc)

        return desc

