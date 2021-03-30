<?php


class EmailRevisionReclaimed implements PublicEmailBody
{
  public ?EmailCommentMessage $mainCommentMessage;
  /** @var EmailInlineComment[] */
  public array $inlineComments;
  public string $transactionLink;
  /** @var EmailReviewer[] */
  public array $reviewers;

  /**
   * @param EmailCommentMessage|null $mainCommentMessage
   * @param EmailInlineComment[] $inlineComments
   * @param string $transactionLink
   * @param EmailReviewer[] $reviewers
   */
  public function __construct(?EmailCommentMessage $mainCommentMessage, array $inlineComments, string $transactionLink, array $reviewers)
  {
    $this->mainCommentMessage = $mainCommentMessage;
    $this->inlineComments = $inlineComments;
    $this->transactionLink = $transactionLink;
    $this->reviewers = $reviewers;
  }
}