<?php


class EmailRevisionCommentPinged implements PublicEmailBody {
  /** @var EmailRecipient */
  public $recipient;
  /** @var string */
  public $transactionLink;
  /** @var string|null @deprecated */
  public $pingedMainComment;
  /** @var EmailCommentMessage|null */
  public $pingedMainCommentMessage;
  /** @var EmailInlineComment[] */
  public $pingedInlineComments;

  /**
   * @param EmailRecipient $recipient
   * @param string $transactionLink
   * @param EmailCommentMessage|null $pingedMainCommentMessage
   * @param EmailInlineComment[] $pingedInlineComments
   */
  public function __construct(EmailRecipient $recipient, string $transactionLink, ?EmailCommentMessage $pingedMainCommentMessage, array $pingedInlineComments) {
    $this->recipient = $recipient;
    $this->transactionLink = $transactionLink;
    $this->pingedMainComment = $pingedMainCommentMessage ? $pingedMainCommentMessage->asText : null;
    $this->pingedMainCommentMessage = $pingedMainCommentMessage;
    $this->pingedInlineComments = $pingedInlineComments;
  }


}