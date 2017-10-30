<?php

final class BugzillaAccountSearchConduitAPIMethod
  extends UserConduitAPIMethod {

  public function getAPIMethodName() {
    return 'bugzilla.account.search';
  }

  public function getMethodDescription() {
    return pht('Retrieve Bugzilla data based on Bugzilla ID or Phabricator PHID.');
  }

  public function defineParamTypes() {
    return array('ids' => 'optional list<string>',
                 'phids' => 'optional list<string>');
  }

  protected function defineReturnType() {
    return 'nonempty dict<string, wild>';
  }

  protected function execute(ConduitAPIRequest $request) {
    $bugzilla_ids = $request->getValue('ids');
    $phab_phids   = $request->getValue('phids');

    if (!$bugzilla_ids && !$phab_phids) {
      return array();
    }

    $query = id(new PhabricatorExternalAccountQuery())
      ->setViewer($request->getUser());

    if ($bugzilla_ids) {
      $query->withAccountIDs($bugzilla_ids);
    }
    elseif ($phab_phids) {
      $query->withUserPHIDs($phab_phids);
    }

    $handles = $query->execute();

    $results = array();
    foreach ($handles as $user => $handle) {
      $results[] = array(
        'id'   => $handle->getAccountID(), // The Bugzilla ID
        'phid' => $handle->getUserPHID()   // The Phabricator User PHID
      );
    }

    return $results;
  }
}
