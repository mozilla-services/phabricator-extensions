<?php


class PhabricatorStoryBuilder {
  public $transactions;
  public $eventKind;
  public $revisionPHID;
  public $actorPHID;
  private $revision;
  private $actor;
  private $key;
  private $timestamp;

  /**
   * @param EventKind $eventKind
   * @param array $transactions
   * @param string $key
   * @param int $timestamp
   */
  public function __construct(EventKind $eventKind, array $transactions, string $key, int $timestamp) {
    $this->eventKind = $eventKind;
    $this->transactions = new TransactionList($transactions);
    $this->revisionPHID = $eventKind->findMainTransaction($this->transactions)->getObjectPHID();
    $this->key = $key;
    $this->timestamp = $timestamp;
  }

  public function associateRevision(DifferentialRevision $revision, string $actorPHID) {
    $this->revision = $revision;
    $this->actorPHID = $actorPHID;
  }

  public function associateActor(PhabricatorUser $actor) {
    $this->actor = $actor;
  }

  public function asStory() {
    return new PhabricatorStory($this->eventKind, $this->transactions, $this->revision, $this->actor, $this->key, $this->timestamp);
  }
}