<?php

class RevisionAdminViewPolicyEnforcerAction extends HeraldAction {

  const ACTIONCONST = 'differential.revision.adminview';
  const DO_POLICY = 'do.policy';

  pubLic function getHeraldActionName() {
    return pht('Visible only to admins');
  }

  public function supportsObject($object) {
    return ($object instanceof DifferentialRevision);
  }

  public function getActionGroupKey() {
    return HeraldApplicationActionGroup::ACTIONGROUPKEY;
  }

  public function supportsRuleType($rule_type) {
    return ($rule_type == HeraldRuleTypeConfig::RULE_TYPE_GLOBAL);
  }

  public function applyEffect($object, HeraldEffect $effect) {
    $object->setViewPolicy(PhabricatorPolicies::POLICY_ADMIN)
           ->save();

    $adapter = $this->getAdapter();

    $adapter->queueTransaction(
      id(new DifferentialTransaction())
        ->setTransactionType(PhabricatorTransactions::TYPE_VIEW_POLICY)
        ->setNewValue(PhabricatorPolicies::POLICY_ADMIN));

    return new HeraldApplyTranscript(
      $effect,
      true,
      pht('Set revision view policy to: admins'));

    $this->logEffect(self::DO_POLICY);
  }

  public function renderActionDescription($value) {
    return pht('Make revision visible only to administrators');
  }

  protected function getActionEffectMap() {
    return array(
      self::DO_POLICY => array(
        'icon' => 'fa-pencil',
        'color' => 'green',
        'name' => pht('Changed revision view policy'),
      ),
    );
  }  

  protected function renderActionEffectDescription($type, $data) {
    switch ($type) {
      case self::DO_POLICY:
        return pht('Changed view policy to "admins only".');
    }
  }
}
