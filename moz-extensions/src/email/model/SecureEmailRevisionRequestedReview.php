<?php


class SecureEmailRevisionRequestedReview implements SecureEmailBody
{
  /** @var EmailReviewer[] */
  public array $reviewers;
  public int $commentCount;
  public string $transactionLink;

  /**
   * @param EmailReviewer[] $reviewers
   * @param int $commentCount
   * @param string $transactionLink
   */
  public function __construct(array $reviewers, int $commentCount, string $transactionLink) {
    $this->reviewers = $reviewers;
    $this->commentCount = $commentCount;
    $this->transactionLink = $transactionLink;
  }
}