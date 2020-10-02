<?php


class EmailRevisionAccepted implements PublicEmailBody
{
  /** @var string|null @deprecated */
  public $mainComment;
  /** @var EmailCommentMessage|null */
  public $mainCommentMessage;
  /** @var EmailInlineComment[] */
  public $inlineComments;
  /** @var string */
  public $transactionLink;
  /** @var string */
  public $landoLink;
  /** @var bool */
  public $isReadyToLand;
  /** @var EmailRecipient[] */
  public $reviewers;
  /** @var EmailRecipient|null */
  public $author;

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