<?php


class PhabricatorDiffStore {
  private $cache;

  public function __construct() {
    $this->cache = [];
  }

  public function find(string $PHID) {
    if (array_key_exists($PHID, $this->cache)) {
      return $this->cache[$PHID];
    }

    $diff = id(new DifferentialDiffQuery())
      ->withPHIDs([$PHID])
      ->setViewer(PhabricatorUser::getOmnipotentUser())
      ->needChangesets(true)
      ->executeOne();
    $this->cache[$PHID] = $diff;
    return $diff;
  }
}