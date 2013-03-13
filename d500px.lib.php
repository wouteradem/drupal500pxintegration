<?php

/**
 * Exception handling class.
 */
class D500pxException extends Exception {}



/**
 * Primary 500px API implementation class
 */
class D500px {
  /**
   * @var $source the 500px api 'source'
   */
  protected $source = 'drupal';
  protected $signature_method;
  protected $consumer;
  protected $token;


  /********************************************//**
   * Authentication
   ***********************************************/
  /**
   * Constructor for the 500px class
   */
  public function __construct($consumer_key, $consumer_secret, $oauth_token = NULL, $oauth_token_secret = NULL) {
    $this->signature_method = new OAuthSignatureMethod_HMAC_SHA1();
    $this->consumer = new OAuthConsumer($consumer_key, $consumer_secret);
    if (!empty($oauth_token) && !empty($oauth_token_secret)) {
      $this->token = new OAuthConsumer($oauth_token, $oauth_token_secret);
    }
  }


  public function get_request_token() {
    $url = variable_get('d500px_api', D500PX_API) . '/v1/oauth/request_token';
    try {
      $params = array('oauth_callback' => url('d500px/oauth', array('absolute' => TRUE)));
      $response = $this->auth_request($url, $params);
    }
    catch (D500pxException $e) {
      watchdog('D500px', '!message', array('!message' => $e->__toString()), WATCHDOG_ERROR);
      return FALSE;
    }
    parse_str($response, $token);
    $this->token = new OAuthConsumer($token['oauth_token'], $token['oauth_token_secret']);
    return $token;
  }


  public function get_authorize_url($token) {
    $url = variable_get('d500px_api', D500PX_API) . '/v1/oauth/authorize';
    $url.= '?oauth_token=' . $token['oauth_token'];

    return $url;
  }  
  
  
  public function get_authenticate_url($token) {
    $url = variable_get('d500px_api', D500PX_API) . '/v1/oauth/authenticate';
    $url.= '?oauth_token=' . $token['oauth_token'];

    return $url;
  }  
  
  
  public function get_access_token() {
    $url = variable_get('d500px_api', D500PX_API) . '/v1/oauth/access_token';
    try {
      $response = $this->auth_request($url);
    }
    catch (D500pxException $e) {
      watchdog('D500px', '!message', array('!message' => $e->__toString()), WATCHDOG_ERROR);
      return FALSE;
    }
    parse_str($response, $token);
    $this->token = new OAuthConsumer($token['oauth_token'], $token['oauth_token_secret']);
    return $token;
  } 
  
  
  /**
   * Performs an authenticated request.
   */
  public function auth_request($url, $params = array(), $method = 'GET') {
    $request = OAuthRequest::from_consumer_and_token($this->consumer, $this->token, $method, $url, $params);
    $request->sign_request($this->signature_method, $this->consumer, $this->token);
    switch ($method) {
      case 'GET':
        return $this->request($request->to_url());
      case 'POST':
        return $this->request($request->get_normalized_http_url(), $request->get_parameters(), 'POST');
    }
  }   
}