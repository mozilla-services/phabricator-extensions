<?php
# This Source Code Form is subject to the terms of the Mozilla Public
# License, v. 2.0. If a copy of the MPL was not distributed with this
# file, You can obtain one at http://mozilla.org/MPL/2.0/.
#
# This Source Code Form is "Incompatible With Secondary Licenses", as
# defined by the Mozilla Public License, v. 2.0.

class DifferentialRevisionWarning extends Phobject {
  private $warnings = array();

  public function createWarnings($viewer, $revision) {
    $this->warnings[] = $this->createSecurityWarning($viewer, $revision);
    return $this->warnings;
  }

  public function createSecurityWarning($viewer, $revision) {
    // Load assigned projects (tags).
    $revision_projects = PhabricatorEdgeQuery::loadDestinationPHIDs(
      $revision->getPHID(),
      PhabricatorProjectObjectHasProjectEdgeType::EDGECONST);

    if (!(bool)$revision_projects) {
      return null;
    }

    // Load a secure-revision project
    // TODO cache the result
    $secure_group = id(new PhabricatorProjectQuery())
      ->setViewer($viewer)
      ->withNames(array('secure-revision'))
      ->executeOne();

    if (!in_array($secure_group->getPHID(), $revision_projects)) {
      return null;
    }

    $warning = new PHUIInfoView();
    $warning->setTitle(pht('This is a secure revision.'));
    $warning->setSeverity(PHUIInfoView::SEVERITY_WARNING);
    $warning->appendChild(hsprintf(pht(
      'Please use Bugzilla CC list to manage access to this revision.')));
    return $warning->render();
  }
}
