<?php


class EmailRevisionCommented implements PublicEmailBody
{
  public string $transactionLink;
  /** @deprecated */
  public ?string $mainComment;
  public ?EmailCommentMessage $mainCommentMessage;
  /** @var EmailInlineComment[] */
  public array $inlineComments;
  /** @var EmailRecipient[] */
  public array $reviewers;
  public ?EmailRecipient $author;

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