<?php


class SecureEventPings {
  private $pingedUsers;

  public function __construct() {
    $this->pingedUsers = [];
  }

  public function add(PhabricatorUser $target) {
    $this->pingedUsers[$target->getPHID()] = $target;
  }

  /**
   * @param string $actorEmail
   * @param string $transactionLink
   * @return SecureEmailRevisionCommentPinged[]
   */
  public function intoBodies(string $actorEmail, string $transactionLink): array {
    return array_map(function(PhabricatorUser $target) use ($actorEmail, $transactionLink) {
      return new SecureEmailRevisionCommentPinged(EmailRecipient::from($target, $actorEmail),$transactionLink);
    }, array_values($this->pingedUsers));
  }
}