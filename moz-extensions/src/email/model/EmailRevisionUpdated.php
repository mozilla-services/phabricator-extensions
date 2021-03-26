<?php


class EmailRevisionUpdated implements PublicEmailBody
{
  /** @var EmailAffectedFile[] */
  public array $affectedFiles;
  public string $landoLink;
  public string $newChangesLink;
  public bool $isReadyToLand;
  /** @var EmailReviewer[] */
  public array $reviewers;

  /**
   * @param EmailAffectedFile[] $affectedFiles
   * @param string $landoLink
   * @param string $newChangesLink
   * @param bool $isReadyToLand
   * @param EmailReviewer[] $reviewers
   */
  public function __construct(array $affectedFiles, string $landoLink, string $newChangesLink, bool $isReadyToLand, array $reviewers) {
    $this->affectedFiles = $affectedFiles;
    $this->landoLink = $landoLink;
    $this->newChangesLink = $newChangesLink;
    $this->isReadyToLand = $isReadyToLand;
    $this->reviewers = $reviewers;
  }
}