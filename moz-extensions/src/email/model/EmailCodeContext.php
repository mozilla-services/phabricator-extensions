<?php


class EmailCodeContext {
  /** @var EmailDiffLine[] */
  public $diff;

  /**
   * @param EmailDiffLine[] $diff
   */
  public function __construct(array $diff) {
    $this->diff = $diff;
  }


}