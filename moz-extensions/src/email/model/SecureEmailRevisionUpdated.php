<?php


class SecureEmailRevisionUpdated implements SecureEmailBody
{
  /** @var string */
  public $landoLink;
  /** @var string */
  public $newChangesLink;
  /** @var bool */
  public $isReadyToLand;
  /** @var EmailReviewer[] */
  public $reviewers;

  /**
   * @param string $landoLink
   * @param string $newChangesLink
   * @param bool $isReadyToLand
   * @param EmailReviewer[] $reviewers
   */
  public function __construct(string $landoLink, string $newChangesLink, bool $isReadyToLand, array $reviewers) {
    $this->landoLink = $landoLink;
    $this->newChangesLink = $newChangesLink;
    $this->isReadyToLand = $isReadyToLand;
    $this->reviewers = $reviewers;
  }
}