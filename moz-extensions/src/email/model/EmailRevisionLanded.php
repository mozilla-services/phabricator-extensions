<?php


class EmailRevisionLanded implements PublicEmailBody
{
  public ?EmailCommentMessage $mainCommentMessage;
  /** @var EmailInlineComment[] */
  public array $inlineComments;
  public string $transactionLink;
  /** @var EmailRecipient[] */
  public array $reviewers;
  public ?EmailRecipient $author;

  /**
   * @param EmailCommentMessage|null $mainCommentMessage
   * @param EmailInlineComment[] $inlineComments
   * @param string $transactionLink
   * @param EmailRecipient[] $reviewers
   * @param EmailRecipient|null $author
   */
  public function __construct(?EmailCommentMessage $mainCommentMessage, array $inlineComments, string $transactionLink, array $reviewers, ?EmailRecipient $author)
  {
    $this->mainCommentMessage = $mainCommentMessage;
    $this->inlineComments = $inlineComments;
    $this->transactionLink = $transactionLink;
    $this->reviewers = $reviewers;
    $this->author = $author;
  }
}