<?php


class PhabricatorReviewerStore {
  /** @var PhabricatorUserStore */
  private $userStore;

  /**
   * @param PhabricatorUserStore $userStore
   */
  public function __construct(PhabricatorUserStore $userStore) {
    $this->userStore = $userStore;
  }

  /**
   * @param string $PHID
   * @return PhabricatorReviewer
   */
  public function findReviewer(string $PHID): PhabricatorReviewer {
    if (substr($PHID, 0, strlen('PHID-PROJ')) === "PHID-PROJ") {
      $project = (new PhabricatorProjectQuery())
        ->setViewer(PhabricatorUser::getOmnipotentUser())
        ->needMembers(true)
        ->withPHIDs([$PHID])
        ->executeOne();
      $users = array_values($this->userStore->queryAll($project->getMemberPHIDs()));
      return new GroupPhabricatorReviewer($project, $users);
    } else {
      // PHID type must be "PHID-USER" if it's not "PHID-PROJ"
      return new UserPhabricatorReviewer($this->userStore->find($PHID));
    }
  }
}