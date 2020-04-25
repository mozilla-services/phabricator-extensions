<?php


class EventKind {
  public static $ABANDON = 'revision-abandoned';
  public static $METADATA_EDIT = 'revision-metadata-edited';
  public static $RECLAIM = 'revision-reclaimed';
  public static $ACCEPT = 'revision-accepted';
  public static $COMMENT = 'revision-commented';
  public static $UPDATE = 'revision-updated';
  public static $REJECT = 'revision-requested-changes';
  public static $REQUEST_REVIEW = 'revision-requested-review';
  public static $CLOSE = 'revision-landed';
  public static $CREATE = 'revision-created';
  public static $PINGED = 'revision-comment-pinged';

  /** @var string */
  public $publicKind;
  /** @var string */
  private $mainTransactionType;

  /**
   * @param string $publicKind public identifier value in "kind" property of {@link EmailEvent}
   * @param string $phabricatorType internal type for main transaction
   */
  public function __construct(string $publicKind, ?string $phabricatorType) {
    $this->publicKind = $publicKind;
    $this->mainTransactionType = $phabricatorType;
  }

  /**
   * For all events, we need to find the "main transaction" so we can get the revision and author of the event.
   * However, not all events are equal - for some, there's a particular transaction that is the most important.
   * For example, the "differential.revision.abandon" transaction is most important for "abandon" events.
   * However, others (such as the "metadata edited" or "comment" events) don't have a single transaction type
   * that's guaranteed to exist, so we instead consider each transaction equally important and grab any one of them.
   *
   * @param TransactionList $transactionList
   */
  public function findMainTransaction(TransactionList $transactionList) {
    if ($this->mainTransactionType) {
      return $transactionList->getTransactionWithType($this->mainTransactionType);
    } else {
      return $transactionList->getAnyTransaction();
    }
  }


  public function findActor(TransactionList $transactions, DifferentialRevision $revision) {
    if ($this->publicKind != self::$CREATE) {
      // Most of the time, the transaction actor is the actor of the event.
      return $this->findMainTransaction($transactions)->getAuthorPHID();
    } else {
      // However, for "revision-created", the "main transaction" here (phab-bot setting visibility) isn't actually the
      // real event we want to email about (<user> created the revision).
      return $revision->getAuthorPHID();
    }
  }


  public static function mainKind(array $transactions, PhabricatorUserStore $userStore) {
    // Identifying a "revision created" event is ... tricky. We can't just look for "core:create", because
    // that event happens before we identify if a revision is secure or not. So, instead, we try to detect when
    // the admin bot does its "security detection" work. When we find events that match this heuristic, we assume
    // that a revision was created.
    // The heuristic is:
    // * author of transaction is "phab-bot"
    // * there's four transactions in the story: "core:view-policy", "core:edit-policy",
    //   "differential.revision.request" and "core:edge"
    $revisionCreatedHeuristic = new RevisionCreatedHeuristic();

    // If any other kind matches, then that's the primary event kind.
    // Otherwise, if the only relevant transaction kind is the comment, then the commenting is the only relevant event.
    $includesComment = false;

    foreach ($transactions as $transaction) {
      $type = $transaction->getTransactionType();
      if ($type == 'differential.revision.abandon') {
        return new EventKind(self::$ABANDON, 'differential.revision.abandon');
      } else if ($type == 'differential.revision.reclaim') {
        return new EventKind(self::$RECLAIM, 'differential.revision.reclaim');
      } else if ($type == 'differential.revision.accept') {
        return new EventKind(self::$ACCEPT, 'differential.revision.accept');
      } else if ($type == 'differential:update') {
        return new EventKind(self::$UPDATE, 'differential:update');
      } else if ($type == 'differential.revision.reject') {
        return new EventKind(self::$REJECT, 'differential.revision.reject');
      } else if ($type == 'differential.revision.request') {
        $rawActor = $userStore->find($transaction->getAuthorPHID());
        if ($rawActor->getUserName() == 'phab-bot') {
          // It was a bot requesting a review, probably part of the "revision created" workflow
          $revisionCreatedHeuristic->authorIsPhabBot();
          $revisionCreatedHeuristic->includesRevisionRequestChange();
        } else {
          // it was not phab-bot requesting a review, so this is probably a real review request
          return new EventKind(self::$REQUEST_REVIEW, 'differential.revision.request');
        }
      } else if ($type == 'differential.revision.close') {
        return new EventKind(self::$CLOSE, 'differential.revision.close');
      } else if ($type == 'core:view-policy') {
        $revisionCreatedHeuristic->includesViewPolicyChange();
      } else if ($type == 'core:edit-policy') {
        $revisionCreatedHeuristic->includesEditPolicyChange();
      } else if ($type == 'core:edge') {
        $revisionCreatedHeuristic->includesCoreEdgeChange();
      } else if (in_array($type, ['core:comment', 'differential:inline'])) {
        $includesComment = true;
        continue;
      } else if (in_array($type, ['core:customField', 'differential.revision.title', 'differential.revision.reviewers'])) {
        return new EventKind(self::$METADATA_EDIT, null);
      }
    }

    if ($revisionCreatedHeuristic->check()) {
      return new EventKind(self::$CREATE, null);
    }
    
    if ($includesComment) {
      return new EventKind(self::$COMMENT, null);
    }

    return null;
  }
}