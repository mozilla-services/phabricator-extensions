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

  public function getAccountMFA() {
    return $this->getOAuthAccountData('mfa');
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
      $result = phutil_json_decode($body);
    } catch (PhutilJSONParserException $ex) {
      throw new PhutilProxyException(
        pht('Expected valid JSON response from BMO account data request.'),
        $ex);
    }

    // // If mfa is disabled in Bugzilla, do not allow login
    // if (
    //   PhabricatorEnv::getEnvConfig('bugzilla.require_mfa')
    //   && (!isset($result['mfa']) || !$result['mfa'])
    // ) {
    //   $bugzilla_url = PhabricatorEnv::getEnvConfig('bugzilla.url') . '/userprefs.cgi?tab=mfa';
    //   $error_content = phutil_tag(
    //     'div',
    //     array(),
    //     phutil_safe_html(
    //       'Login using Bugzilla requires multi-factor authentication ' .
    //       'to be enabled in Bugzilla. Please enable multi-factor authentication ' .
    //       'in your Bugzilla ' . phutil_tag('a', array('href' => $bugzilla_url), pht('preferences')) .
    //       ' and try again.'));
    //   $dialog = id(new AphrontDialogView())
    //     ->setTitle(pht('Bugzilla MFA Required'))
    //     ->appendChild($error_content)
    //     ->addCancelButton('/', pht('Continue'));
    //   return id(new AphrontDialogResponse())->setDialog($dialog);
    // }

    return $result;
  }

}
