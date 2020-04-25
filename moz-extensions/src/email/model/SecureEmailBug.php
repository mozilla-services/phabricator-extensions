<?php


class SecureEmailBug {
  /** @var int */
  public $bugId;
  /** @var string */
  public $link;

  /**
   * @param int $bugId
   * @param string $link
   */
  public function __construct(int $bugId, string $link) {
    $this->bugId = $bugId;
    $this->link = $link;
  }


}