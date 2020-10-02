<?php


class PublicRevisionComments {
  /** @var EmailCommentMessage|null $mainCommentMessage */
  public $mainCommentMessage;
  /** @var EmailInlineComment[] */
  public $inlineComments;
  /** @var PublicEventPings */
  public $pings;

  /**
   * @param EmailCommentMessage|null $mainCommentMessage
   * @param EmailInlineComment[] $inlineComments
   * @param PublicEventPings $pings
   */
  public function __construct(?EmailCommentMessage $mainCommentMessage, array $inlineComments, PublicEventPings $pings) {
    $this->mainCommentMessage = $mainCommentMessage;
    $this->inlineComments = $inlineComments;
    $this->pings = $pings;
  }


}