<?php

final class ExternalAccountsSearchEngineAttachment
  extends PhabricatorSearchEngineAttachment {

  public function getAttachmentName() {
    return pht('External Accounts');
  }

  public function getAttachmentDescription() {
    return pht('Get external account data about users.');
  }

  public function willLoadAttachmentData($query, $spec) {
    return true;
  }

  public function getAttachmentForObject($object, $data, $spec) {
    $handles = id(new PhabricatorExternalAccountQuery())
      ->setViewer($this->getViewer())
      ->withUserPHIDs(array($object->getPHID()))
      ->requireCapabilities(array(PhabricatorPolicyCapability::CAN_VIEW))
      ->execute();

    $results = array();
    foreach ($handles as $user => $handle) {
      $results[] = array(
        'id'   => $handle->getAccountID(),
        'type' => $handle->getAccountType()
      );
    }

    return array(
      'external-accounts' => $results,
    );
  }
}
