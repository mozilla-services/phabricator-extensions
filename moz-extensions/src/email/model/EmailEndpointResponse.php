<?php


class EmailEndpointResponse {
  /** @var EmailEndpointResponseData */
  public $data;
  /** @var EmailEndpointResponseCursor */
  public $cursor;

  /**
   * @param EmailEndpointResponseData $data
   * @param EmailEndpointResponseCursor $cursor
   */
  public function __construct(EmailEndpointResponseData $data, EmailEndpointResponseCursor $cursor) {
    $this->data = $data;
    $this->cursor = $cursor;
  }


}