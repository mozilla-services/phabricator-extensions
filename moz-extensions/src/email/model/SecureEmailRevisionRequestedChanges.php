<?php


class SecureEmailRevisionRequestedChanges implements SecureEmailBody
{
  /** @var string */
  public $transactionLink;
  /** @var EmailRecipient[] */
  public $reviewers;
  /** @var EmailRecipient (optional) */
  public $author;
  /** @var int */
  public $commentCount;

  /**
   * @param string $transactionLink
   * @param EmailRecipient[] $reviewers
   * @param EmailRecipient $author (optional)
   * @param int $commentCount
   */
  public function __construct(string $transactionLink, array $reviewers, ?EmailRecipient $author, int $commentCount) {
    $this->transactionLink = $transactionLink;
    $this->reviewers = $reviewers;
    $this->author = $author;
    $this->commentCount = $commentCount;
  }
}