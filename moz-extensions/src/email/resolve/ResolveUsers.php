<?php


class ResolveUsers {
  /** @var DifferentialRevision */
  public $rawRevision;
  /** @var string */
  public $actorEmail;
  /** @var PhabricatorUserStore */
  public $userStore;
  /** @var PhabricatorReviewerStore */
  public $reviewerStore;

  /**
   * @param DifferentialRevision $rawRevision
   * @param string $actorEmail
   * @param PhabricatorUserStore $userStore
   * @param PhabricatorReviewerStore $reviewerStore
   */
  public function __construct(DifferentialRevision $rawRevision, string $actorEmail, PhabricatorUserStore $userStore, PhabricatorReviewerStore $reviewerStore) {
    $this->rawRevision = $rawRevision;
    $this->actorEmail = $actorEmail;
    $this->userStore = $userStore;
    $this->reviewerStore = $reviewerStore;
  }


  /**
   * @return EmailRecipient
   * @throws Exception
   */
  public function resolveAuthorAsRecipient(): ?EmailRecipient {
    $authorPHID = $this->rawRevision->getAuthorPHID();
    $rawAuthor = $this->userStore->find($authorPHID);

    return EmailRecipient::from($rawAuthor, $this->actorEmail);
  }

  /**
   * @return EmailRecipient[]
   * @throws Exception
   */
  public function resolveReviewersAsRecipients(): array {
    $recipients = [];
    foreach ($this->rawRevision->getReviewers() as $reviewer) {
      if ($reviewer->isResigned()) {
        continue;
      }

      $reviewer = $this->reviewerStore->findReviewer($reviewer->getReviewerPHID());
      foreach ($reviewer->toRecipients($this->actorEmail) as $recipient) {
        $recipients[] = $recipient;
      }
    }
    return $recipients;
  }

  /**
   * @return EmailReviewer[]
   * @throws Exception
   */
  public function resolveReviewers(): array {
    $rawReviewers = $this->rawRevision->getReviewers();
    $statuses = array_map(function($reviewer) {
      return $reviewer->getReviewerStatus();
    }, $rawReviewers);
    $isOnlyNonblockingUnreviewed = count(array_filter($statuses, function($status) {
        return $status != 'added';
      })) == 0;

    $reviewers = [];
    foreach ($rawReviewers as $reviewerPHID => $rawReviewer) {
      if ($rawReviewer->isResigned()) {
        // In the future, we could show resigned reviewers in the email body
        continue;
      }

      $rawStatus = $rawReviewer->getReviewerStatus();
      if ($rawStatus == 'accepted') {
        $status = 'accepted';
      } else if ($rawStatus == 'rejected') {
        $status = 'requested-changes';
      } else if ($rawStatus == 'blocking') {
        $status = 'blocking';
      } else {
        $status = 'unreviewed';
      }

      $isActionable = $status == 'blocking' || $status == 'requested-changes' ||
        ($status == 'accepted' && $rawReviewer->getVoidedPHID()) ||
        $isOnlyNonblockingUnreviewed;

      $reviewer = $this->reviewerStore->findReviewer($reviewerPHID);
      $reviewers[] = new EmailReviewer($reviewer->name(), $isActionable, $status, $reviewer->toRecipients($this->actorEmail));
    }
    return $reviewers;

  }
}