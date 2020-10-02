<?php


class EmailRevisionCommented implements PublicEmailBody
{
  /** @var string */
  public $transactionLink;
  /** @var string|null @deprecated */
  public $mainComment;
  /** @var EmailCommentMessage|null */
  public $mainCommentMessage;
  /** @var EmailInlineComment[] */
  public $inlineComments;
  /** @var EmailRecipient[] */
  public $reviewers;
  /** @var EmailRecipient|null */
  public $author;

  /**
   * @param string $transactionLink
   * @param EmailCommentMessage|null $mainCommentMessage
   * @param EmailInlineComment[] $inlineComments
   * @param EmailRecipient[] $reviewers
   * @param EmailRecipient|null $author
   */
  public function __construct(string $transactionLink, ?EmailCommentMessage $mainCommentMessage, array $inlineComments, array $reviewers, ?EmailRecipient $author)
  {
    $this->transactionLink = $transactionLink;
    $this->mainComment = $mainCommentMessage ? $mainCommentMessage->asText : null;
    $this->mainCommentMessage = $mainCommentMessage;
    $this->inlineComments = $inlineComments;
    $this->reviewers = $reviewers;
    $this->author = $author;
  }
}