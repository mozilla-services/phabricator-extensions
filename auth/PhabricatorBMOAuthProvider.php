<?php

final class PhabricatorBMOAuthProvider extends PhabricatorAuthProvider {

  // Values we can safely hardcode
  const ADAPTER_TYPE = 'bmo';
  const TOKEN_TYPE = 'bmo:auth:request';
  const GENERIC_ERROR = 'Phabricator to Bugzilla login has encountered an error.';

  // This name is passed to BMO for the API key generation and confirmation page
  const APP_NAME = 'MozPhabricator';

  // Represents the length of the temporary auth tokens
  const TRANSACTION_CODE_LENGTH = 32;

  // Logging error key type
  const LOGGING_TYPE = 'mozphab.auth.provider';

  // Need to add this to avoid error during auth addition activation
  protected $adapter;
  protected $providerConfig;

  /*
   *  Required provider setup methods
   */

  public function getProviderName() {
    return pht('BMO Auth Delegation');
  }

  public function getDescriptionForCreate() {
    return pht(
      'Configure a connection to bugzilla.mozilla.org so that users may use '.
      'BMO credentials to log in to Phabricator.'
    );
  }

  /*
   *  Methods for getting additional config information for this provider
   *  These config options are seen on the "Edit Auth Provider" form in admin
   */

  public function getConfigurationHelp() {
    return pht(
      'This extension was written by the Mozilla Conduit team.  Please '.
      'contact someone in the #phabricator channel if you have '.
      'config questions or consult the Mana page:  '.
      'https://mana.mozilla.org/wiki/display/SVCOPS/Configuring+the+BMO+Auth+extension+in+Phabricator'
    );
  }

  public function getAdapter() {
    if (!$this->adapter) {
      $this->adapter = $adapter = new PhabricatorBMOAuthAdapter();
      $this->configureAdapter($adapter);
    }
    return $this->adapter;
  }

  public function getConfig() {
    if (!$this->providerConfig) {
      $this->providerConfig = $this->getProviderConfig();
    }
    return $this->providerConfig;
  }

  public function configureAdapter($adapter) {
    $config = $this->getConfig();

    $adapter->setAdapterType(self::ADAPTER_TYPE);
    $adapter->setAdapterDomain($config->getProviderDomain());
    $adapter->setAuthenticateURI(
      id(new PhutilURI(PhabricatorEnv::getEnvConfig('bugzilla.url')))
        ->setPath('/auth.cgi')
        ->setQueryParam('description', self::APP_NAME)
    );
  }

  /*
   *  Methods to guide the user through the auth and login processes
   */

  protected function renderLoginForm(AphrontRequest $request, $mode) {
    require_celerity_resource('bmo-auth-css');
    require_celerity_resource('bmo-auth-js');

    $adapter = $this->getAdapter();

    // This CSRF token is passed to BMO and is then passed back during the
    // back channel post and login redirect to tie the user to the
    // original login button click
    $csrf = $this->getAuthCSRFCode($request);

    // The CSRF here is alphanumeric
    $callback_uri = PhabricatorEnv::getURI($this->getLoginURI()).'?secret='.$csrf;
    $bmo_login_uri = id(new PhutilURI($adapter->getAuthenticateURI()))
      ->setQueryParam('callback', $callback_uri);

    return $this->renderStandardLoginButton($request, $mode, array(
      'method' => 'GET',
      'uri' => (string) $bmo_login_uri,
      'sigil' => 'bmo-login'
    ));
  }

  public function processLoginRequest(
    PhabricatorAuthLoginController $controller) {
    $request = $controller->getRequest();

    // When Bugzilla sends us a "back channel" POST during registration...
    if ($request->isHTTPPost()) {
      return $this->processLoginRequestBackChannelPost($controller, $request);
    }

    // The last step in Bugzilla's verification process
    return $this->processLoginRequestConfirmationGet($controller, $request);
  }

  private function processLoginRequestBackChannelPost($controller, $request) {
    $config = $this->getConfig();
    $account = null;
    $response = null;

    // Get the `client_api_key` and `client_api_login` from Bugzilla's POST data
    $raw_input = PhabricatorStartup::getRawInput();
    $post_info = array();
    try {
      $post_info = phutil_json_decode($raw_input);
    }
    catch(Exception $ex) {
      $this->throwException(
        'Phabricator BMO Authentication failed due to invalid JSON from Bugzilla.',
        array('raw_input' => $raw_input)
      );
    }

    // Throw exception if either key is not provided by Bugzilla
    if(!isset($post_info['client_api_key']) || !isset($post_info['client_api_login'])) {
      $this->throwException(
        'No client_api_key or client_api_login provided by Bugzilla.',
        array('raw_input' => $raw_input)
      );
    }

    // Verify that we've received the a CSRF back from BMO
    $csrf = $request->getStr('secret');
    if(!strlen($csrf)) {
      $this->throwException('No CSRF was provided by Bugzilla in the URL.');
    }

    // Generate a transaction code which we'll receive back from Bugzilla
    // To confirm the API and Client Login information which we saved
    $trans_code = $this->generateAuthToken();

    // Create a temporary auth token to save the user info JSON provided
    // by the POST from Bugzilla.  Implicitly validates CSRF.
    $unguarded = AphrontWriteGuard::beginScopedUnguardedWrites();
    id(new PhabricatorAuthTemporaryToken())
      ->setTokenResource($trans_code)
      ->setTokenCode($csrf)
      ->setTokenType(self::TOKEN_TYPE)
      ->setTokenExpires(time() + phutil_units('10 minutes in seconds'))
      ->setTemporaryTokenProperty('api_key', $post_info['client_api_key'])
      ->setTemporaryTokenProperty('client_login', $post_info['client_api_login'])
      ->save();
    unset($unguarded);

    // Return a JSON object with a `result` key
    // This result represents the transaction key we'll use to look up
    // the JSON information in the next phase of requests
    $json_obj = array('result' => $trans_code);

    // Render the response JSON
    $response = id(new AphrontJSONResponse())
      ->setAddJSONShield(false)
      ->setHTTPResponseCode(200)
      ->setContent($json_obj);
    return array($account, $response);
  }

