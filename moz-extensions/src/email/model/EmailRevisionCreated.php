<?php


class EmailRevisionCreated implements PublicEmailBody
{
  /** @var EmailAffectedFile[] */
  public array $affectedFiles;
  /** @var EmailReviewer[] */
  public array $reviewers;

  /**
   * @param EmailAffectedFile[] $affectedFiles
   * @param EmailReviewer[] $reviewers
   */
  public function __construct(array $affectedFiles, array $reviewers) {
    $this->affectedFiles = $affectedFiles;
    $this->reviewers = $reviewers;
  }
}