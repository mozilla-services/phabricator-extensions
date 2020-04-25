<?php


class SecureEmailRevisionCommentPinged implements SecureEmailBody {
  /** @var EmailRecipient */
  public $recipient;
  /** @var string */
  public $transactionLink;

  /**
   * @param EmailRecipient $recipient
   * @param string $transactionLink
   */
  public function __construct(EmailRecipient $recipient, string $transactionLink) {
    $this->recipient = $recipient;
    $this->transactionLink = $transactionLink;
  }
}