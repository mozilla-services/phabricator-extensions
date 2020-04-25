<?php


class RevisionCreatedHeuristic {
  /** @var bool */
  private $authorIsPhabBot;
  /** @var bool */
  private $includesViewPolicyChange;
  /** @var bool */
  private $includesEditPolicyChange;
  /** @var bool */
  private $includesRevisionRequestChange;
  /** @var bool */
  private $includesCoreEdgeChange;

  /**
   */
  public function __construct() {
    $this->authorIsPhabBot = false;
    $this->includesViewPolicyChange = false;
    $this->includesEditPolicyChange = false;
    $this->includesRevisionRequestChange = false;
    $this->includesCoreEdgeChange = false;
  }

  public function authorIsPhabBot(): void {
    $this->authorIsPhabBot = true;
  }

  public function includesViewPolicyChange(): void {
    $this->includesViewPolicyChange = true;
  }

  public function includesEditPolicyChange(): void {
    $this->includesEditPolicyChange = true;
  }

  public function includesRevisionRequestChange(): void {
    $this->includesRevisionRequestChange = true;
  }

  public function includesCoreEdgeChange(): void {
    $this->includesCoreEdgeChange = true;
  }

  public function check() {
    return $this->authorIsPhabBot &&
      $this->includesViewPolicyChange &&
      $this->includesEditPolicyChange &&
      $this->includesRevisionRequestChange &&
      $this->includesCoreEdgeChange;
  }


}