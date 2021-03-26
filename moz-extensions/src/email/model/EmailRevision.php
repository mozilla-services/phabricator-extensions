<?php


class EmailRevision {
  public int $revisionId;
  public string $name;
  public string $link;
  public ?EmailBug $bug;

  public function __construct(int $revisionId, string $name, string $link, ?EmailBug $bug) {
    $this->revisionId = $revisionId;
    $this->name = $name;
    $this->link = $link;
    $this->bug = $bug;
  }

  public static function from(DifferentialRevision $rawRevision, BugStore $bugStore): EmailRevision
  {
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