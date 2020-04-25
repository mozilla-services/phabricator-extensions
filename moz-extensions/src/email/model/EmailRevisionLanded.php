<?php


class EmailRevisionLanded implements PublicEmailBody
{
  /** @var string (optional) */
  public $mainComment;
  /** @var EmailInlineComment[] */
  public $inlineComments;
  /** @var string */
  public $transactionLink;
  /** @var EmailRecipient[] */
  public $reviewers;
  /** @var EmailRecipient (optional) */
  public $author;

  /**
   * @param string $mainComment (optional)
   * @param EmailInlineComment[] $inlineComments
   * @param string $transactionLink
   * @param EmailRecipient[] $reviewers
   * @param EmailRecipient $author (optional)
   */
  public function __construct(?string $mainComment, array $inlineComments, string $transactionLink, array $reviewers, ?EmailRecipient $author)
  {
    $this->mainComment = $mainComment;
    $this->inlineComments = $inlineComments;
    $this->transactionLink = $transactionLink;
    $this->reviewers = $reviewers;
    $this->author = $author;
  }
}