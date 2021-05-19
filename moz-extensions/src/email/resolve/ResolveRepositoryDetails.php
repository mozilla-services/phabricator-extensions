<?php


class ResolveRepositoryDetails
{
  public function resolveRepoName(string $PHID): string {
    $repository = id(new PhabricatorRepositoryQuery())
      ->setViewer(PhabricatorUser::getOmnipotentUser())
      ->withPHIDs(array($PHID))
      ->executeOne();
    return $repository->getName();
  }
}