<?php


class EmailReviewer {
  /** @var string */
  public $name;
  /** @var bool */
  public $isActionable;
  /** @var string either 'accepted', 'requested-changes' 'unreviewed' or 'blocking' */
  public $status;
  /** @var EmailRecipient */
  public $recipient;

  /**
   * @param string $name
   * @param bool $isActionable
   * @param string $status
   * @param EmailRecipient $recipient
   */
  public function __construct(string $name, bool $isActionable, string $status, EmailRecipient $recipient) {
    $this->name = $name;
    $this->isActionable = $isActionable;
    $this->status = $status;
    $this->recipient = $recipient;
  }
}