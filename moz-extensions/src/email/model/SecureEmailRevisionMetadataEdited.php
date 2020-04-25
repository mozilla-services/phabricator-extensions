<?php


class SecureEmailRevisionMetadataEdited implements SecureEmailBody, PublicEmailBody {
  /** @var bool */
  public $isReadyToLand;
  /** @var bool */
  public $isTitleChanged;
  /** @var bool */
  public $isBugChanged;
  /** @var EmailRecipient (optional) */
  public $author;
  /** @var EmailMetadataEditedReviewer[] */
  public $reviewers;

  /**
   * @param bool $isReadyToLand
   * @param bool $isTitleChanged
   * @param bool $isBugChanged
   * @param EmailRecipient $author (optional)
   * @param EmailMetadataEditedReviewer[] $reviewers
   */
  public function __construct(bool $isReadyToLand, bool $isTitleChanged, bool $isBugChanged, ?EmailRecipient $author, array $reviewers) {
    $this->isReadyToLand = $isReadyToLand;
    $this->isTitleChanged = $isTitleChanged;
    $this->isBugChanged = $isBugChanged;
    $this->author = $author;
    $this->reviewers = $reviewers;
  }


  public static function from(ResolveUsers $resolveRecipients, ResolveLandStatus $resolveLandStatus, TransactionList $transactions, DifferentialRevision $rawRevision, PhabricatorReviewerStore $reviewerStore, string $actorEmail) {
    $isTitleChanged = $transactions->containsType('differential.revision.title');
    $customFieldTx = $transactions->getTransactionWithType('core:customfield');
    if ($customFieldTx) {
      $isBugChanged = $customFieldTx->getMetadataValue('customfield:key') == 'differential:bugzilla-bug-id';
    } else {
      $isBugChanged = false;
    }

    $reviewers = [];
    $rawReviewersTx = $transactions->getTransactionWithType('differential.revision.reviewers');
    if ($rawReviewersTx) {
      $reviewersTx = new ReviewersTransaction($rawReviewersTx);
      foreach ($reviewersTx->getAllUsers() as $reviewerPHID) {
        $reviewers[] = EmailMetadataEditedReviewer::from($reviewerPHID, $rawRevision, $reviewersTx, $reviewerStore, $actorEmail);
      }
    } else {
      foreach ($resolveRecipients->resolveReviewers() as $reviewer) {
        /** @var $reviewer EmailReviewer */
        $reviewers[] = new EmailMetadataEditedReviewer(
          $reviewer->name,
          $reviewer->isActionable,
          $reviewer->status,
          'no-change',
          $reviewer->recipients
        );
      }
    }

    return new SecureEmailRevisionMetadataEdited($resolveLandStatus->resolveIsReadyToLand(), $isTitleChanged, $isBugChanged, $resolveRecipients->resolveAuthorAsRecipient(), $reviewers);
  }
}