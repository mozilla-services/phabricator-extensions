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
    // When the user reclaims a revision they had abandoned
    const REVISION_RECLAIMED = 'differential.revision.reclaim'; // true|false
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
        self::REVISION_RECLAIMED,
        self::REVISION_PLAN_CHANGES,
        self::REVISION_REVIEW_REQUEST
      );

      echo('------ DoorkeeperRevisionFeedWorker ------');

      $story = $this->getFeedStory(); // PhabricatorApplicationTransactionFeedStory

      echo($story->renderText().'('.$story->getURI().')');

      // We only care about differential transactions here,
      // so bail if something else makes it in
      $primary_transaction = $story->getPrimaryTransaction();
      $primary_transaction_class = get_class($primary_transaction);
      if($primary_transaction_class != 'DifferentialTransaction') {
        echo(pht('Expected story type DifferentialTransaction, received %s', $primary_transaction_class));
        return;
      }

      // ex: "differential.revision.accept"
      $transaction_type = $primary_transaction->getTransactionType();

      echo(pht('Transaction type: %s', $transaction_type));
      echo(pht('Old value: %s / New value: %s',
        $primary_transaction->getOldValue(),
        $primary_transaction->getNewValue()
      ));

      // Bail if we don't care about this change
      if(!in_array($transaction_type, $handled_differential_transaction_types)) {
        echo(pht('Undesired transaction type: %s', $transaction_type));
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
          $this->obsoleteAttachment($revision, true);
          break;

        // https://trello.com/c/yIibWslA/508-x-when-a-revision-is-abandoned-bmo-obsoletes-the-attachment-if-the-revision-is-reclaimed-bmo-should-either-unobsolete-the-attach
        case self::REVISION_RECLAIMED:
          $this->obsoleteAttachment($revision, false);
          break;

        default:
          break;
      }

      echo('------ /DoorkeeperRevisionFeedWorker ------');
    }

    private function updateReviewStatuses($revision) {
      $accepted_bmo_ids = array();

      // Grab all reviewers with an "accepted" status
      $reviewers = $revision->getReviewers();
      $accepted_phids = array();
      foreach($reviewers as $reviewer) {
        // NOTE:  There's an "STATUS_ACCEPTED_OLDER"
        // This fires when a revision is R+d by the reviewer, then the
        // revision author updates their patch
        // https://github.com/phacility/phabricator/blob/48a74de0b64901538feb878e2f12e18e605ca76a/src/applications/differential/editor/DifferentialTransactionEditor.php#L215
        $reviewer_status = $reviewer->getReviewerStatus();
        if(
          $reviewer_status === DifferentialReviewerStatus::STATUS_ACCEPTED ||
          $reviewer_status === DifferentialReviewerStatus::STATUS_ACCEPTED_OLDER
          ) {
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

    private function obsoleteAttachment($revision, $make_obsolete) {
      $request_data = array(
        'revision_id' => $revision->getID(),
        'bug_id' => $this->get_bugzilla_bug_id($revision),
        'make_obsolete' => $make_obsolete
      );

      $future_uri = id(new PhutilURI(PhabricatorEnv::getEnvConfig('bugzilla.url')))
        ->setPath('/rest/phabbugz/obsolete');

      $future = $this->get_http_future($future_uri)
        ->setData($request_data);

      echo(pht('Making a request to: %s', (string) $future_uri));
      echo('Using data: '.json_encode($request_data));

      try {
        list($status) = $future->resolve();
        $status_code = $status->getStatusCode();
        if($status_code != 200) {
          echo(pht('obsoleteAttachment failure: BMO returned code %s', $status_code));
        }
      }
      catch(HTTPFutureResponseStatus $ex) {
        $status_code = $status->getStatusCode();
        $exception_message = pht('obsoleteAttachment exception: %s %s', $status_code, $ex->getErrorCodeDescription($status_code));
        echo($exception_message);

        // Re-queue
        throw new Exception($exception_message);
      }
    }

    private function sendUpdateRequest($revision, $accepted_bmo_ids) {
      // Ship the array to BMO
      $request_data = array(
        'accepted_users' => implode(':', $accepted_bmo_ids),
        'revision_id' => $revision->getID(),
        'bug_id' => $this->get_bugzilla_bug_id($revision)
      );
      $future_uri = id(new PhutilURI(PhabricatorEnv::getEnvConfig('bugzilla.url')))
        ->setPath('/rest/phabbugz/update_reviewer_statuses');

      $future = $this->get_http_future($future_uri)
        ->setData($request_data);

      echo(pht('Making a request to: %s', (string) $future_uri));
      echo('Using data:'.json_encode($request_data, JSON_FORCE_OBJECT));

        try {
          list($status) = $future->resolve();
          $status_code = $status->getStatusCode();
          if($status->getStatusCode() != 200) {
            echo(pht('sendUpdateRequest failure: BMO returned code %s', $status_code));
          }
        }
        catch(HTTPFutureResponseStatus $ex) {
          $status_code = $status->getStatusCode();
          $exception_message = pht('sendUpdateRequest exception: %s %s', $status_code, $ex->getErrorCodeDescription($status_code));
          echo($exception_message);

          // Re-queue
          throw new Exception($exception_message);
        }
    }

    private function get_http_future($uri) {
      return id(new HTTPSFuture((string) $uri))
        ->addHeader('X-Bugzilla-API-Key', PhabricatorEnv::getEnvConfig('bugzilla.automation_api_key'))
        ->setMethod('PUT')
        ->addHeader('Accept', 'application/json')
        ->setExpectStatus(array(200))
        ->setTimeout(PhabricatorEnv::getEnvConfig('bugzilla.timeout'));
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
