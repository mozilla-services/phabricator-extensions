<?php

final class PhabricatorBMOAuthProviderTestCase extends PhabricatorTestCase {

  public function testThrowExceptionShowsGenericErrorWhenDebugIsOff() {
    $provider = new PhabricatorBMOAuthProvider();
    $config = $provider->getDefaultProviderConfig();
    $config->setProperty(PhabricatorBMOAuthProvider::CONFIG_KEY_DEBUG_MODE, 0);
    $provider->attachProviderConfig($config);

    try {
      $provider->throwException('Blah');
    }
    catch(Exception $ex) {
      $this->assertEqual(PhabricatorBMOAuthProvider::GENERIC_ERROR, $ex->getMessage());
    }
  }

}
