<?php

final class PhabricatorBMOAuthProvider extends PhabricatorAuthProvider {

  // Values we can safely hardcode
  const ADAPTER_TYPE = 'bmo';
  const TOKEN_TYPE = 'bmo:auth:request';
  const GENERIC_ERROR = 'Phabricator to Bugzilla login has encountered an error.';

  // Keys for database -> provider config
  const CONFIG_KEY_DEBUG_MODE = 'debug_mode';
  const CONFIG_KEY_APP_NAME = 'app_name';
  const CONFIG_KEY_TRANSACTION_CODE_LENGTH = 'transaction_code_length';

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
      'config questions.'
    );
  }

  public function getDefaultProviderConfig() {
    return parent::getDefaultProviderConfig()
      ->setProperty(self::CONFIG_KEY_DEBUG_MODE, 0)
      ->setProperty(self::CONFIG_KEY_APP_NAME, 'MozPhabricator')
      ->setProperty(self::CONFIG_KEY_TRANSACTION_CODE_LENGTH, 32);
  }

  public function extendEditForm(
    AphrontRequest $request,
    AphrontFormView $form,
    array $values,
    array $issues) {
      $form
        ->appendChild(
          id(new AphrontFormCheckboxControl())
            ->addCheckbox(
              self::CONFIG_KEY_DEBUG_MODE,
              '1',
              hsprintf(
                '<strong>%s</strong> <strong style="color:#c0392b;">%s</strong>',
                pht('Debug Mode'),
                pht('ONLY USE DURING DEVELOPMENT')
              ),
              (isset($values[self::CONFIG_KEY_DEBUG_MODE]) && $values[self::CONFIG_KEY_DEBUG_MODE] === '1')
            )
        )
        ->appendChild(
          id(new AphrontFormTextControl())
            ->setLabel(pht('App Name'))
            ->setPlaceholder(pht('App name as shown in Bugzilla auth page'))
            ->setName(self::CONFIG_KEY_APP_NAME)
            ->setValue(self::getArrayValueOrDefault($values, self::CONFIG_KEY_APP_NAME))
            ->setError(self::getArrayValueOrDefault($issues, self::CONFIG_KEY_APP_NAME))
        )
        ->appendChild(
          id(new AphrontFormTextControl())
            ->setLabel(pht('Transaction Code Length'))
            ->setPlaceholder(pht('Example: 32, 64, ...'))
            ->setName(self::CONFIG_KEY_TRANSACTION_CODE_LENGTH)
            ->setValue(
              self::getArrayValueOrDefault($values, self::CONFIG_KEY_TRANSACTION_CODE_LENGTH)
            )
            ->setError(
              self::getArrayValueOrDefault($issues, self::CONFIG_KEY_TRANSACTION_CODE_LENGTH)
            )
        );
  }

  public function getArrayValueOrDefault($values, $key, $default = '') {
    return trim(isset($values[$key]) ? $values[$key] : $default);
  }

  public function readFormValuesFromProvider() {
    $config = $this->getConfig();

    return array(
      self::CONFIG_KEY_DEBUG_MODE => $config->getProperty(self::CONFIG_KEY_DEBUG_MODE),
      self::CONFIG_KEY_APP_NAME => $config->getProperty(self::CONFIG_KEY_APP_NAME),
      self::CONFIG_KEY_TRANSACTION_CODE_LENGTH => (int)$config->getProperty(self::CONFIG_KEY_TRANSACTION_CODE_LENGTH)
    );
  }

  public function readFormValuesFromRequest(AphrontRequest $request) {
    return array(
      self::CONFIG_KEY_DEBUG_MODE => $request->getStr(self::CONFIG_KEY_DEBUG_MODE),
      self::CONFIG_KEY_APP_NAME => $request->getStr(self::CONFIG_KEY_APP_NAME),
      self::CONFIG_KEY_TRANSACTION_CODE_LENGTH => (int)$request->getStr(self::CONFIG_KEY_TRANSACTION_CODE_LENGTH)
    );
  }

  public function processEditForm(AphrontRequest $request, array $values) {
    $errors = array();
    $issues = array();

    if(!strlen(self::getArrayValueOrDefault($values, self::CONFIG_KEY_APP_NAME))) {
      $errors[] = pht('Application Name is required.');
      $issues[self::CONFIG_KEY_APP_NAME] = pht('Required');
    }

    $trans_code_length = (int) self::getArrayValueOrDefault(
      $values, self::CONFIG_KEY_TRANSACTION_CODE_LENGTH
    );
    if($trans_code_length < 16 || $trans_code_length > 64) {
      $errors[] = pht('Transaction Code Length must be between 16 and 64.');
      $issues[self::CONFIG_KEY_TRANSACTION_CODE_LENGTH] = pht('Invalid');
    }

    if(!count($errors)) {
      $config = $this->getProviderConfig();
      $config->setProviderDomain($this->getProviderDomain());
    }

    return array($errors, $issues, $values);
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
        ->setQueryParam(
          'description', $config->getProperty(self::CONFIG_KEY_APP_NAME)
        )
    );
  }

  /*
   *  Methods to guide the user through the auth and login processes
   */

  protected function renderLoginForm(AphrontRequest $request, $mode) {
    require_celerity_resource('bmo-auth-css');
    require_celerity_resource('bmo-auth-js');

    $adapter = $this->getAdapter();

    $csrf = $this->getAuthCSRFCode($request);
    $login_uri = $this->getLoginURI();
    $uri = new PhutilURI($adapter->getAuthenticateURI());

    $uri->setQueryParam('callback',
      PhabricatorEnv::getURI($login_uri).'?secret='.$csrf);

    return $this->renderStandardLoginButton($request, $mode, array(
      'method' => 'GET',
      'uri' => (string) $uri,
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

  private function processLoginRequestBackChannelPost($controller) {
    $config = $this->getConfig();
    $request = $controller->getRequest();
    $account = null;
    $response = null;

    // Get the `client_api_key` and `client_api_login` from Bugzilla's POST data
    $post_info = array();
    try {
      $post_info = phutil_json_decode(PhabricatorStartup::getRawInput());
    }
    catch(Exception $ex) {
      $this->throwException(
        pht('Phabricator BMO Authentication failed due to '.
            'invalid JSON from Bugzilla.')
      );
    }

    // Throw exception if either key is not provided by Bugzilla
    if(!isset($post_info['client_api_key']) || !isset($post_info['client_api_login'])) {
      $this->throwException(
        pht('Phabricator BMO Authentication failed due to '.
            'incomplete JSON from Bugzilla.')
      );
    }

    // Generate a transaction code which we'll receive back from Bugzilla
    // To confirm the API and Client Login information which we saved
    $trans_code = $this->generateAuthToken();
    $csrf = $request->getStr('secret');
    if(!strlen($csrf)) {
      $this->throwException(
        pht('No CSRF was provided by Bugzilla in the URL.')
      );
    }

    // Create a temporary auth token to save the user info JSON provided
    // by the POST from Bugzilla.  Implicitly validates CSRF.
    $unguarded = AphrontWriteGuard::beginScopedUnguardedWrites();
    id(new PhabricatorAuthTemporaryToken())
      ->setTokenResource($trans_code)
      ->setTokenCode($csrf)
      ->setTokenType(self::TOKEN_TYPE)
      ->setTokenExpires(time() + phutil_units('1 hour in seconds'))
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
      $this->throwException(pht('No temporary token found for this transaction code (%s) or CSRF token (%s).', $provided_trans_code, $csrf));
    }

    // Compare result token and client_api_login to original response
    $token_props = $token->getProperties();
    if($token_props['client_login'] != $provided_api_login) {
      $this->throwException(
        pht('Token\'s API Login does not match Bugzilla API Login.')
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
      ->setTimeout(5);

    // Resolve the async HTTPSFuture request and extract JSON body
    $whoami_body = '';
    try {
      list($whoami_body) = $future->resolvex();
    } catch (HTTPFutureResponseStatus $ex) {
      $this->throwException(pht('Bugzilla WhoAmI request failed to resolve.'));
    }

    $user_json = array();
    try {
      $user_json = phutil_json_decode($whoami_body);
    }
    catch(Exception $e) {
      $this->throwException(
        pht('JSON from Bugzilla WhoAmI could not be parsed: '.$whoami_body)
      );
    }

    // If there's no "id" key in the JSON, we know something is wrong
    if(!isset($user_json['id'])) {
      $this->throwException(pht('No user ID was provided by Bugzilla.'));
    }

    // Clean up! Delete temporary token used for this login
    $unguarded = AphrontWriteGuard::beginScopedUnguardedWrites();
    $token->delete();
    unset($unguarded);

    // Provide the adapter with massaged user data
    $this->setAccountDetails($user_json);

    // Create or load the user account and refresh the page
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
      $real_name = trim(str_replace(':'.$bugzilla_nick, '', $real_name));
    }

    $adapter->setAccountName($bugzilla_nick ?: str_replace(' ', '', $real_name));
    $adapter->setAccountRealName($real_name);
    $adapter->setAccountID($user_json['id']);
    $adapter->setAccountEmail($user_json['name']);
  }

  public function throwException($text) {
    $config = $this->getConfig();
    throw new Exception(pht(
      $config->getProperty(self::CONFIG_KEY_DEBUG_MODE) ? $text : self::GENERIC_ERROR
    ));
  }

  public function generateAuthToken() {
    $config = $this->getConfig();
    return Filesystem::readRandomCharacters(
      $config->getProperty(self::CONFIG_KEY_TRANSACTION_CODE_LENGTH)
    );
  }

  public function getProviderDomain() {
    list($bmo_protocol, $bmo_host) = explode('://', PhabricatorEnv::getEnvConfig('bugzilla.url'));
    return $bmo_host;
  }
}
