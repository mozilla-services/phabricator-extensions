<?php


interface PhabricatorReviewer {
  public function name(): string;

  /**
   * @param string $actorEmail
   * @return EmailRecipient[]
   */
  public function toRecipients(string $actorEmail): array;
}