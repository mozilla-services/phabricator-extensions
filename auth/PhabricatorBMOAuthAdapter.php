<?php

final class PhabricatorBMOAuthAdapter extends PhutilAuthAdapter {

  private $account_id;
  private $account_email;
  private $account_name;
  private $account_real_name;
  private $auth_uri;

  private $type;
  private $domain;

  public function getAccountID() {
    return $this->account_id;
  }

  public function setAccountId($id) {
    $this->account_id = $id;
  }

  public function getAdapterType() {
    return $this->type;
  }

  public function setAdapterType($type) {
    $this->type = $type;
  }

  public function getAdapterDomain() {
    return $this->domain;
  }

  public function setAdapterDomain($domain) {
   $this->domain = $domain;
  }

  public function getAccountEmail() {
    return $this->account_email;
  }

  public function setAccountEmail($email) {
    $this->account_email = $email;
  }

  public function getAccountName() {
    return $this->account_name;
  }

  public function setAccountName($name) {
    $this->account_name = $name;
  }

  public function getAccountURI() {
    return null;
  }

  public function getAccountImageURI() {
    return null;
  }

  public function getAccountRealName() {
    return $this->account_real_name;
  }

  public function setAccountRealName($real_name) {
    $this->account_real_name = $real_name;
  }

  public function parseBugzillaNames($name) {
    // If possible, use the Bugzilla user name (ex: "First Last :firstlast")
    // Same regex used by version-control-tools
    $names = array('nick' => str_replace(' ', '', $name), 'real' => $name);

    // Parse out nickname and real name
    preg_match("/([\pL' -]+)?\s?\[?\(?:([a-zA-Z0-9\-\_]+)\)?\]?/u", $name, $matches);

    if($matches) {
      if(isset($matches[1])) {
        $names['real'] = $matches[1];
      }
      if(isset($matches[2])) {
        $names['nick'] = $matches[2];
      }
    }

    return $names;
  }

  public function getAuthenticateURI() {
    return $this->auth_uri;
  }

  public function setAuthenticateURI($uri) {
    $this->auth_uri = (string) $uri;
  }
}
