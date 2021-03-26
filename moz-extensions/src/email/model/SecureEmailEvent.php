<?php


class SecureEmailEvent {
  public string $eventKind;
  public bool $isSecure;
  public string $actorName;
  public SecureEmailRevision $revision;
  public SecureEmailBody $body;
  public string $key;
  /** time of event in seconds since epoch */
  public int $timestamp;

  public function __construct(string $eventKind, string $actorName, DifferentialRevision $rawRevision, SecureEmailBody $body, BugStore $bugStore, string $key, int $timestamp) {
    $this->eventKind = $eventKind;
    $this->isSecure = true;
    $this->actorName = $actorName;
    $this->revision = SecureEmailRevision::from($rawRevision, $bugStore);
    $this->body = $body;
    $this->key = $key;
    $this->timestamp = $timestamp;
  }


}