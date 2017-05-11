<?php
// This Source Code Form is subject to the terms of the Mozilla Public
// License, v. 2.0. If a copy of the MPL was not distributed with this
// file, You can obtain one at http://mozilla.org/MPL/2.0/.

/**
 * Extends Commit Message with a 'Bugzilla Bug ID' field.
 */
final class DifferentialBugzillaBugIDCommitMessageField
  extends DifferentialCommitMessageCustomField {

  // returned with
  // `DifferentialCommitMessageField::getCommitMessageFieldKey`
  // to provide error messages
  // and then in `DifferentialCommitMessageParser::setCommitMessageFields`
  // to prepare the fields and then save in
  // `DifferentialRevisionTransactionType`
  const FIELDKEY = 'bugzilla.bug-id';

  /* -- Commit Message Field descriptions ---------------------------- */

  public function getFieldName() {
    return pht('Bug');
  }

  public function getCustomFieldKey() {
    // Link to DifferentialBugzillaBugIDField.
    return 'differential:bugzilla-bug-id';
  }

  // Should Label appear in Arcanist message
  public function isFieldEditable() {
    return true;
  }

  public function getFieldAliases() {
    // Possible alternative labels to be parsed for in CVS or Arcanist
    // commit message.
    return array(
      'Bugzilla Bug ID',
      'Bugzilla',
    );
  }

  /* -- Parsing commits --------------------------------------------- */

  public function validateFieldValue($value) {
    if (!strlen($value) or !$value) {
      $this->raiseValidationException(
        pht('You need to provide a Bugzilla Bug ID'));
    }
    if (!ctype_digit($value)) {
      $this->raiseValidationException(
        pht('Bugzilla Bug ID should consist of all digits'));
    }
    // TODO validate if a public bug with given id exists in Bugzilla.
    return $value;
  }
}
