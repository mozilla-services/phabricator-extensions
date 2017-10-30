<?php

  class DoorkeeperRevisionFeedWorker extends DoorkeeperFeedWorker {

    // When a user creates a new revision
    const REVISION_CREATED = 'core:create'; // true|false
    // When a custom field has changed such as the bug id
    const CUSTOM_FIELD_CHANGED = 'core:customfield'; // true/false
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

    // Logging error key type
    const LOGGING_TYPE = 'DoorkeeperRevisionFeedWorkerEvent';

    // Used for logging
    private $story_phid = '';
    private $revision_id = '';
    private $author_phid = '';

    // Used for linking back from BMO
    private $transaction_id = '';

    public function isEnabled() {
      return PhabricatorEnv::getEnvConfig('bugzilla.automation_api_key');
    }

    // This will be the main worker that publishes information to BMO
    public function publishFeedStory() {
      // These are the transaction types we care to handle
      $handled_differential_transaction_types = array(
        self::REVISION_CREATED,
        self::CUSTOM_FIELD_CHANGED,
        self::REVISION_REJECTED,
        self::REVISION_ACCEPTED,
        self::REVISION_REVIEWERS_CHANGED,
        self::REVISION_REVIEWER_RESIGNED,
        self::REVISION_ABANDONED,
        self::REVISION_RECLAIMED,
        self::REVISION_PLAN_CHANGES,
        self::REVISION_REVIEW_REQUEST
      );

      $story = $this->getFeedStory(); // PhabricatorApplicationTransactionFeedStory

      // Track the story through multiple log calls using its PHID
      $primary_transaction = $story->getPrimaryTransaction();
      $this->story_phid = $primary_transaction->getPHID();
      $this->transaction_id = $primary_transaction->getID();
      $this->author_phid = $primary_transaction->getAuthorPHID();

      $this->mozlog(
        pht('Story received: %s (%s)', $story->renderText(), $story->getURI())
      );

      // We only care about differential transactions here,
      // so bail if something else makes it in
      $primary_transaction_class = get_class($primary_transaction);
      if($primary_transaction_class != 'DifferentialTransaction') {
        $this->mozlog(
          pht(
            'Expected story type DifferentialTransaction, received %s.'.
            'Taking no sync action.',
            $story->renderText()
          )
        );
        return;
      }

      // ex: "differential.revision.accept"
      $transaction_type = $primary_transaction->getTransactionType();

      // Bail if we don't care about this change
      if(!in_array($transaction_type, $handled_differential_transaction_types)) {
        $this->mozlog(
          pht('Undesired transaction type: %s. Taking no sync action.', $transaction_type)
        );
        return;
      }

      // TODO:
      // To save a few requests: if it's REJECTED and its previous state wasn't ACCEPTED,
      // We can just cut out as nothing important to us happened
      // ....how?

      // Get the DifferentialRevision object
      $revision = $this->getStoryObject();
      $this->revision_id = $revision->getID();

      // Abandon all syncing if the changed revision has no bug number and
      // this story did not change the bug number
      $bug_id = $this->getBugzillaBugID($revision);
      if(
        !$bug_id &&
        !$this->isBugNumberChange($primary_transaction, $transaction_type) &&
        $transaction_type !== self::REVISION_CREATED
      ) {
        $this->mozlog(pht('Revision has no associated bug ID. Taking no sync action.'));
        return;
      }

      // Keep track of values changed
      $old_value = $primary_transaction->getOldValue();
      $new_value = $primary_transaction->getNewValue();

      // What to do, what to do
      switch($transaction_type) {
        // User created a new revision
        case self::REVISION_CREATED:
          if($bug_id) {
            $this->updateRevisionSecurity($revision);
          }
          else {
            $this->mozlog('Public revision created with no bug number; making revision public.');
            $this->makeRevisionPublic($revision);
          }
          break;

        case self::CUSTOM_FIELD_CHANGED:
          if($this->isBugNumberChange($primary_transaction, $transaction_type)) {
            // Bug number has been removed
            if($old_value != null && $new_value == null) {
              $this->mozlog(
                pht(
                  'Bug number changed from %s to %s.  '.
                  'Making revision public and obsoleting BMO attachment.',
                  $old_value,
                  $new_value
                )
              );
              $this->makeRevisionPublic($revision);
              $this->obsoleteAttachment($revision, true, $old_value);
            }
            // A bug number has been added
            else if($new_value) {
              $this->updateRevisionSecurity($revision);
            }
          }
	        break;

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
    }

    private function updateRevisionSecurity($revision) {
      if (!$this->getBugzillaBugID($revision)) {
        $this->mozlog(pht('updateRevisionSecurity aborted: No Bugzilla ID attached.'));
        return;
      }

      $future_uri = id(new PhutilURI(PhabricatorEnv::getEnvConfig('bugzilla.url')))
        ->setPath('/rest/phabbugz/revision/' . $revision->getID());

      $future = $this->getHTTPSFuture($future_uri)
        ->setMethod('POST');

      $this->mozlog(
        pht('updateRevisionSecurity: Making request to %s', (string) $future_uri)
      );

      try {
        list($status, $body) = $future->resolve();
        $status_code = $status->getStatusCode();
        if($status_code != 200) {
          $exception_message = pht('updateRevisionSecurity failure: BMO returned code: %s | message: %s',
                                   $status_code, $this->getBMOErrorMessage($body));
          $this->mozlog($exception_message);
          throw new Exception($exception_message);
        }
      }
      catch(HTTPFutureResponseStatus $ex) {
        $status_code = $status->getStatusCode();
        $exception_message = pht('updateRevisionSecurity exception: %s %s', $status_code, $ex->getErrorCodeDescription($status_code));
        $this->mozlog($exception_message);

        // Re-queue
        throw new Exception($exception_message);
      }
    }

    private function updateReviewStatuses($revision) {
      // Grab all reviewers with an "accepted" or "rejected" status
      $reviewers = $revision->getReviewers();
      $accepted_phids = array();
      $denied_phids = array();
      foreach($reviewers as $reviewer) {
        // NOTE:  There's an "STATUS_ACCEPTED_OLDER"
        // This fires when a revision is R+d by the reviewer, then the
        // revision author "plans changes" but hasn't yet updated the patch
        // https://github.com/phacility/phabricator/blob/48a74de0b64901538feb878e2f12e18e605ca76a/src/applications/differential/editor/DifferentialTransactionEditor.php#L215
        $reviewer_status = $reviewer->getReviewerStatus();
        if(
          $reviewer_status === DifferentialReviewerStatus::STATUS_ACCEPTED ||
          $reviewer_status === DifferentialReviewerStatus::STATUS_ACCEPTED_OLDER
          ) {
            $accepted_phids[] = $reviewer->getReviewerPHID();
        } elseif(
          $reviewer_status === DifferentialReviewerStatus::STATUS_REJECTED
          ) {
            $denied_phids[] = $reviewer->getReviewerPHID();
        }
      }

      // Use the External User Query to get their BMO IDS
      $accepted_bmo_ids = array();
      $denied_bmo_ids = array();
      if(count($accepted_phids)) {
        $accepted_bmo_ids = $this->getBMOIDsByPHID($accepted_phids);
      }
      if(count($denied_phids)) {
        $denied_bmo_ids = $this->getBMOIDsByPHID($denied_phids);
      }

      $this->sendUpdateRequest($revision, $accepted_bmo_ids, $denied_bmo_ids);
    }


    private function clearAllReviewStatuses($revision) {
      $this->sendUpdateRequest($revision);
    }

    private function obsoleteAttachment($revision, $make_obsolete, $bug_id=0) {
      if(!$bug_id) {
        $bug_id = $this->getBugzillaBugID($revision);
      }

      $request_data = array(
        'revision_id' => $revision->getID(),
        'bug_id' => $bug_id,
        'make_obsolete' => $make_obsolete,
        'transaction_id' => $this->transaction_id
      );

      $future_uri = id(new PhutilURI(PhabricatorEnv::getEnvConfig('bugzilla.url')))
        ->setPath('/rest/phabbugz/obsolete');

      $future = $this->getHTTPSFuture($future_uri)
        ->setData($request_data);

      $this->mozlog(
        pht(
          'obsoleteAttachment: Making request to %s with data: %s',
          (string) $future_uri,
          json_encode($request_data)
        )
      );

      try {
        list($status, $body) = $future->resolve();
        $status_code = $status->getStatusCode();
        if($status_code != 200) {
          $exception_message = pht('obsoleteAttachment failure: BMO returned code: %s | message: %s',
                                   $status_code, $this->getBMOErrorMessage($body));
          $this->mozlog($exception_message);

          // Re-queue
          throw new Exception($exception_message);
        }
      }
      catch(HTTPFutureResponseStatus $ex) {
        $status_code = $status->getStatusCode();
        $exception_message = pht('obsoleteAttachment exception: %s %s', $status_code, $ex->getErrorCodeDescription($status_code));
        $this->mozlog($exception_message);

        // Re-queue
        throw new Exception($exception_message);
      }
    }

    private function sendUpdateRequest($revision, $accepted_bmo_ids = array(), $denied_bmo_ids = array()) {
      // Ship the array to BMO
      $request_data = array(
        'accepted_users' => implode(':', $accepted_bmo_ids),
        'denied_users' => implode(':', $denied_bmo_ids),
        'revision_id' => $revision->getID(),
        'bug_id' => $this->getBugzillaBugID($revision),
        'transaction_id' => $this->transaction_id
      );
      $future_uri = id(new PhutilURI(PhabricatorEnv::getEnvConfig('bugzilla.url')))
        ->setPath('/rest/phabbugz/update_reviewer_statuses');

      $future = $this->getHTTPSFuture($future_uri)
        ->setData($request_data);

      $this->mozlog(
        pht(
          'Making request to %s with data: %s',
          (string) $future_uri,
          json_encode($request_data)
        )
      );

      try {
        list($status, $body) = $future->resolve();
        $status_code = $status->getStatusCode();
        if($status->getStatusCode() != 200) {
          $exception_message = pht('sendUpdateRequest failure: BMO returned code: %s | message: %s',
                                   $status_code, $this->getBMOErrorMessage($body));
          $this->mozlog($exception_message);

          // Re-queue
          throw new Exception($exception_message);
        }
      }
      catch(HTTPFutureResponseStatus $ex) {
        $status_code = $status->getStatusCode();
        $exception_message = pht('sendUpdateRequest exception: %s %s', $status_code, $ex->getErrorCodeDescription($status_code));
        $this->mozlog($exception_message);

        // Re-queue
        throw new Exception($exception_message);
      }
    }

    private function getHTTPSFuture($uri) {
      return id(new HTTPSFuture((string) $uri))
        ->addHeader('X-Bugzilla-API-Key', PhabricatorEnv::getEnvConfig('bugzilla.automation_api_key'))
        ->setMethod('PUT')
        ->addHeader('Accept', 'application/json')
        ->setExpectStatus(array(200))
        ->setTimeout(PhabricatorEnv::getEnvConfig('bugzilla.timeout'));
    }

    private function getBugzillaBugID($revision) {
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

    private function getBMOIDsByPHID($user_phids) {
      $users = id(new PhabricatorExternalAccountQuery())
          ->setViewer(PhabricatorUser::getOmnipotentUser())
          ->withAccountTypes(array(PhabricatorBMOAuthProvider::ADAPTER_TYPE))
          ->withUserPHIDs($user_phids)
          ->execute();

      $bmo_ids = array();
      foreach($users as $user) {
        $bmo_ids[] = $user->getAccountID();
      }

      return $bmo_ids;
    }

    private function mozlog($message) {
      MozLogger::log(
        $message,
        self::LOGGING_TYPE,
        array('Fields' => array(
          'story' => $this->story_phid,
          'revision_id' => $this->revision_id,
          'author_phid' => $this->author_phid
        ))
      );
    }

    // Sets a revision to public in Phabricator then
    // obsoletes any existing attachment on BMO
    private function makeRevisionPublic($revision) {
      $changed = false;
      if($revision->getViewPolicy() != 'public') {
        $changed = true;
        $revision->setViewPolicy('public');
      }
      if($revision->getEditPolicy() != 'users') {
        $changed = true;
        $revision->setEditPolicy('users');
      }
      if($changed) {
        $revision->save();
      }
    }

    private function isBugNumberChange($transaction, $type) {
      return $type === self::CUSTOM_FIELD_CHANGED &&
             $transaction->getMetadataValue('customfield:key') === 'differential:bugzilla-bug-id';
    }

    // Per PhabricatorWorker source: "Return `null` to retry indefinitely."
    public function getMaximumRetryCount() {
      return null;
    }

    // Per PhabricatorWorker source: "Return `null` to retry every 60 seconds."
    public function getWaitBeforeRetry(PhabricatorWorkerTask $task) {
      $count = $task->getFailureCount();
      return min(pow(2, $count), 5 * 60); // Maximum 5 minutes
    }

    // Text output which will be shown in the /daemon/ task screen
    public function renderForDisplay(PhabricatorUser $viewer) {
      // We'll build up a message based on all information we currently have:
      $text = '';

      try {
        $story = $this->getFeedStory();
        return phutil_tag(
          'a',
          array('target' => '_blank', 'href' => $story->getURI()),
          $story->renderText()
        );
      } catch (Exception $ex) {
        return phutil_tag('span', array(), 'Error calculating story information');
      }
    }

    protected function getBMOErrorMessage($text = '{}') {
      try {
        $json = phutil_json_decode($text);
        return ($json !== null && isset($json['message'])) ? $json['message'] : 'None';
      }
      catch(Exception $e) {
        return pht('Caught exception: ' . $e->getMessage());
      }
    }
  }
