<?php

class SentryLoggerPlugin extends Phobject {

  public static function registerErrorHandler() {
    PhutilReadableSerializer::printableValue(null);
    PhutilErrorHandler::setErrorListener(
      array(__CLASS__, 'handleError'));
  }

  public static function handleError($event, $value, $metadata) {
    $sentry_dsn = PhabricatorEnv::getEnvConfigIfExists('sentry.dsn');

    if (empty($sentry_dsn)) {
      return;
    }

    // Configure the client
    $root = phutil_get_library_root('phabricator');
    $root = dirname($root);
    require_once $root . '/externals/extensions/autoload.php';
    $client = new Raven_Client($sentry_dsn);

    switch ($event) {
      case PhutilErrorHandler::EXCEPTION:
        // $value is of type Exception
        $client->captureException($value);
        break;
      case PhutilErrorHandler::ERROR:
        // $value is a simple string
        $client->captureMessage($value);
        break;
      default:
        error_log(pht('Unknown event: %s', $event));
        break;
    }
  }
}

