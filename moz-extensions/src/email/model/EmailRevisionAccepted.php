<?php


class EmailRevisionAccepted implements PublicEmailBody
{
  /** @var string|null */
  public $mainComment;
  /** @var EmailInlineComment[] */
  public $inlineComments;
  /** @var string */
  public $transactionLink;
  /** @var string */
  public $landoLink;
  /** @var bool */
  public $isReadyToLand;
  /** @var EmailRecipient[] */
  public $reviewers;
  /** @var EmailRecipient|null */
  public $author;

  /**
   * @param string|null $mainComment
   * @param EmailInlineComment[] $inlineComments
   * @param string $transactionLink
   * @param string $landoLink
   * @param bool $isReadyToLand
   * @param EmailRecipient[] $reviewers
   * @param EmailRecipient|null $author
   */
  public function __construct(?string $mainComment, array $inlineComments, string $transactionLink, string $landoLink, bool $isReadyToLand, array $reviewers, ?EmailRecipient $author) {
    $this->mainComment = $mainComment;
    $this->inlineComments = $inlineComments;
    $this->transactionLink = $transactionLink;
    $this->landoLink = $landoLink;
    $this->isReadyToLand = $isReadyToLand;
    $this->reviewers = $reviewers;
    $this->author = $author;
  }
}