<?php


class EmailAPIAuthorization {
  /**
   * @param PhabricatorUser $user
   * @throws ConduitException
   */
  public static function assert(PhabricatorUser $user) {
    if (PhabricatorEnv::getEnvConfig('bugzilla.url') == "http://bmo.test") {
      // When running in the local development environment, allow using the API as any user
      return;
    }

    if ($user->getUserName() == 'email-bot' && $user->getIsSystemAgent() && $user->getIsApproved()) {
      return;
    }

    throw (new ConduitException('ERR-INVALID-AUTH'))
      ->setErrorDescription('Only the "email-bot" user can use this endpoint');
  }
}