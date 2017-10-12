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

  public function parseBugzillaNick($name) {
    // If possible, use the Bugzilla user name (ex: "First Last :firstlast")
    // Same regex used by BMO
    // https://github.com/mozilla-bteam/bmo/blob/3465c3905f542d576a61f372a5cb2da3f823d508/extensions/BugModal/lib/MonkeyPatches.pm
    preg_match('/:?:(\S+?)\b/', $name, $matches);
    return isset($matches[1]) ? $matches[1] : null;
  }

  public function getAuthenticateURI() {
    return $this->auth_uri;
  }

  public function setAuthenticateURI($uri) {
    $this->auth_uri = (string) $uri;
  }
}
