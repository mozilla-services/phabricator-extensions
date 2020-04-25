<?php


class EmailEndpointResponseData {
  /** @var array of email events (secure and regular) */
  public $events;
  /** @var int */
  public $storyErrors;

  /**
   * @param array $events
   * @param int $storyErrors
   */
  public function __construct(array $events, int $storyErrors) {
    $this->events = $events;
    $this->storyErrors = $storyErrors;
  }


}