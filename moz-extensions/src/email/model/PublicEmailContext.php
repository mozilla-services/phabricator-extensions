<?php


class PublicEmailContext {
  public string $eventKind;
  public string $actorName;
  public EmailRevision $revision;
  public PublicEmailBody $body;

  public function __construct(string $eventKind, string $actorName, EmailRevision $revision, PublicEmailBody $body) {
    $this->eventKind = $eventKind;
    $this->actorName = $actorName;
    $this->revision = $revision;
    $this->body = $body;
  }


}