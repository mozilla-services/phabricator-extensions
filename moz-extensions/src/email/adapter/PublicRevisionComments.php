<?php


class PublicRevisionComments {
  /** @var string (optional) */
  public $mainComment;
  /** @var EmailInlineComment[] */
  public $inlineComments;
  /** @var PublicEventPings */
  public $pings;

  /**
   * @param string $mainComment
   * @param EmailInlineComment[] $inlineComments
   * @param PublicEventPings $pings
   */
  public function __construct(?string $mainComment, array $inlineComments, PublicEventPings $pings) {
    $this->mainComment = $mainComment;
    $this->inlineComments = $inlineComments;
    $this->pings = $pings;
  }


}