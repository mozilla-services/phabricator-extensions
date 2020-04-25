<?php


class SecureEmailRevisionAccepted implements SecureEmailBody
{
  /** @var string */
  public $landoLink;
  /** @var bool */
  public $isReadyToLand;
  /** @var EmailRecipient[] */
  public $reviewers;
  /** @var EmailRecipient (optional) */
  public $author;
  /** @var int */
  public $commentCount;
  /** @var string */
  public $transactionLink;

  /**
   * @param string $landoLink
   * @param bool $isReadyToLand
   * @param EmailRecipient[] $reviewers
   * @param EmailRecipient $author (optional)
   * @param int $commentCount
   * @param string $transactionLink
   */
  public function __construct(string $landoLink, bool $isReadyToLand, array $reviewers, ?EmailRecipient $author, int $commentCount, string $transactionLink) {
    $this->landoLink = $landoLink;
    $this->isReadyToLand = $isReadyToLand;
    $this->reviewers = $reviewers;
    $this->author = $author;
    $this->commentCount = $commentCount;
    $this->transactionLink = $transactionLink;
  }
}