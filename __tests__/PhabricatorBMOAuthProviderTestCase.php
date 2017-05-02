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
      $this->assertEqual(
        PhabricatorBMOAuthProvider::GENERIC_ERROR,
        $ex->getMessage(),
        pht('Validate that a generic exception is shown when debug is off')
      );
    }
  }

  public function testUniqueAuthTokenGenerated() {
    $provider = new PhabricatorBMOAuthProvider();
    $config = $provider->getDefaultProviderConfig();
    $config->setProperty(
      PhabricatorBMOAuthProvider::CONFIG_KEY_TRANSACTION_CODE_LENGTH, 32
    );
    $provider->attachProviderConfig($config);


    $tokens = array();
    $num_tokens = 100000;
    for($x = 0; $x < $num_tokens; $x++) {
      $tokens[] = $provider->generateAuthToken();
    }

    $this->assertEqual(
      $num_tokens, count(array_unique($tokens)),
      pht('Validate that %s unique tokens are generated', $num_tokens)
    );
  }

}
