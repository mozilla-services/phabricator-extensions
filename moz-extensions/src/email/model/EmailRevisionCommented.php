<?php


class EmailRevisionCommented implements PublicEmailBody
{
  /** @var string */
  public $transactionLink;
  /** @var string (optional) */
  public $mainComment;
  /** @var EmailInlineComment[] */
  public $inlineComments;
  /** @var EmailRecipient[] */
  public $reviewers;
  /** @var EmailRecipient (optional) */
  public $author;

  /**
   * @param string $transactionLink
   * @param string $mainComment (optional)
   * @param EmailInlineComment[] $inlineComments
   * @param EmailRecipient[] $reviewers
   * @param EmailRecipient $author (optional)
   */
  public function __construct(string $transactionLink, ?string $mainComment, array $inlineComments, array $reviewers, ?EmailRecipient $author)
  {
    $this->transactionLink = $transactionLink;
    $this->mainComment = $mainComment;
    $this->inlineComments = $inlineComments;
    $this->reviewers = $reviewers;
    $this->author = $author;
  }
}