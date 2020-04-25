<?php


class SecureEmailEvent {
  /** @var string */
  public $eventKind;
  /** @var bool */
  public $isSecure;
  /** @var string */
  public $actorName;
  /** @var SecureEmailRevision */
  public $revision;
  /** @var SecureEmailBody */
  public $body;
  /** @var string */
  public $key;

  /**
   * @param string $eventKind
   * @param string $actorName
   * @param DifferentialRevision $rawRevision
   * @param SecureEmailBody $body
   * @param BugStore $bugStore
   * @param string $key
   */
  public function __construct(string $eventKind, string $actorName, DifferentialRevision $rawRevision, SecureEmailBody $body, BugStore $bugStore, string $key) {
    $this->eventKind = $eventKind;
    $this->isSecure = true;
    $this->actorName = $actorName;
    $this->revision = SecureEmailRevision::from($rawRevision, $bugStore);
    $this->body = $body;
    $this->key = $key;
  }


}