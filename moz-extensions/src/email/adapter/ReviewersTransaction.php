<?php


class ReviewersTransaction {
  /** @var DifferentialTransaction */
  private $oldValue;
  private $newValue;

  /**
   * @param DifferentialTransaction $tx
   */
  public function __construct(DifferentialTransaction $tx) {
    $this->oldValue = $tx->getOldValue();
    $this->newValue = $tx->getNewValue();
  }

  public function getAllUsers() {
    return array_unique(array_merge(array_keys($this->oldValue), array_keys($this->newValue)));
  }

  public function getReviewerStatus(string $userPHID) {
    // The extra "?? null" at the end is to suppress the PHP "undefined array key" error
    $rawStatus = $this->newValue[$userPHID] ?? $this->oldValue[$userPHID] ?? null; // get first non-null value;
    if (is_null($rawStatus)) {
      return null;
    }

    if ($rawStatus == 'accepted') {
      $status = 'accepted';
    } else if ($rawStatus == 'rejected') {
      $status = 'requested-changes';
    } else if ($rawStatus == 'blocking') {
      $status = 'blocking';
    } else {
      $status = 'unreviewed';
    }

    return $status;
  }

  public function getReviewerChange(string $userPHID) {
    $old = $this->oldValue[$userPHID] ?? null;
    $new = $this->newValue[$userPHID] ?? null;

    if ($old and !$new) {
      $change = 'removed';
    } else if (!$old and $new) {
      $change = 'added';
    } else {
      $change = 'no-change';
    }

    return $change;
  }

  public function isOnlyNonblockingUnreviewed() {
    return count(array_filter(array_values($this->newValue), function($status) {
      return $status != 'added';
    })) == 0;
  }
}