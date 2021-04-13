<?php


class EmailRevisionRequestedChanges implements PublicEmailBody {
  public string $transactionLink;
  public ?EmailCommentMessage $mainCommentMessage;
  /** @var EmailInlineComment[] */
  public array $inlineComments;
  /** @var EmailRecipient[] */
  public array $reviewers;
  /** @var EmailRecipient[] */
  public array $subscribers;
  public ?EmailRecipient $author;

  /**
   * @param string $transactionLink
   * @param EmailCommentMessage|null $mainCommentMessage
   * @param EmailInlineComment[] $inlineComments
   * @param EmailRecipient[] $reviewers
   * @param EmailRecipient[] $subscribers
   * @param EmailRecipient|null $author
   */
  public function __construct(string $transactionLink, ?EmailCommentMessage $mainCommentMessage, array $inlineComments, array $reviewers, array $subscribers, ?EmailRecipient $author)
  {
    $this->transactionLink = $transactionLink;
    $this->mainCommentMessage = $mainCommentMessage;
    $this->inlineComments = $inlineComments;
    $this->reviewers = $reviewers;
    $this->subscribers = $subscribers;
    $this->author = $author;
  }

}