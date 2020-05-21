<?php

/**
 * Authentication adapter for Github OAuth2.
 */
final class PhutilBMOAuthAdapter extends PhutilOAuthAuthAdapter {

  public function getAdapterType() {
    return 'bmo';
  }

  public function getAdapterDomain() {
    return 'bugzilla.mozilla.org';
  }

  public function getAccountID() {
    return $this->getOAuthAccountData('id');
  }

  public function getAccountEmail() {
    return $this->getOAuthAccountData('login');
  }

  public function getAccountName() {
    return $this->getOAuthAccountData('nick');
  }

  public function getAccountRealName() {
    return $this->getOAuthAccountData('name');
  }

  protected function getAuthenticateBaseURI() {
    $url = PhabricatorEnv::getEnvConfig('bugzilla.url');
    return $url . '/oauth/authorize';
  }

  protected function getTokenBaseURI() {
    $url = PhabricatorEnv::getEnvConfig('bugzilla.url');
    return $url . '/oauth/access_token';
  }

  public function getScope() {
    return 'user:read';
  }

  public function getExtraAuthenticateParameters() {
    return array(
      'response_type' => 'code',
    );
  }

  public function getExtraTokenParameters() {
    return array(
      'grant_type' => 'authorization_code',
    );
  }

  protected function loadOAuthAccountData() {
    $uri = new PhutilURI(PhabricatorEnv::getEnvConfig('bugzilla.url') . '/api/user/profile');

    $future = new HTTPSFuture($uri);

    // NOTE: GitHub requires a User-Agent string.
    $future->addHeader('User-Agent', __CLASS__);

    // See T13485. Circa early 2020, GitHub has deprecated use of the
    // "access_token" URI parameter.
    $token_header = sprintf('Bearer %s', $this->getAccessToken());
    $future->addHeader('Authorization', $token_header);

    list($body) = $future->resolvex();

    try {
      return phutil_json_decode($body);
    } catch (PhutilJSONParserException $ex) {
      throw new PhutilProxyException(
        pht('Expected valid JSON response from BMO account data request.'),
        $ex);
    }
  }

}
