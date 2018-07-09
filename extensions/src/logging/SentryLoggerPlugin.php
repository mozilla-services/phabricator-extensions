<?php

class SentryLoggerPlugin extends Phobject {

  public static function registerErrorHandler() {
    PhutilReadableSerializer::printableValue(null);
    PhutilErrorHandler::setErrorListener(
      array(__CLASS__, 'handleError'));
  }

  public static function parse_query_str($str) {
    # result array
    $arr = array();

    # split on outer delimiter
    $pairs = explode('&', $str);

    # loop through each pair
    foreach ($pairs as $i) {
      # split into name and value
      list($name,$value) = explode('=', $i, 2);

      # if name already exists
      if( isset($arr[$name]) ) {
        # stick multiple values into an array
        if( is_array($arr[$name]) ) {
          $arr[$name][] = $value;
        }
        else {
          $arr[$name] = array($arr[$name], $value);
        }
      }
      # otherwise, simply stick it in a scalar
      else {
        $arr[$name] = $value;
      }
    }

    # return result array
    return $arr;
  }

  public static function generate_query_str($array) {
    $params = array();
    foreach ($array as $k => $v) {
      if (is_array($v))
        $params[] = append_params($v, urlencode($k));
      else
        $params[] = urlencode($k) . '=' . urlencode($v);
    }

    return implode('&', $params);
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
    $client = new Raven_Client($sentry_dsn, array(
      'processors' => array(
        'Raven_Processor_SanitizeHttpHeadersProcessor', // Sanitize the HTTP headers
      ),
      'processorOptions' => array(
        'Raven_Processor_SanitizeHttpHeadersProcessor' => array(
          'sanitize_http_headers' => array('Cookie', 'X-Phabricator-Csfr')
        )
      ),
      'send_callback' => function(&$data) {
        // Sanitize HTTP POST data
        $fields_re = '/^(__csrf__|token)$/i';
        $sanitize = function(&$item, $key, $fields_re) {
          if (empty($key)) {
            return;
          }
          if (preg_match($fields_re, $key)) {
            $item = '********';
          }
        };
        array_walk_recursive($data['request']['data'], $sanitize, $fields_re);

        // Sanitize query string
        $query_data = self::parse_query_str($data['request']['query_string']);
        array_walk_recursive($query_data, $sanitize, $fields_re);
        $data['request']['query_string'] = self::generate_query_str($query_data);

        // Sanitize cookie data
        if (isset($data['request']['cookies'])) {
          $data['request']['cookies']['phsid'] = '********';
        }
      }
    ));

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

