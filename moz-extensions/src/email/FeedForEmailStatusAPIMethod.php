<?php

final class FeedForEmailStatusAPIMethod extends ConduitAPIMethod {
  public function getAPIMethodName() {
    return 'feed.for_email.status';
  }

  public function getMethodDescription() {
    return 'Provides the "query key" of the most recent feed story';
  }

  protected function defineParamTypes() {
    return array();
  }

  protected function defineReturnType() {
    return 'str';
  }

  public function getMethodStatus() {
    return self::METHOD_STATUS_UNSTABLE;
  }

  private function authorize(PhabricatorUser $user) {
    return $user->getUserName() == 'email-bot' && $user->getIsSystemAgent() && $user->getIsApproved();
  }

  protected function execute(ConduitAPIRequest $request) {
    if (!$this->authorize($request->getUser())) {
      throw (new ConduitException('ERR-INVALID-AUTH'))
        ->setErrorDescription('Only the "email-bot" user can use this endpoint');
    }

    $rawStory = (new PhabricatorFeedQuery())
      ->setOrder('newest')
      ->setViewer(PhabricatorUser::getOmnipotentUser())
      ->setLimit(1)
      ->executeOne();

    return $rawStory->getStoryData()->getChronologicalKey();
  }
}

