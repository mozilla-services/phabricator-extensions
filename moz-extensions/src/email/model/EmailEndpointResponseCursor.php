<?php


class EmailEndpointResponseCursor {
  /** @var int */
  public $limit;
  /** @var string|null */
  public $after;

  /**
   * @param int $limit
   * @param string|null $after
   */
  public function __construct(int $limit, ?string $after) {
    $this->limit = $limit;
    $this->after = $after;
  }


}