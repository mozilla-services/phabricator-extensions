<?php

  class DoorkeeperRevisionFeedWorker extends DoorkeeperFeedWorker {

    // When a reviewer rejects (r-) a revision
    const REVISION_REJECTED = 'differential.revision.reject'; // true|false
    // When a reviewer accepts (r+) a revision
    const REVISION_ACCEPTED = 'differential.revision.accept'; // true|false
    // When the list of reviewers changes, with their statuses
    const REVISION_REVIEWERS_CHANGED = 'differential.revision.reviewers'; // ex: {"PHID-USER-wtlpjohxmdtz4blbgg5p":"rejected","PHID-USER-w5tmcvhqel3on45s3x2u":"added"}
    // When a reviewer declines to review it
    const REVISION_REVIEWER_RESIGNED = 'differential.revision.resign'; // true|false
    // When the user abandons their revision
    const REVISION_ABANDONED = 'differential.revision.abandon'; // true|false
    // When the user selects "plan changes"
    const REVISION_PLAN_CHANGES = 'differential.revision.plan';
    // When the user selects "request review"
    const REVISION_REVIEW_REQUEST = 'differential.revision.request';

    public function isEnabled() {
      return true;
    }

    // This will be the main worker that publishes information to BMO
    public function publishFeedStory() {

      // These are the transaction types we care to handle
      $handled_differential_transaction_types = array(
        self::REVISION_REJECTED,
        self::REVISION_ACCEPTED,
        self::REVISION_REVIEWERS_CHANGED,
        self::REVISION_REVIEWER_RESIGNED,
        self::REVISION_ABANDONED,
        self::REVISION_PLAN_CHANGES,
        self::REVISION_REVIEW_REQUEST
      );

      $this->log('------ DoorkeeperRevisionFeedWorker ------');

      $story = $this->getFeedStory(); // PhabricatorApplicationTransactionFeedStory

      $this->log($story->renderText().'('.$story->getURI().')');

      // We only care about differential transactions here,
      // so bail if something else makes it in
      $primary_transaction = $story->getPrimaryTransaction();
      $primary_transaction_class = get_class($primary_transaction);
      if($primary_transaction_class != 'DifferentialTransaction') {
        $this->log(pht('Expected story type DifferentialTransaction, received %s', $primary_transaction_class));
        return;
      }

      // ex: "differential.revision.accept"
      $transaction_type = $primary_transaction->getTransactionType();

      $this->log(pht('Transaction type: %s', $transaction_type));
      $this->log(pht('Old value: %s / New value: %s',
        $primary_transaction->getOldValue(),
        $primary_transaction->getNewValue()
      ));

      // Bail if we don't care about this change
      if(!in_array($transaction_type, $handled_differential_transaction_types)) {
        $this->log(pht('Undesired transaction type: %s', $transaction_type));
        return;
      }

      // TODO:
      // To save a few requests: if it's REJECTED and its previous state wasn't ACCEPTED,
      // We can just cut out as nothing important to us happened
      // ....how?

      // Get the DifferentialRevision object
      $revision = $this->getStoryObject();

      // What to do, what to do
      switch($transaction_type) {
        // https://trello.com/c/2NXd7Caq/285-8-approving-a-revision-creates-an-r-flag-on-the-revision-attachment-in-the-associated-bug
        case self::REVISION_ACCEPTED:
        // https://trello.com/c/YDugKrFj/405-3-triggering-the-request-changes-or-resign-as-reviewer-actions-clears-that-users-r-flags-if-any-from-the-associated-bug-stub-att
        case self::REVISION_REJECTED:
        case self::REVISION_REVIEWER_RESIGNED:
        // This happens when I go into my own revision's edit phase and add or drop people
        // This is important because a person who r+'d could be removed from the reviewers list
        // Thuse we'd want to remove them as an R+ on BMO
        case self::REVISION_REVIEWERS_CHANGED:
          $this->updateReviewStatuses($revision);
          break;

        // https://trello.com/c/s7sauVAO/286-3-trigger-the-plan-changes-or-request-review-actions-clears-all-r-flags-on-the-revision-attachment-in-the-associated-bug
        case self::REVISION_PLAN_CHANGES:
        case self::REVISION_REVIEW_REQUEST:
          $this->clearAllReviewStatuses($revision);
          break;

        // https://trello.com/c/0bbNOGlC/386-3-obsolete-stub-attachment-from-bmo-if-associated-revision-is-abandoned
        case self::REVISION_ABANDONED:
          $this->obsoleteAttachment($revision);
          break;

        default:
          break;
      }

      $this->log('------ /DoorkeeperRevisionFeedWorker ------');
    }

    private function updateReviewStatuses($revision) {
      $accepted_bmo_ids = array();

      // Grab all reviewers with an "accepted" status
      $reviewers = $revision->getReviewers();
      $accepted_phids = array();
      foreach($reviewers as $reviewer) {
        // NOTE:  There's an "STATUS_ACCEPTED_OLDER"...what does that represent?
        // https://github.com/phacility/phabricator/blob/48a74de0b64901538feb878e2f12e18e605ca76a/src/applications/differential/editor/DifferentialTransactionEditor.php#L215
        if($reviewer->getReviewerStatus() === DifferentialReviewerStatus::STATUS_ACCEPTED) {
          $accepted_phids[] = $reviewer->getReviewerPHID();
        }
      }

      // Use the External User Query to get their BMO IDS
      if(count($accepted_phids)) {
        $bmo_users = id(new PhabricatorExternalAccountQuery())
          ->setViewer(PhabricatorUser::getOmnipotentUser())
          ->withAccountTypes(array(PhabricatorBMOAuthProvider::ADAPTER_TYPE))
          ->withUserPHIDs($accepted_phids)
          ->execute();
        foreach($bmo_users as $user) {
          $accepted_bmo_ids[] = $user->getAccountID();
        }
      }

      $this->sendUpdateRequest($revision, $accepted_bmo_ids);
    }


    private function clearAllReviewStatuses($revision) {
      $this->sendUpdateRequest($revision, array());
    }

    private function obsoleteAttachment($revision) {
      $request_data = array(
        'revision_id' => $revision->getID(),
        'bug_id' => $this->get_bugzilla_bug_id($revision)
      );

      $future_uri = id(new PhutilURI(PhabricatorEnv::getEnvConfig('bugzilla.url')))
        ->setPath('/rest/phabbugz/obsolete');

      $future = $this->get_http_future($future_uri)
        ->setMethod('PUT')
        ->setData($request_data)
        ->setExpectStatus(array(200));

      $this->log(pht('Making a request to: %s', (string) $future_uri));
      $this->log('Using data: '.json_encode($request_data, JSON_FORCE_OBJECT));

      try {
        list($status) = $future->resolve();
        $status_code = $status->getStatusCode();
        if($status_code != 200) {
          $this->log(pht('obsoleteAttachment failure: BMO returned code %s', $status_code));
        }
      }
      catch(HTTPFutureResponseStatus $ex) {
        $status_code = $status->getStatusCode();
        $this->log(pht('obsoleteAttachment exception: %s %s', $status_code, $ex->getErrorCodeDescription($status_code)));
      }
    }

    private function sendUpdateRequest($revision, $accepted_bmo_ids) {
      // Ship the array to BMO
      $request_data = array(
        'accepted_users' => $accepted_bmo_ids,
        'revision_id' => $revision->getID(),
        'bug_id' => $this->get_bugzilla_bug_id($revision)
      );
      $future_uri = id(new PhutilURI(PhabricatorEnv::getEnvConfig('bugzilla.url')))
        ->setPath('/rest/phabbugz/update_reviewer_statuses');

      $future = $this->get_http_future($future_uri)
        ->setMethod('PUT')
        ->setData($request_data)
        ->setExpectStatus(array(200));

      $this->log(pht('Making a request to: %s', (string) $future_uri));
      $this->log('Using data:'.json_encode($request_data, JSON_FORCE_OBJECT));

        try {
          list($status) = $future->resolve();
          $status_code = $status->getStatusCode();
          if($status->getStatusCode() != 200) {
            $this->log(pht('sendUpdateRequest failure: BMO returned code %s', $status_code));
          }
        }
        catch(HTTPFutureResponseStatus $ex) {
          $status_code = $status->getStatusCode();
          $this->log(pht('obsoleteAttachment exception: %s %s', $status_code, $ex->getErrorCodeDescription($status_code)));
        }
    }

    private function get_http_future($uri) {
      return id(new HTTPSFuture((string) $uri))
        ->addHeader('X-Bugzilla-API-Key', PhabricatorEnv::getEnvConfig('bugzilla.automation_api_key'))
        ->addHeader('Accept', 'application/json')
        ->setTimeout(15);
    }

    private function get_bugzilla_bug_id($revision) {
      $field = PhabricatorCustomField::getObjectField(
        $revision,
        PhabricatorCustomField::ROLE_DEFAULT,
        DifferentialBugzillaBugIDCommitMessageField::CUSTOM_FIELD_KEY
      );

      id(new PhabricatorCustomFieldStorageQuery())
        ->addField($field)
        ->execute();

      $bug_id = $field->getValueForStorage();

      return $bug_id;
    }
  }
