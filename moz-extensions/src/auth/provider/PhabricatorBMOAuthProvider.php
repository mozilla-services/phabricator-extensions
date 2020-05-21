<?php

final class PhabricatorBMOAuthProvider
  extends PhabricatorOAuth2AuthProvider {

  public function getProviderName() {
    return pht('BMO');
  }

  protected function getProviderConfigurationHelp() {
    $uri = PhabricatorEnv::getProductionURI('/');
    $callback_uri = PhabricatorEnv::getURI($this->getLoginURI());

    return pht(
      "To configure BMO OAuth, create a new BMO OAuth Application here:".
      "\n\n".
      "https://bugzilla.mozilla.org/admin/oauth/create".
      "\n\n".
      "You should use these settings in your application:".
      "\n\n".
      "  - **URL:** Set this to your full domain with protocol. For this ".
      "    Phabricator install, the correct value is: `%s`\n".
      "  - **Callback URL**: Set this to: `%s`\n".
      "\n\n".
      "Once you've created an application, copy the **Client ID** and ".
      "**Client Secret** into the fields above.",
      $uri,
      $callback_uri);
  }

  protected function newOAuthAdapter() {
    return new PhutilBMOAuthAdapter();
  }

  protected function renderLoginForm(AphrontRequest $request, $mode) {
    require_celerity_resource('bmo-auth-css');
    require_celerity_resource('bmo-auth-js');
    return parent::renderLoginForm($request, $mode);
  }

}
