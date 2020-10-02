<?php


class EmailReplyContext {
  /** @var string */
  public $otherAuthor;
  /** @var string */
  public $otherDateUtc;
  /** @var string @deprecated */
  public $otherComment;
  /** @var string */
  public $otherCommentMessage;

  /**
   * @param string $otherAuthor
   * @param DateTime $otherDateUtc
   * @param string $otherComment
   */
  public function __construct(string $otherAuthor, DateTime $otherDateUtc, EmailCommentMessage $message) {
    $this->otherAuthor = $otherAuthor;
    $this->otherDateUtc = $otherDateUtc->format(DateTime::ATOM);
    $this->otherComment = $message->asText;
    $this->otherCommentMessage = $message;
  }


}