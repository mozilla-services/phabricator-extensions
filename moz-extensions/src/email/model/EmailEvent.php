<?php


class EmailEvent {
  /** @var string */
  public $eventKind;
  /** @var bool */
  public $isSecure;
  /** @var string */
  public $actorName;
  /** @var EmailRevision */
  public $revision;
  /** @var PublicEmailBody */
  public $body;
  /** @var string */
  public $key;
  /** @var int time of event in seconds since epoch */
  public $timestamp;

  /**
   * @param string $eventKind
   * @param string $actorName
   * @param EmailRevision $revision
   * @param PublicEmailBody $body
   * @param string $key
   * @param int $timestamp
   */
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