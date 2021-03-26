<?php


class EmailReplyContext {
  public string $otherAuthor;
  public string $otherDateUtc;
  /** @deprecated */
  public string $otherComment;
  public EmailCommentMessage $otherCommentMessage;

  public function __construct(string $otherAuthor, DateTime $otherDateUtc, EmailCommentMessage $message) {
    $this->otherAuthor = $otherAuthor;
    $this->otherDateUtc = $otherDateUtc->format(DateTime::ATOM);
    $this->otherComment = $message->asText;
    $this->otherCommentMessage = $message;
  }


}