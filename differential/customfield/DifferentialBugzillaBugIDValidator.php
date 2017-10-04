<?php

class DifferentialBugzillaBugIDValidator extends Phobject {

  public static function formatBugID($id) {
    return trim(str_replace('#', '', $id));
  }

  public static function validate($bug_id, $account_phid) {
    // This function returns an array of strings representing errors
    // If this function returns an empty array, all data is valid
    $errors = array();

    $bug_id = self::formatBugID($bug_id);

    // Check for bug ID which may or may not be required at a given time
    if(!strlen($bug_id)) {
      if(PhabricatorEnv::getEnvConfig('bugzilla.require_bugs') === true) {
        $errors[] = pht('Bugzilla Bug ID is required');
      }
      return $errors;
    }

    // Isn't a number we can work with
    if (!ctype_digit($bug_id) || $bug_id === "0") {
      $errors[] = pht('Bugzilla Bug ID must be a valid bug number');
      return $errors;
    }

    // Make a request to BMO to ensure the bug exists and user can see it

    // Check to see if the user is an admin; if so, don't validate bug existence
    // because the admin account may not have a BMO account ID associated with it
    $users = id(new PhabricatorPeopleQuery())
      ->setViewer(PhabricatorUser::getOmnipotentUser())
      ->withPHIDs(array($account_phid))
      ->withIsAdmin(true)
      ->execute();
    if(count($users)) {
      return $errors;
    }

    // Get the transactor's ExternalAccount->accountID using the author's phid
    $users = id(new PhabricatorExternalAccountQuery())
      ->setViewer(PhabricatorUser::getOmnipotentUser())
      ->withAccountTypes(array(PhabricatorBMOAuthProvider::ADAPTER_TYPE))
      ->withUserPHIDs(array($account_phid))
      ->execute();

    // The only way this should happen is if the user creating/editing the
    // revision isn't tied to a BMO account id (i.e. traditional Phab registration)
    if(!count($users)) {
      $errors[] = pht('This transaction\'s user\'s account ID could not be found.');
      return $errors;
    }
    $user_detail = reset($users);
    $user_bmo_id = $user_detail->getAccountID();

    $future_uri = id(new PhutilURI(PhabricatorEnv::getEnvConfig('bugzilla.url')))
      ->setPath('/rest/phabbugz/check_bug/'.$bug_id.'/'.$user_bmo_id);

    // http://bugzilla.readthedocs.io/en/latest/api/core/v1/bug.html#get-bug
    // 100 (Invalid Bug Alias) If you specified an alias and there is no bug with that alias.
    // 101 (Invalid Bug ID) The bug_id you specified doesn't exist in the database.
    // 102 (Access Denied) You do not have access to the bug_id you specified.
    $accepted_status_codes = array(100, 101, 102, 200, 404, 500);

    $future = id(new HTTPSFuture((string) $future_uri))
      ->setMethod('GET')
      ->addHeader('X-Bugzilla-API-Key', PhabricatorEnv::getEnvConfig('bugzilla.automation_api_key'))
      ->addHeader('Accept', 'application/json')
      ->setExpectStatus($accepted_status_codes)
      ->setTimeout(PhabricatorEnv::getEnvConfig('bugzilla.timeout'));

    // Resolve the async HTTPSFuture request and extract JSON body
    try {
      list($status, $body) = $future->resolve();
      $status_code = (int) $status->getStatusCode();

      if(in_array($status_code, array(100, 101, 404))) {
        $errors[] = pht('Bugzilla Bug ID: %s does not exist (BMO %s)', $bug_id, $status_code);
      }
      else if($status_code === 102) {
        $errors[] = pht('Bugzilla Bug ID: You do not have permission for this bug.');
      }
      else if($status_code === 500) {
        $errors[] = pht('Bugzilla Bug ID: Bugzilla responded with a 500 error.');
      }
      else if(!in_array($status_code, $accepted_status_codes)) {
        $errors[] = pht('Bugzilla Bug ID:  Bugzilla did not provide an expected response (BMO %s).', $status_code);
      }
      else {
        $json = phutil_json_decode($body);

        if($json['result'] != '1') {
          $errors[] = pht('Bugzilla Bug ID:  You do not have permission to view this bug or the bug does not exist.');
        }

        // At this point we should be good!  Valid response code and result: 1
      }
    } catch (HTTPFutureResponseStatus $ex) {
      $errors[] = pht(
        'Bugzilla returned an unexpected status code or response body:'.
        'Status code: %s / '.
        'Body: %s',
        $status_code, $body);
    }

    return $errors;
  }
}
