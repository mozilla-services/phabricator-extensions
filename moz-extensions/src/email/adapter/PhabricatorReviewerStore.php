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
      // This is a group reviewer
      $project = (new PhabricatorProjectQuery())
        ->setViewer(PhabricatorUser::getOmnipotentUser())
        ->needMembers(true)
        ->withPHIDs([$PHID])
        ->executeOne();
      $users = array_values($this->userStore->queryAll($project->getMemberPHIDs()));
      return new GroupPhabricatorReviewer($project->getDisplayName(), $users);
    } else if (substr($PHID, 0, strlen('PHID-OPKG')) == 'PHID-OPKG') {
      // This is a "code owner" reviewer
      $package = (new PhabricatorOwnersPackageQuery())
        ->setViewer(PhabricatorUser::getOmnipotentUser())
        ->withPHIDs([$PHID])
        ->executeOne();
      $users = array_values($this->userStore->queryAll($package->getOwnerPHIDs()));
      return new GroupPhabricatorReviewer($package->getName(), $users);
    } else {
      // PHID type must be "PHID-USER" if it's not "PHID-PROJ".
      // So, this is a single user reviewer
      return new UserPhabricatorReviewer($this->userStore->find($PHID));
    }
  }
}