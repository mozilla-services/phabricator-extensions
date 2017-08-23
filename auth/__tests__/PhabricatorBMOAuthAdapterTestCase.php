<?php

final class PhabricatorBMOAuthAdapterTestCase extends PhabricatorTestCase {

  public function testBugzillaNameParserFindsNick() {
    $adapter = new PhabricatorBMOAuthAdapter();

    $names = array(
      array(
        'full' => 'David Walsh :davidwalsh',
        'nick' => 'davidwalsh',
        'real' => 'David Walsh'
      ),
      array(
        'full' => 'David Walsh [:davidwalsh]',
        'nick' => 'davidwalsh',
        'real' => 'David Walsh'
      ),
      array(
        'full' => 'David Walsh (:davidwalsh)',
        'nick' => 'davidwalsh',
        'real' => 'David Walsh'
      ),
      array(
        'full' => ':davidwalsh',
        'nick' => 'davidwalsh',
        'real' => ':davidwalsh'
      ),
      array(
        'full' => 'Yes Nopé [:ynope]',
        'nick' => 'ynope',
        'real' => 'Yes Nopé'
      )
    );

    foreach($names as $name) {
      $parsed = $adapter->parseBugzillaNames($name['full']);
      $this->assertEqual(
        $name['nick'],
        $parse['nick'],
        pht('Validation that parseBugzillaNames finds nick and name %s', $name['full'])
      );
    }
  }

}
