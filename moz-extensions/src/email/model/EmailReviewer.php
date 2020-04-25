<?php


class EmailReviewer {
  /** @var string */
  public $name;
  /** @var bool */
  public $isActionable;
  /** @var string either 'accepted', 'requested-changes' 'unreviewed' or 'blocking' */
  public $status;
  /** @var EmailRecipient[] (optional) */
  public $recipients;

  /**
   * @param string $name
   * @param bool $isActionable
   * @param string $status
   * @param EmailRecipient[] $recipients
   */
  public function __construct(string $name, bool $isActionable, string $status, array $recipients) {
    $this->name = $name;
    $this->isActionable = $isActionable;
    $this->status = $status;
    $this->recipients = $recipients;
  }
}