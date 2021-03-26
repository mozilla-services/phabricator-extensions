<?php


class SecureEmailRevisionUpdated implements SecureEmailBody
{
  public string $landoLink;
  public string $newChangesLink;
  public bool $isReadyToLand;
  /** @var EmailReviewer[] */
  public array $reviewers;

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