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

  public static function from(PhabricatorUser $user, string $actorEmail): ?EmailRecipient {
    if ($user->getIsDisabled()) {
      // Don't send emails to disabled users
      return null;
    }

    $preferences = (new PhabricatorUserPreferencesQuery())
      ->setViewer(PhabricatorUser::getOmnipotentUser())
      ->withUserPHIDs([$user->getPHID()])
      ->executeOne();

    if (!$preferences) {
      // User doesn't have any non-default preferences, and the default email preference is not to use
      // these new Mozilla emails, so don't count them as a recipient
      return null;
    }

    $timezonePref = $preferences->getPreference('timezone');
    if ($timezonePref) {
      $timezone = new DateTimeZone($timezonePref);
    } else {
      $timezone = new DateTimeZone('UTC');
    }

    $mailPref = $preferences->getSettingValue(PhabricatorEmailNotificationsSetting::SETTINGKEY);
    if ($mailPref != PhabricatorEmailNotificationsSetting::VALUE_MOZILLA_MAIL) {
      // This user doesn't want the new emails, so don't consider them a recipient
      return null;
    }

    $email = $user->loadPrimaryEmailAddress();
    return new EmailRecipient($email, $timezone, $email == $actorEmail);
  }
}