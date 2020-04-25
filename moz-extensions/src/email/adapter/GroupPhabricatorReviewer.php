<?php


class GroupPhabricatorReviewer implements PhabricatorReviewer {
  /** @var PhabricatorProject */
  private $rawProject;
  /** @var PhabricatorUser */
  private $rawUsers;

  /**
   * @param PhabricatorProject $rawProject
   * @param PhabricatorUser[] $rawUsers
   */
  public function __construct(PhabricatorProject $rawProject, array $rawUsers) {
    $this->rawProject = $rawProject;
    $this->rawUsers = $rawUsers;
  }

  public function name(): string {
    return $this->rawProject->getDisplayName();
  }

  public function toRecipients(string $actorEmail): array {
    return array_values(array_filter(array_map(function($user) use ($actorEmail) {
      return EmailRecipient::from($user, $actorEmail);
    }, $this->rawUsers)));
  }
}