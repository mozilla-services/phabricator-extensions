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
  /** @var EmailRecipient[] */
  public $recipients;

  /**
   * @param string $name
   * @param bool $isActionable
   * @param string $status
   * @param string $metadataChange
   * @param EmailRecipient[] $recipients
   */
  public function __construct(string $name, bool $isActionable, string $status, string $metadataChange, array $recipients) {
    $this->name = $name;
    $this->isActionable = $isActionable;
    $this->status = $status;
    $this->metadataChange = $metadataChange;
    $this->recipients = $recipients;
  }

  public static function from(string $reviewerPHID, DifferentialRevision $rawRevision, ReviewersTransaction $reviewersTx, PhabricatorReviewerStore $reviewerStore, string $actorEmail) {
    $status = $reviewersTx->getReviewerStatus($reviewerPHID);
    $metadataChange = $reviewersTx->getReviewerChange($reviewerPHID);

    $rawReviewers = $rawRevision->getReviewers();
    $rawReviewer = current(array_filter($rawReviewers, function($rawReviewer) use ($reviewerPHID) {
      return $rawReviewer->getReviewerPHID() == $reviewerPHID;
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

    $reviewer = $reviewerStore->findReviewer($reviewerPHID);
    return new EmailMetadataEditedReviewer($reviewer->name(), $isActionable, $status, $metadataChange, $reviewer->toRecipients($actorEmail));
  }
}