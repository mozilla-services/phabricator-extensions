<?php


class EmailDiffLine {
  /** @var int */
  public $lineNumber;
  /** @var string ("added", "removed" or "no-change) */
  public $type;
  /** @var string */
  public $rawContent;

  /**
   * @param int $lineNumber
   * @param string $type
   * @param string $rawContent
   */
  public function __construct(int $lineNumber, string $type, string $rawContent) {
    $this->lineNumber = $lineNumber;
    $this->type = $type;
    $this->rawContent = $rawContent;
  }


}