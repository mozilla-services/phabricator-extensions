<?php


class EmailEvent {
  public string $eventKind;
  public bool $isSecure;
  public string $actorName;
  public EmailRevision $revision;
  public PublicEmailBody $body;
  public string $key;
  /** time of event in seconds since epoch */
  public int $timestamp;

  public function __construct(string $eventKind, string $actorName, EmailRevision $revision, PublicEmailBody $body, string $key, int $timestamp) {
    $this->eventKind = $eventKind;
    $this->isSecure = false;
    $this->actorName = $actorName;
    $this->revision = $revision;
    $this->body = $body;
    $this->key = $key;
    $this->timestamp = $timestamp;
  }


}