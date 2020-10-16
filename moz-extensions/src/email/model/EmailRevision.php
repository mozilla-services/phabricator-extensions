<?php


class EmailRevision {
  /** @var int */
  public $revisionId;
  /** @var string */
  public $name;
  /** @var string */
  public $link;
  /** @var EmailBug|null */
  public $bug;

  /**
   * @param int $revisionId
   * @param string $name
   * @param string $link
   * @param EmailBug|null $bug
   */
  public function __construct(int $revisionId, string $name, string $link, ?EmailBug $bug) {
    $this->revisionId = $revisionId;
    $this->name = $name;
    $this->link = $link;
    $this->bug = $bug;
  }

  public static function from(DifferentialRevision $rawRevision, BugStore $bugStore) {
    $secureBug = $bugStore->resolveBug($rawRevision);
    if (!$secureBug) {
      $bug = null;
    } else {
      $bugName = $bugStore->queryName($secureBug->bugId) ?? '(failed to fetch bug name)';
      $bug = new EmailBug($secureBug->bugId, $bugName, $secureBug->link);
    }

    return new EmailRevision(
      $rawRevision->getID(),
      str_replace("\n", ' ', $rawRevision->getTitle()),
      PhabricatorEnv::getProductionURI($rawRevision->getURI()),
      $bug
    );
  }

}