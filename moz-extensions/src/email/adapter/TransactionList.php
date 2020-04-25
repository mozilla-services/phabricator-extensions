<?php


class TransactionList {
  private $transactions;

  /**
   * @param $transactions
   */
  public function __construct($transactions) {
    $this->transactions = $transactions;
  }

  public function getAnyTransaction(): DifferentialTransaction {
    return current($this->transactions);
  }

  public function getFirstTransaction(): DifferentialTransaction {
    $lowest = null;
    foreach ($this->transactions as $transaction) {
      if (!$lowest || $transaction->getID() < $lowest->getID()) {
        $lowest = $transaction;
      }
    }
    return $lowest;
  }

  public function getTransactionWithType($type) {
    $matching = $this->getAllTransactionsWithType($type);

    if (count($matching) > 1) {
      throw new RuntimeException('Too many transactions match type');
    } else if (count($matching) == 0) {
      return null;
    }
    return current($matching);
  }

  public function getAllTransactionsWithType($type) {
    return array_filter($this->transactions, function($transaction) use ($type) {
      return $transaction->getTransactionType() == $type;
    });
  }

  public function containsType(string $type) {
    return !empty(array_filter($this->transactions, function ($transaction) use ($type) {
      return $transaction->getTransactionType() == $type;
    }));
  }
}