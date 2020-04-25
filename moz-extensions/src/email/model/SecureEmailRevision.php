<?php


class SecureEmailRevision {
  /** @var int */
  public $revisionId;
  /** @var string */
  public $link;
  /** @var SecureEmailBug (optional) */
  public $bug;

  /**
   * @param int $revisionId
   * @param string $link
   * @param SecureEmailBug $bug (optional)
   */
  public function __construct(string $revisionId, string $link, ?SecureEmailBug $bug) {
    $this->revisionId = $revisionId;
    $this->link = $link;
    $this->bug = $bug;
  }

  public static function from(DifferentialRevision $rawRevision, BugStore $bugStore) {
    $bug = $bugStore->resolveBug($rawRevision);
    return new SecureEmailRevision(
      $rawRevision->getID(),
      PhabricatorEnv::getProductionURI($rawRevision->getURI()),
      $bug
    );
  }
}