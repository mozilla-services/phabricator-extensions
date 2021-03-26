<?php


class EmailRevisionAccepted implements PublicEmailBody
{
  public ?string $mainComment;
  public ?EmailCommentMessage $mainCommentMessage;
  /** @var EmailInlineComment[] */
  public array $inlineComments;
  public string $transactionLink;
  public string $landoLink;
  public bool $isReadyToLand;
  /** @var EmailRecipient[] */
  public array $reviewers;
  public ?EmailRecipient $author;

  /**
   * @param EmailCommentMessage|null $mainCommentMessage
   * @param EmailInlineComment[] $inlineComments
   * @param string $transactionLink
   * @param string $landoLink
   * @param bool $isReadyToLand
   * @param EmailRecipient[] $reviewers
   * @param EmailRecipient|null $author
   */
  public function __construct(?EmailCommentMessage $mainCommentMessage, array $inlineComments, string $transactionLink, string $landoLink, bool $isReadyToLand, array $reviewers, ?EmailRecipient $author) {
    $this->mainComment = $mainCommentMessage ? $mainCommentMessage->asText : null;
    $this->mainCommentMessage = $mainCommentMessage;
    $this->inlineComments = $inlineComments;
    $this->transactionLink = $transactionLink;
    $this->landoLink = $landoLink;
    $this->isReadyToLand = $isReadyToLand;
    $this->reviewers = $reviewers;
    $this->author = $author;
  }
}