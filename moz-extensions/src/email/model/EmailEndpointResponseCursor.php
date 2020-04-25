<?php


class EmailEndpointResponseCursor {
  /** @var int */
  public $limit;
  /** @var string (optional) */
  public $after;

  /**
   * @param int $limit
   * @param string $after (optional)
   */
  public function __construct(int $limit, ?string $after) {
    $this->limit = $limit;
    $this->after = $after;
  }


}