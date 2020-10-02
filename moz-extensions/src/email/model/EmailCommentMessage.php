<?php


class EmailCommentMessage {
  /** @var string */
  public $asText;
  /** @var string */
  public $asHtml;

  /**
   * @param string $asText
   * @param string $asHtml
   */
  public function __construct(string $asText, string $asHtml) {
    $this->asText = $asText;
    $this->asHtml = $asHtml;
  }


}