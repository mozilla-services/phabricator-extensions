<?php


class TransactionList {
  /** @var DifferentialTransaction[] */
  private array $transactions;

  /**
   * @param DifferentialTransaction[] $transactions
   */
  public function __construct(array $transactions) {
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

  public function getTransactionWithType($type): ?DifferentialTransaction {
    $matching = $this->getAllTransactionsWithType($type);

    if (count($matching) > 1) {
      throw new RuntimeException('Too many transactions match type');
    } else if (count($matching) == 0) {
      return null;
    }
    return current($matching);
  }

  /**
   * @return DifferentialTransaction[]
   */
  public function getAllTransactionsWithType(string $type): array
  {
    return array_filter($this->transactions, function($transaction) use ($type) {
      return $transaction->getTransactionType() == $type;
    });
  }

  public function containsType(string $type): bool
  {
    return !empty(array_filter($this->transactions, function ($transaction) use ($type) {
      return $transaction->getTransactionType() == $type;
    }));
  }
}