  private function processLoginRequestConfirmationGet($controller, $request) {
    $response = null;
    $csrf = $request->getStr('secret');

    // Verify with CSRF as an additional security measure
    $this->verifyAuthCSRFCode($request, $csrf);

    // Match result token and client_api_login to find client_api_key
    $provided_trans_code = $request->getStr('callback_result');
    $provided_api_login = $request->getStr('client_api_login');

    $token = id(new PhabricatorAuthTemporaryTokenQuery())
      ->setViewer(PhabricatorUser::getOmnipotentUser())
      ->withTokenResources(array($provided_trans_code))
      ->withTokenCodes(array($csrf))
      ->withTokenTypes(array(self::TOKEN_TYPE))
      ->withExpired(false)
      ->executeOne();

    // No token means we've received invalid information from Bugzilla
    if(!$token) {
      $this->throwException(
        'No temporary token found for this transaction code.',
        array(
          'provided_transaction_code' => $provided_trans_code,
          'csrf' => $csrf
        )
      );
    }

    // Compare result token and client_api_login to original response
    $token_props = $token->getProperties();
    if($token_props['client_login'] != $provided_api_login) {
      $this->throwException(
        'Token\'s API Login does not match Bugzilla API Login.',
        array(
          'token_client_login' => $token_props['client_login'],
          'provided_api_login' => $provided_api_login
        )
      );
    }

    // Call BMO Who Am I REST resource to validate API Key (X-Bugzilla-API-Key)
    $adapter = $this->getAdapter();
    $config = $this->getConfig();
    $api_key = $token_props['api_key'];

    $future_uri = id(new PhutilURI(PhabricatorEnv::getEnvConfig('bugzilla.url')))
      ->setPath('/rest/whoami');

    $future = id(new HTTPSFuture((string) $future_uri))
      ->setMethod('GET')
      ->addHeader('X-Bugzilla-API-Key', $api_key)
      ->addHeader('Accept', 'application/json')
      ->setTimeout(PhabricatorEnv::getEnvConfig('bugzilla.timeout'));

    // Resolve the async HTTPSFuture request and extract JSON body
    $whoami_body = '';
    try {
      list($whoami_body) = $future->resolvex();
    } catch (HTTPFutureResponseStatus $ex) {
      $this->throwException(
        'Bugzilla WhoAmI request failed to resolve.',
        array('token_client_login' => $token_props['client_login'])
      );
    }

    // Parse the user information provided by BMO
    $user_json = array();
    try {
      $user_json = phutil_json_decode($whoami_body);
    }
    catch(Exception $e) {
      $this->throwException(
        'JSON from Bugzilla WhoAmI could not be parsed.',
        array('body' => $whoami_body)
      );
    }

    // If there's no "id" key in the JSON, we know something is wrong
    if(!isset($user_json['id'])) {
      $this->throwException(
        'No user ID was provided by Bugzilla.',
        array('body' => $whoami_body)
      );
    }

    // Clean up! Delete temporary token used for this login
    $unguarded = AphrontWriteGuard::beginScopedUnguardedWrites();
    $token->delete();
    unset($unguarded);

    // Provide the adapter with massaged user data
    $this->setAccountDetails($user_json);

    // Create or load the user account and refresh the page
    MozLogger::log(
      pht('Loading or creating account for BMO id: %s', $user_json['id']),
      self::LOGGING_TYPE
    );

    /*
      NOTE:  This updates a user's email address in "user_externalaccount"
      but does not update their email within phabricator's user profile page
    */
    $account = $this->loadOrCreateAccount($user_json['id']);

    return array($account, $response);
  }

  private function setAccountDetails($user_json) {
    $adapter = $this->getAdapter();

    // Save the user information, then creating or loading the account
    // Example: Array ( [real_name] => Vagrant User [name] => vagrant@bmo-web.vm [id] => 1 )
    $real_name = $user_json['real_name'];
    $bugzilla_nick = $adapter->parseBugzillaNick($real_name);
    if($bugzilla_nick) {
      $real_name = str_replace(':'.$bugzilla_nick, '', $real_name);
      $real_name = trim(str_replace(array('()', '[]'), '', $real_name));
    }

    $adapter->setAccountName($bugzilla_nick ?: str_replace(' ', '', $real_name));
    $adapter->setAccountRealName($real_name);
    $adapter->setAccountID($user_json['id']);
    $adapter->setAccountEmail($user_json['name']);
  }

  public function throwException($text, $fields = array()) {
    MozLogger::log($text, self::LOGGING_TYPE, array('Fields' => $fields));
    throw new Exception(self::GENERIC_ERROR);
  }

  public function generateAuthToken() {
    return Filesystem::readRandomCharacters(self::TRANSACTION_CODE_LENGTH);
  }

  public function getProviderDomain() {
    list($bmo_protocol, $bmo_host) = explode('://', PhabricatorEnv::getEnvConfig('bugzilla.url'));
    return $bmo_host;
  }
}
