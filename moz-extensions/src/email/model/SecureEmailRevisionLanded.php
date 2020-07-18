<?php


class SecureEmailRevisionLanded implements SecureEmailBody
{
  /** @var EmailRecipient[] */
  public $reviewers;
  /** @var EmailRecipient|null */
  public $author;
  /** @var int */
  public $commentCount;
  /** @var string */
  public $transactionLink;

  /**
   * @param EmailRecipient[] $reviewers
   * @param EmailRecipient|null $author
   * @param int $commentCount
   * @param string $transactionLink
   */
  public function __construct(array $reviewers, ?EmailRecipient $author, int $commentCount, string $transactionLink) {
    $this->reviewers = $reviewers;
    $this->author = $author;
    $this->commentCount = $commentCount;
    $this->transactionLink = $transactionLink;
  }
}