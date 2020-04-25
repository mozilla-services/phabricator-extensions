<?php


class PhabricatorUserStore {
  private $PHIDCache;
  private $usernameCache;

  public function __construct() {
    $this->PHIDCache = [];
    $this->usernameCache = [];
  }

  public function find(string $PHID): PhabricatorUser {
    if (array_key_exists($PHID, $this->PHIDCache)) {
      return $this->PHIDCache[$PHID];
    }

    $user = (new PhabricatorPeopleQuery())
      ->setViewer(PhabricatorUser::getOmnipotentUser())
      ->withPHIDs([$PHID])
      ->executeOne();
    $this->PHIDCache[$PHID] = $user;
    return $user;
  }

  public function findByName(string $name): ?PhabricatorUser {
    if (array_key_exists($name, $this->usernameCache)) {
      return $this->usernameCache[$name];
    }

    $user = (new PhabricatorPeopleQuery())
      ->setViewer(PhabricatorUser::getOmnipotentUser())
      ->withUsernames([$name])
      ->withIsDisabled(false)
      ->executeOne();
    $this->usernameCache[$name] = $user;
    return $user;
  }

  /**
   * @param string[] $PHIDs
   * @return PhabricatorUser[]
   * @throws PhutilInvalidStateException
   */
  public function queryAll(array $PHIDs): array {
    $users = (new PhabricatorPeopleQuery())
      ->setViewer(PhabricatorUser::getOmnipotentUser())
      ->withPHIDs($PHIDs)
      ->execute();

    foreach ($users as $user) {
      $this->PHIDCache[$user->getPHID()] = $user;
    }
    return $users;
  }
}