<?php


class ResolveCodeChange {
  /** @var TransactionList */
  public $transactions;
  /** @var DifferentialRevision */
  public $rawRevision;
  /** @var PhabricatorDiffStore */
  public $diffStore;

  /**
   * @param TransactionList $transactions
   * @param DifferentialRevision $rawRevision
   * @param PhabricatorDiffStore $diffStore
   */
  public function __construct(TransactionList $transactions, DifferentialRevision $rawRevision, PhabricatorDiffStore $diffStore) {
    $this->transactions = $transactions;
    $this->rawRevision = $rawRevision;
    $this->diffStore = $diffStore;
  }

  /**
   * @return EmailAffectedFile[]
   */
  public function resolveAffectedFiles(): array {
    $diff = $this->diffStore->find($this->rawRevision->getActiveDiffPHID());
    $changesets = $diff->getChangesets();
    $affectedFiles = [];
    foreach($changesets as $changeset) {
      $affectedFiles[] = EmailAffectedFile::from($changeset);
    }
    return $affectedFiles;
  }

  public function resolveNewChangesLink(): string {
    $updateTx = $this->transactions->getTransactionWithType('differential:update');
    $original = $this->diffStore->find($updateTx->getOldValue());
    $oldId = $original->getID();
    $current = $this->rawRevision->getActiveDiff();
    $newId = $current->getID();
    return PhabricatorEnv::getProductionURI('/'.$this->rawRevision->getMonogram().'?vs='.$oldId.'&id='.$newId);
  }
}