<?php


class PublicEventPings {
  private $pingedUsers;

  public function __construct() {
    $this->pingedUsers = [];
  }

  public function fromMainComment(PhabricatorUser $target, string $comment) {
    $ping = $this->pingedUsers[$target->getPHID()] ?? new PublicPing($target);
    $ping->setMainComment($comment);
    $this->pingedUsers[$target->getPHID()] = $ping;
  }

  public function fromInlineComment(PhabricatorUser $target, EmailInlineComment $inlineComment) {
    $ping = $this->pingedUsers[$target->getPHID()] ?? new PublicPing($target);
    $ping->appendInlineComment($inlineComment);
    $this->pingedUsers[$target->getPHID()] = $ping;
  }

  /**
   * @param string $actorEmail
   * @param string $transactionLink
   * @return EmailRevisionCommentPinged[]
   */
  public function intoBodies(string $actorEmail, string $transactionLink): array {
    return array_filter(array_map(function(PublicPing $ping) use ($actorEmail, $transactionLink) {
      return $ping->intoPublicBody($actorEmail, $transactionLink);
    }, array_values($this->pingedUsers)));
  }
}