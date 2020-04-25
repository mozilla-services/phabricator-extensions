<?php


class EmailMetadataEditedReviewer {
  /** @var string */
  public $name;
  /** @var bool */
  public $isActionable;
  /** @var string either 'accepted', 'requested-changes' 'unreviewed' or 'blocking' */
  public $status;
  /** @var string either 'added', 'removed' or 'no-change' */
  public $metadataChange;
  /** @var EmailRecipient (optional) */
  public $recipient;

  /**
   * @param string $name
   * @param bool $isActionable
   * @param string $status
   * @param string $metadataChange
   * @param EmailRecipient $recipient (optional)
   */
  public function __construct(string $name, bool $isActionable, string $status, string $metadataChange, ?EmailRecipient $recipient) {
    $this->name = $name;
    $this->isActionable = $isActionable;
    $this->status = $status;
    $this->metadataChange = $metadataChange;
    $this->recipient = $recipient;
  }

  public static function from(string $PHID, DifferentialRevision $rawRevision, ReviewersTransaction $reviewersTx, PhabricatorUserStore $userStore, string $actorEmail) {
    $rawUser = $userStore->find($PHID);
    $recipient = EmailRecipient::from($rawUser, $actorEmail);
    $status = $reviewersTx->getReviewerStatus($PHID);
    $metadataChange = $reviewersTx->getReviewerChange($PHID);

    $rawReviewers = $rawRevision->getReviewers();
    $rawReviewer = current(array_filter($rawReviewers, function($rawReviewer) use ($PHID) {
      return $rawReviewer->getReviewerPHID() == $PHID;
    }));

    if (!$rawReviewer) {
      // The reviewer was removed from the revision _after_ the metadata edit, but _before_ the call to the
      // Phabricator email API endpoint. So, from our metadata edit transaction, we're looking for a reviewer
      // that no longer exists. In this situation where we don't know the old reviewer's information, we
      // default to assuming that their status was _not_ voided since that is less noisy (less "actionable" emails)
      $isVoided = false;
    } else {
      $isVoided = !is_null($rawReviewer->getVoidedPHID());
    }
    $isActionable = false;
    if ($metadataChange != 'removed') {
      $isActionable = $status == 'blocking' || $status == 'requested-changes' ||
        ($status == 'accepted' && $isVoided) ||
        $reviewersTx->isOnlyNonblockingUnreviewed();
    }

    return new EmailMetadataEditedReviewer($rawUser->getUserName(), $isActionable, $status, $metadataChange, $recipient);
  }
}