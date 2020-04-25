<?php


class EmailRecipient {
  /** @var string */
  public $email;
  /** @var string */
  public $timezoneOffset;
  /** @var bool */
  public $isActor;

  /**
   * @param string $email
   * @param DateTimeZone $timezone
   * @param bool $isActor
   */
  public function __construct(string $email, DateTimeZone $timezone, bool $isActor) {
    $this->email = $email;
    $this->timezoneOffset = $timezone->getOffset(new DateTime(null, $timezone));
    $this->isActor = $isActor;
  }

  public static function from(PhabricatorUser $user, string $actorEmail) {
    $preferences = (new PhabricatorUserPreferencesQuery())
      ->setViewer(PhabricatorUser::getOmnipotentUser())
      ->withUserPHIDs([$user->getPHID()])
      ->executeOne();

    $timezone = new DateTimeZone('UTC');
    if ($preferences && $preferences->getPreference('timezone')) {
      $timezone = new DateTimeZone($preferences->getPreference('timezone'));
    }

    $email = $user->loadPrimaryEmailAddress();
    return new EmailRecipient($email, $timezone, $email == $actorEmail);
  }
}