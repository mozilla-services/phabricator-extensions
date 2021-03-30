<?php


class SecureEmailContext {
  public string $eventKind;
  public string $actorName;
  public SecureEmailRevision $revision;
  public SecureEmailBody $body;

  public function __construct(string $eventKind, string $actorName, SecureEmailRevision $revision, SecureEmailBody $body) {
    $this->eventKind = $eventKind;
    $this->actorName = $actorName;
    $this->revision = $revision;
    $this->body = $body;
  }


}