<?php

final class PhabricatorBMOAuthAdapterTestCase extends PhabricatorTestCase {

  public function testBugzillaNameParserFindsNick() {
    $adapter = new PhabricatorBMOAuthAdapter();

    $nick = 'davidwalsh';
    $name = sprintf('David Walsh :%s', $nick);

    $this->assertEqual(
      $adapter->parseBugzillaNick($name),
      $nick,
      pht('Validation that parseBugzillaNick finds nick %s', $nick)
    );
  }

}
