<?php
// This Source Code Form is subject to the terms of the Mozilla Public
// License, v. 2.0. If a copy of the MPL was not distributed with this
// file, You can obtain one at http://mozilla.org/MPL/2.0/.

/**
 * Extends Differential with a 'Bugzilla Bug ID' field.
 */
final class DifferentialBugzillaBugIDField
  extends DifferentialStoredCustomField {

/* -(  Core Properties and Field Identity  )--------------------------------- */

  public function getFieldKey() {
    return 'differential:bugzilla-bug-id';
  }

  public function getFieldName() {
    return pht('Bugzilla Bug ID');
  }

  public function getFieldKeyForConduit() {
    // Link to DifferentialBugzillaBugIDCommitMessageField
    return 'bugzilla.bug-id';
  }

  public function getFieldDescription() {
    // Rendered in 'Config > Differential > differential.fields'
    return pht('Displays associated Bugzilla Bug ID.');
  }

  public function isFieldEnabled() {
    return true;
  }

  public function canDisableField() {
    // Field can't be switched off in configuration
    return false;
  }

/* -(  ApplicationTransactions  )-------------------------------------------- */

  public function shouldAppearInApplicationTransactions() {
    // Required to be editable
    return true;
  }

/* -(  Edit View  )---------------------------------------------------------- */

  public function shouldAppearInEditView() {
    // Should the field appear in Edit Revision feature
    // If set to false value will not be read from Arcanist commit message.
    // ERR-CONDUIT-CORE: Transaction with key "6" has invalid type
    // "bugzilla.bug-id". This type is not recognized. Valid types are: update,
    // [...]
    return true;
  }

  public function readValueFromRequest(AphrontRequest $request) {
    $this->setValue($request->getStr($this->getFieldKey()));
  }

  public function renderEditControl(array $handles) {
    return id(new AphrontFormTextControl())
      ->setLabel($this->getFieldName())
      ->setCaption(
        pht('Example: %s', phutil_tag('tt', array(), '2345')))
      ->setName($this->getFieldKey())
      ->setValue($this->getValue(), '');
  }

  public function validateApplicationTransactions(
    PhabricatorApplicationTransactionEditor $editor,
    $type, array $xactions) {

    $errors = parent::validateApplicationTransactions($editor, $type, $xactions);

    foreach ($xactions as $xaction) {
      // Get the transactor's ExternalAccount->accountID using the author's phid
      $xaction_author_phid = $xaction->getAuthorPHID();
      // Validate that the user may see the bug they've submitted a revision for
      $bug_id = DifferentialBugzillaBugIDValidator::formatBugID(
        $xaction->getNewValue()
      );
      $validation_errors = DifferentialBugzillaBugIDValidator::validate(
        $bug_id,
        $xaction_author_phid
      );
      // Push errors into the revision save stack
      foreach($validation_errors as $validation_error) {
        $errors[] = new PhabricatorApplicationTransactionValidationError(
          $type,
          '',
          pht($validation_error)
        );
      }
    }

    return $errors;
  }

/* -(  Property View  )------------------------------------------------------ */

  public function shouldAppearInPropertyView() {
    // Should bug id be visible in Differential UI.
    return true;
  }

  public function renderPropertyViewValue(array $handles) {
    $bug_id = $this->getValue();

    if($bug_id) {
      $bug_uri = (string) id(new PhutilURI(PhabricatorEnv::getEnvConfig('bugzilla.url')))
        ->setPath($this->getValue());

      return phutil_tag('a', array('href' => $bug_uri), $this->getValue());
    }

    return 'Not provided';
  }

/* -(  List View  )---------------------------------------------------------- */

  // Switched of as renderOnListItem is undefined
  // public function shouldAppearInListView() {
  //   return true;
  // }

  // TODO Find out if/how to implement renderOnListItem
  // It throws Incomplete if not overriden, but doesn't appear anywhere else
  // except of it's definition in `PhabricatorCustomField`

/* -(  Global Search  )------------------------------------------------------ */

  public function shouldAppearInGlobalSearch() {
    return true;
  }

/* -(  Conduit  )------------------------------------------------------------ */

  public function shouldAppearInConduitDictionary() {
    // Should the field appear in `differential.revision.search`
    return true;
  }

  public function shouldAppearInConduitTransactions() {
    // Required if needs to be saved via Conduit (i.e. from `arc diff`)
    return true;
  }

  protected function newConduitSearchParameterType() {
    return new ConduitStringParameterType();
  }

  protected function newConduitEditParameterType() {
    // Define the type of the parameter for Conduit
    return new ConduitStringParameterType();
  }

  public function readFieldValueFromConduit(string $value) {
    return $value;
  }

  public function isFieldEditable() {
    // Has to be editable to be written from `arc diff`
    return true;
  }

  public function shouldDisableByDefault() {
    return false;
  }

  public function shouldOverwriteWhenCommitMessageIsEdited() {
    return true;
  }
}
