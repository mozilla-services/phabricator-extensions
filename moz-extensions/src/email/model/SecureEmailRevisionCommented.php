<?php


class SecureEmailRevisionCommented implements SecureEmailBody
{
  /** @var EmailRecipient[] */
  public $reviewers;
  /** @var EmailRecipient (optional) */
  public $author;
  /** @var string */
  public $transactionLink;

  /**
   * @param EmailRecipient[] $reviewers
   * @param EmailRecipient $author (optional)
   * @param string $transactionLink
   */
  public function __construct(array $reviewers, ?EmailRecipient $author, string $transactionLink) {
    $this->reviewers = $reviewers;
    $this->author = $author;
    $this->transactionLink = $transactionLink;
  }
}