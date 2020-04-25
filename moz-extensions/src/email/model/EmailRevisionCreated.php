<?php


class EmailRevisionCreated implements PublicEmailBody
{
  /** @var EmailAffectedFile[] */
  public $affectedFiles;
  /** @var EmailReviewer[] */
  public $reviewers;

  /**
   * @param EmailAffectedFile[] $affectedFiles
   * @param EmailReviewer[] $reviewers
   */
  public function __construct(array $affectedFiles, array $reviewers) {
    $this->affectedFiles = $affectedFiles;
    $this->reviewers = $reviewers;
  }
}