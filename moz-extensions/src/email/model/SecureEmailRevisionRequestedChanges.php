<?php


class SecureEmailRevisionRequestedChanges implements SecureEmailBody
{
  public string $transactionLink;
  /** @var EmailRecipient[] */
  public array $reviewers;
  public ?EmailRecipient $author;
  public int $commentCount;

  /**
   * @param string $transactionLink
   * @param EmailRecipient[] $reviewers
   * @param EmailRecipient|null $author
   * @param int $commentCount
   */
  public function __construct(string $transactionLink, array $reviewers, ?EmailRecipient $author, int $commentCount) {
    $this->transactionLink = $transactionLink;
    $this->reviewers = $reviewers;
    $this->author = $author;
    $this->commentCount = $commentCount;
  }
}