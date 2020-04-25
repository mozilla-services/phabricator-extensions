<?php


class PhabricatorStory {
  /** @var EventKind */
  public $eventKind;
  /** @var TransactionList */
  public $transactions;
  /** @var DifferentialRevision */
  public $revision;
  /** @var PhabricatorUser */
  public $actor;
  /** @var string */
  public $key;

  /**
   * @param EventKind $eventKind
   * @param TransactionList $transactions
   * @param DifferentialRevision $revision
   * @param PhabricatorUser $actor
   * @param string $key
   */
  public function __construct(EventKind $eventKind, TransactionList $transactions, DifferentialRevision $revision, PhabricatorUser $actor, string $key) {
    $this->eventKind = $eventKind;
    $this->transactions = $transactions;
    $this->revision = $revision;
    $this->actor = $actor;
    $this->key = $key;
  }

  public function getTransactionLink(): string {
    $anyTransaction = $this->transactions->getFirstTransaction();
    $link = '/' . $this->revision->getMonogram() . '#' . $anyTransaction->getID();
    return PhabricatorEnv::getProductionURI($link);
  }

  public static function queryStories(PhabricatorUserStore $userStore, int $limit, ?int $sinceKey): StoryQueryResult {
    $pager = (new AphrontCursorPagerView())
      ->setPageSize($limit);

    if ($sinceKey) {
      $pager->setAfterID($sinceKey);
    }

    $rawStories = (new PhabricatorFeedQuery())
      ->setOrder('oldest')
      ->setViewer(PhabricatorUser::getOmnipotentUser())
      ->executeWithCursorPager($pager);

    $lastStory = end($rawStories);
    if ($lastStory) {
      $lastKey = $lastStory->getStoryData()->getChronologicalKey();
    } else {
      $lastKey = $sinceKey;
    }

    /** @var PhabricatorStoryBuilder[] $builders */
    $builders = [];
    $revisionPHIDs = [];
    foreach ($rawStories as $rawStory) {
      $storyData = $rawStory->getStoryData()->getStoryData();
      if (strpos($storyData['objectPHID'], 'PHID-DREV') !== 0) {
        continue; // We only email about events about revisions
      }

      $transactions = [];
      foreach ($storyData['transactionPHIDs'] as $phid) {
        $transactions[] = $rawStory->getObject($phid);
      }

      $eventKind = EventKind::mainKind($transactions, $userStore);
      if (!$eventKind) {
        // There's nothing to email about
        continue;
      }

      $builder = new PhabricatorStoryBuilder($eventKind, $transactions, $rawStory->getStoryData()->getChronologicalKey());
      $revisionPHIDs[] = $builder->revisionPHID;
      $builders[] = $builder;
    }

    if (!$builders) {
      // No stories were relevant
      return new StoryQueryResult($lastKey, []);
    }

    $rawRevisions = (new DifferentialRevisionQuery())
      ->setViewer(PhabricatorUser::getOmnipotentUser())
      ->withPHIDs($revisionPHIDs)
      ->needReviewers(true)
      ->needActiveDiffs(true)
      ->execute();

    $actorPHIDs = [];
    foreach ($builders as $builder) {
      foreach ($rawRevisions as $rawRevision) {
        if ($builder->revisionPHID == $rawRevision->getPHID()) {
          $actorPHID = $builder->eventKind->findActor($builder->transactions, $rawRevision);
          $builder->associateRevision($rawRevision, $actorPHID);
          $actorPHIDs[] = $actorPHID;
          break;
        }
      }
    }

    $rawUsers = $userStore->queryAll($actorPHIDs);
    $stories = [];
    foreach ($builders as $builder) {
      foreach ($rawUsers as $rawUser) {
        if ($builder->actorPHID == $rawUser->getPHID()) {
          $builder->associateActor($rawUser);
          $stories[] = $builder->asStory();
          break;
        }
      }
    }

    return new StoryQueryResult($lastKey, $stories);
  }
}