<?php


class EmailRevisionCommentPinged implements PublicEmailBody {
  /** @var EmailRecipient */
  public $recipient;
  /** @var string */
  public $transactionLink;
  /** @var string|null */
  public $pingedMainComment;
  /** @var EmailInlineComment[] */
  public $pingedInlineComments;

  /**
   * @param EmailRecipient $recipient
   * @param string $transactionLink
   * @param string|null $pingedMainComment
   * @param EmailInlineComment[] $pingedInlineComments
   */
  public function __construct(EmailRecipient $recipient, string $transactionLink, ?string $pingedMainComment, array $pingedInlineComments) {
    $this->recipient = $recipient;
    $this->transactionLink = $transactionLink;
    $this->pingedMainComment = $pingedMainComment;
    $this->pingedInlineComments = $pingedInlineComments;
  }


}