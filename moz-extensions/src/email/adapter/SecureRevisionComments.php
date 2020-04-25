<?php


class SecureRevisionComments {
  /** @var int */
  public $count;
  /** @var SecureEventPings */
  public $pings;

  /**
   * @param int $commentCount
   * @param SecureEventPings $pings
   */
  public function __construct(int $commentCount, SecureEventPings $pings) {
    $this->count = $commentCount;
    $this->pings = $pings;
  }


}