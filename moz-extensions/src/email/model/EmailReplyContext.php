<?php


class EmailReplyContext {
  /** @var string */
  public $otherAuthor;
  /** @var string */
  public $otherDateUtc;
  /** @var string */
  public $otherComment;

  /**
   * @param string $otherAuthor
   * @param DateTime $otherDateUtc
   * @param string $otherComment
   */
  public function __construct(string $otherAuthor, DateTime $otherDateUtc, string $otherComment) {
    $this->otherAuthor = $otherAuthor;
    $this->otherDateUtc = $otherDateUtc->format(DateTime::ATOM);
    $this->otherComment = $otherComment;
  }


}