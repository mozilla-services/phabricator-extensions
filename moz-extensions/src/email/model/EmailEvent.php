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

  /**
   * @param string $eventKind
   * @param string $actorName
   * @param EmailRevision $revision
   * @param PublicEmailBody $body
   * @param string $key
   */
  public function __construct(string $eventKind, string $actorName, EmailRevision $revision, PublicEmailBody $body, string $key) {
    $this->eventKind = $eventKind;
    $this->isSecure = false;
    $this->actorName = $actorName;
    $this->revision = $revision;
    $this->body = $body;
    $this->key = $key;
  }


}