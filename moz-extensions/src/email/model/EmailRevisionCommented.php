<?php


class EmailRevisionCommented implements PublicEmailBody
{
  /** @var string */
  public $transactionLink;
  /** @var string|null */
  public $mainComment;
  /** @var EmailInlineComment[] */
  public $inlineComments;
  /** @var EmailRecipient[] */
  public $reviewers;
  /** @var EmailRecipient|null */
  public $author;

  /**
   * @param string $transactionLink
   * @param string|null $mainComment
   * @param EmailInlineComment[] $inlineComments
   * @param EmailRecipient[] $reviewers
   * @param EmailRecipient|null $author
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