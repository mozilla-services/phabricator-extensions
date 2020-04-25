<?php


class EmailBug {
  /** @var int */
  public $bugId;
  /** @var string */
  public $name;
  /** @var string */
  public $link;

  /**
   * @param int $bugId
   * @param string $name
   * @param string $link
   */
  public function __construct(int $bugId, string $name, string $link) {
    $this->bugId = $bugId;
    $this->name = $name;
    $this->link = $link;
  }


}