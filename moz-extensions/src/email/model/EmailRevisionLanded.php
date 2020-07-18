<?php


class EmailRevisionLanded implements PublicEmailBody
{
  /** @var string|null */
  public $mainComment;
  /** @var EmailInlineComment[] */
  public $inlineComments;
  /** @var string */
  public $transactionLink;
  /** @var EmailRecipient[] */
  public $reviewers;
  /** @var EmailRecipient|null */
  public $author;

  /**
   * @param string|null $mainComment
   * @param EmailInlineComment[] $inlineComments
   * @param string $transactionLink
   * @param EmailRecipient[] $reviewers
   * @param EmailRecipient|null $author
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