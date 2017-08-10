<?php

final class PhabricatorBugzillaConfigOptions
  extends PhabricatorApplicationConfigOptions {

  public function getName() {
    return pht('Bugzilla');
  }

  public function getDescription() {
    return pht('Configure Bugzilla Settings.');
  }

  public function getIcon() {
    return 'fa-cog';
  }

  public function getGroup() {
    return 'apps';
  }

  public function getOptions() {
    return array(
      $this->newOption(
        'bugzilla.url',
        'string',
        'https://bugzilla.mozilla.org')
        ->setDescription(pht('Full URL for the Bugzilla server.')),
      $this->newOption(
        'bugzilla.automation_user',
        'string',
        'phab-bot@bmo.tld')
        ->setDescription(pht('Automation Username on Bugzilla.')),
      $this->newOption(
        'bugzilla.automation_api_key',
        'string',
        false)
        ->setDescription(pht('Automation User API Key on Bugzilla.')),
    );
  }

}
