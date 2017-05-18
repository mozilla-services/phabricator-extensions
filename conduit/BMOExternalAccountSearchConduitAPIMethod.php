<?php

final class BMOExternalAccountSearchConduitAPIMethod
  extends UserConduitAPIMethod {

  public function getAPIMethodName() {
    return 'bmoexternalaccount.search';
  }

  public function getMethodDescription() {
    return pht('Retrieve external user PHID data based on BMO ID.');
  }

  public function defineParamTypes() {
    return array('accountids' => 'required list<string>');
  }

  protected function defineReturnType() {
    return 'nonempty dict<string, wild>';
  }

  protected function execute(ConduitAPIRequest $request) {
    $account_ids = $request->getValue('accountids');

    $handles = id(new PhabricatorExternalAccountQuery())
      ->setViewer($request->getUser())
      ->withAccountIDs($account_ids)
      ->execute();

    $result = array();
    foreach ($handles as $user => $handle) {
      $result[] = array(
        'id' => $handle->getAccountID(),  // The BMO ID
        'phid' => $handle->getUserPHID()  // The Phabricator User PHID
      );
    }

    return $result;
  }
}
