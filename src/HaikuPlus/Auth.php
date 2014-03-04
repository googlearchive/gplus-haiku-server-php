<?php
/*
 *
 * Copyright 2014 Google Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */


namespace HaikuPlus;


use Symfony\Component\HttpKernel\Exception\HttpException;
use Google_Auth_Exception;
use Google_IO_Exception;

/**
 * Authentication methods that handle check to see if a user is signed-in.
 *
 * @author cartland@google.com (Chris Cartland)
 */
class Auth {


  /**
   * Authentication identifier for an ID token.
   */
  const BEARER_SCHEME = 'Bearer';


  /**
   * Authentication identifier for an authorization code.
   */
  const CODE_SCHEME = 'X-OAuth-Code';


  /**
   * Name of the User Agent header to determine which requests come from iOS.
   */
  const USER_AGENT_HEADER_NAME = 'User-Agent';


  /**
   * Regular expression for identifying a request made from an iOS device.
   */
  const IOS_USER_AGENT_REGEX = '/\s*Haiku\+Client-iOS/';


  /**
   * Regular expression for identifying a request made from an iOS device.
   */
  const PROJECT_ID_REGEX = '/^[0-9]+/';


  /**
   * Name of the Bearer authorization header.
   */
  const CODE_AUTHORIZATION_HEADER_NAME = self::CODE_SCHEME;


  /**
   * Regular expression for identifying an authorization code header.
   * Matches: "AUTHORIZATION_CODE redirect_uri='postmessage'"
   * and extracts AUTHORIZATION_CODE in a group.
   */
  const CODE_REGEX = '/\s*(\S+)\s*\S*/';


  /**
   * Regular expression for identifying the redirect URI in header.
   * Matches: "4/v6xr77ewYqhvHSyW6UJ1w7jKwAzu redirect_uri='REDIRECT_URI'"
   * and extracts REDIRECT_URI in a group.
   */
  const REDIRECT_URI_REGEX = '/.*redirect_uri=\'(\S+)\'/';


  /**
   * Name of the Bearer authorization header.
   */
  const BEARER_AUTHORIZATION_HEADER_NAME = 'Authorization';


  /**
   * Regular expression for identifying an authorization ID token header.
   */
  const BEARER_REGEX = '/\s*Bearer\s+(\S+)/';


  /**
   * For use in verifying access tokens
   */
  const TOKEN_INFO_ENDPOINT = 'https://www.googleapis.com/oauth2/v1/tokeninfo';


  /**
   * For use in building authentication response headers
   */
  const WWW_AUTHENTICATE = 'WWW-Authenticate';


  /**
   * For use in building authentication response headers
   */
  const GOOGLE_REALM =
      'realm="https://www.google.com/accounts/AuthSubRequest"';


  /**
   * HTTP status code UNAUTHORIZD.
   */
  const HTTP_OK = 200;


  /**
   * HTTP status code UNAUTHORIZD.
   */
  const HTTP_UNAUTHORIZED = 401;


  /**
   * HTTP status code FORBIDDEN.
   */
  const HTTP_FORBIDDEN = 403;


  /**
   * HTTP status code METHOD NOT ALLOWED.
   */
  const HTTP_METHOD_NOT_ALLOWED = 405;


  /**
   * HTTP status code INTERNAL SERVER ERROR.
   */
  const HTTP_INTERNAL_SERVER_ERROR = 500;


  /**
   * Session key for user ID.
   */
  const SESSION_USER_ID = 'SESSION_USER_ID';


  /**
   * Silex request.
   */
  private $request;


  /**
   * Silex app.
   */
  private $app;


  /**
   * Private constructor to help manage request and app data in static methods.
   *
   * @param Request $request Silex request.
   * @param Silex\Application $app Silex app.
   */
  private function __construct($request, $app) {
    $this->request = $request;
    $this->app = $app;
  }


  /**
   * Authenticates a user by associating this session with a user based on a
   * provided ID token and by associating a user with valid credentials based
   * on a provided authorization code.
   *
   * @param Request $request Silex request.
   * @param Silex\Application $app Silex app.
   * @return user.
   * @throws HttpException.
   */
  public static function authenticate($request, $app) {
    $auth = new Auth($request, $app);
    return $auth->authFromHeaders();
  }


  /**
   * Helper method for self::authenticate.
   *
   * Processes headers and in the request and returns the current user
   * after updating credentials in session and database.
   *
   * @return Haiku+ user.
   * @throws HttpException
   */
  private function authFromHeaders() {
    $request = $this->request;
    $app = $this->app;

    // Process authorization code if it has been provided in the
    // request headers.
    $code_auth = $this->authorizationCodeHeaderValue();
    if ($code_auth) {
      $code_success = $this->processGoogleAuthorizationCode($code_auth);
      if (!$code_success) {
        $message = 'Invalid authorization code: ' . $code_auth;
        throw $this->unauthorizedCodeException($message);
      }
    }

    // Process bearer token if it has been provided in the request headers.
    // Bearer token could either be an ID token or an access token.
    $bearer_token = $this->bearerTokenHeaderValue();
    if ($bearer_token) {
      $id_token_success = $this->processGoogleIdToken($bearer_token);
      if (!$id_token_success) {
        $access_token_success = $this->processGoogleAccessToken($bearer_token);
        if (!$access_token_success) {
          $message = 'Invalid bearer token: ' . $bearer_token;
          throw $this->unauthorizedBearerException($message);
        }
      }
    }

    $user = $this->getUserFromSession();

    if ($user) {
      $user_id = $user->getId();
      $credential = $app['hp.datastore']->loadCredentialWithUserId($user_id);
      try {
        // We check the profile to ensure that the cached Google user data
        // is fresh.
        $user = $this->updateUserCache($user, $credential);
        return $user;
      } catch (Google_Auth_Exception $e) {
        // This exception is thrown when the credential is no longer valid.
        if (!$credential->refresh_token) {
          throw $this->unauthorizedCodeException('No refresh token.');
        }
        throw $this->unauthorizedCodeException('Invalid refresh token.');
      } catch (Google_IO_Exception $e) {
        throw new HttpException(self::HTTP_INTERNAL_SERVER_ERROR,
            'Could not communicate with Google.');
      }
    }
    throw $this->unauthorizedBearerException('Unable to identify user.');
  }


  /**
   * Exchanges the authorization code for OAuth 2.0 credentials.
   * Stores the new credentials in the database and sets the
   * Haiku+ user ID in the session.
   *
   * @param string $code_auth The OAuth 2.0 authorization code.
   * @return boolean true if authorization code is valid.
   */
  private function processGoogleAuthorizationCode($code_auth) {
    $app = $this->app;
    $redirect_uri = $this->redirectUriHeaderValue();
    if ($redirect_uri) {
      // Web client should specify 'postmessage'.
      $app['hp.client']->setRedirectUri($redirect_uri);
    } else {
      // Default to redirect URI for mobile clients.
      $app['hp.client']->setRedirectUri('urn:ietf:wg:oauth:2.0:oob');
    }

    // Exchange the OAuth 2.0 authorization code for user credentials.
    try {
      $app['hp.client']->authenticate($code_auth);
    } catch (Google_Auth_Exception $e) {
      return false;
    }

    try {
      $credential = json_decode($app['hp.client']->getAccessToken());

      // You can read the Google user ID in the ID token.
      // 'sub' represents the ID token subscriber which in our case
      // is the user ID.
      $id_token = $credential->id_token;
      $verification = $app['hp.client']->verifyIdToken($id_token);
      $attributes = $verification->getAttributes();
      $google_id = $attributes['payload']['sub'];

      $user = $this->setUserInSessionWithGoogleId($google_id);
      $user_id = $user->getId();
      if (!$credential->refresh_token) {
        // An authorization code has been provided that did not produce
        // a refresh token. Keep the old refresh token in the database.
        $old_credential =
            $app['hp.datastore']->loadCredentialWithUserId($user_id);
        $credential->refresh_token = $old_credential->refresh_token;
      }
      $app['hp.datastore']->setCredentialWithUserId($user_id,
          $credential);
    } catch (Google_Auth_Exception $e) {
      return false;
    }
    return true;
  }


  /**
   * Verifies the ID token. Sets the Haiku+ user ID in the session.
   *
   * @param string $id_token ID token JWT.
   * @return boolean true if ID token is valid.
   */
  private function processGoogleIdToken($id_token) {
    $app = $this->app;
    try {
      $verification = $app['hp.client']->verifyIdToken($id_token);
      $attributes = $verification->getAttributes();
      $google_id = $attributes['payload']['sub'];

      $user = $this->setUserInSessionWithGoogleId($google_id);
    } catch (Google_Auth_Exception $e) {
      // ID token was not valid.$
      $message = 'Forbidden: Invalid ID token. ' . $id_token;
      return false;
    }
    return true;
  }


  /**
   * Verifies the OAuth 2.0 access token.
   * Stores the new credentials in the database and sets the
   * Haiku+ user ID in the session.
   *
   * @param string $access_token The OAuth 2.0 access token.
   * @return boolean true if access token is valid.
   */
  private function processGoogleAccessToken($access_token) {
    $app = $this->app;
    try {
      $client_id = $app['hp.client_id'];
      $token_info = self::verifyAccessToken($access_token, $client_id);
      $google_id = $token_info->user_id;

      $user = $this->setUserInSessionWithGoogleId($google_id);
      $user_id = $user->getId();

      // Store auth credential.
      $credential = json_decode('{}');
      $credential->access_token = $access_token;
      $credential->expires_in = $token_info->expires_in;
      $credential->created = time();

      // Keep the old refresh token in the database.
      $old_credential =
          $app['hp.datastore']->loadCredentialWithUserId($user_id);
      $credential->refresh_token = $old_credential->refresh_token;

      $app['hp.datastore']->setCredentialWithUserId($user_id, $credential);
    } catch (Google_Auth_Exception $e) {
      $message = 'Forbidden: Invalid access token. ' . $access_token;
      return false;
    }
    return true;
  }


  /**
   * Returns a 403 Forbidden response with a message.
   * The response will include headers asking for a bearer token.
   *
   * @param string $message Response message.
   * @return HttpResponse 403 Forbidden.
   */
  /**
   * Returns a 401 Unauthorized response with a message.
   * The response will include headers asking for a bearer token.
   *
   * @param string $message Response message.
   * @return HttpResponse 401 Unauthorized.
   */
  private function unauthorizedBearerException($message) {
    // IETF RFC6750 defines how we should indicate that the user agent
    // needs to authenticate with a bearer token.
    $bearer_scheme = self::BEARER_SCHEME;
    $google_realm = self::GOOGLE_REALM;
    $headers = array(
      self::WWW_AUTHENTICATE =>
          $bearer_scheme . ' ' . $google_realm
    );
    $app = $this->app;
    return new HttpException(self::HTTP_UNAUTHORIZED, $message, null, $headers);
  }


  /**
   * Returns a 401 Unauthorized response with a message.
   * The response will include headers asking for an authorization code.
   *
   * @param string $message Response message.
   * @return HttpResponse 401 Unauthorized.
   * @throws HttpException.
   */
  private function unauthorizedCodeException($message) {
    // There is no standard way for our server to request a new
    // authorization code from our client, so we use an non-standard
    // scheme (X-OAuth-Code) to indicate that we need a new refresh token.
    $code_scheme = self::CODE_SCHEME;
    $google_realm = self::GOOGLE_REALM;
    $headers = array(
      $code_scheme => $google_realm
    );
    $app = $this->app;
    return new HttpException(self::HTTP_UNAUTHORIZED, $message, null, $headers);
  }


  /**
   * Sign user out of app session by deleting ID associated with session.
   *
   * @throws HttpException.
   */
  public static function signOut($request, $app) {
    $auth = new Auth($request, $app);
    $auth->updateUserIdInSession(null);
  }


  /**
   * Disconnect the user by deleting all Google data and revoking tokens.
   *
   * @throws HttpException
   */
  public static function disconnect($request, $app, $user) {
    $auth = new Auth($request, $app);
    $auth->disconnectUser($user);
  }


  /**
   * Helper method for self::disconnect().
   *
   * @throws HttpException.
   */
  private function disconnectUser($user) {
    $app = $this->app;
    $user_id = $user->getId();
    $credential = $app['hp.datastore']->loadCredentialWithUserId($user_id);
    $access_token = $credential->access_token;
    $success = $app['hp.client']->revokeToken($access_token);
    if (!$success) {
      throw new HttpException(self::HTTP_INTERNAL_SERVER_ERROR,
          'Could not revoke token.');
    }
    $app['hp.datastore']->deleteCredentialWithUserId($user_id);

    // The user has chosen to disconnect their Google account.
    // After we delete the data retrieved from Google, there will be
    // not way to associated the haikus with that user. Because the only
    // way we identify a user is with their Google account, we are going
    // to delete all of their haikus and user data.
    // If Haiku+ supported multiple authentication methods, such as
    // an email address and password, then we would only delete the user
    // accounts and haikus when Google was the last useable authentication
    // system.
    $app['hp.datastore']->deleteHaikusWithUserId($user_id);
    $app['hp.datastore']->deleteUserWithId($user_id);
    self::signOut($this->request, $app);
  }


  /**
   * Get the user ID based on the session.
   *
   * @return string or null.
   */
  private function getUserIdFromSession() {
    $user_id = $this->app['session']->get(self::SESSION_USER_ID);
    return $user_id;
  }


  /**
   * Get the user based on the session.
   *
   * @return string or null.
   */
  private function getUserFromSession() {
    $user_id = $this->getUserIdFromSession();
    if (!$user_id) {
      return null;
    }
    $user = $this->app['hp.datastore']->loadUserWithId($user_id);
    if (!$user) {
      return null;
    }
    return $user;
  }


  /**
   * Creates a new user if they are not in the database.
   * Sets the Haiku+ user ID in the current session.
   * Note that the Haiku+ user ID is different than the
   * Google user ID in the paramter.
   *
   * @param string $google_id The Google ID of a new or existing user.
   * @return User The Haiku+ user with Google ID.
   */
  private function setUserInSessionWithGoogleId($google_id) {
    $app = $this->app;
    $user = $this->getUserFromSession();
    if (!$user || $user->getGoogleUserId() != $google_id) {
      // The headers have authenticated the current user.
      // If the user has changed or the user is not in the session,
      // destroy session and put the new user in the session.
      $app['session']->clear();

      // Put the new user ID in the session.
      $user = $this->app['hp.datastore']->loadUserWithGoogleId($google_id);
      if (!$user) {
        // Create an entry for a new user.
        $user = new User();
        $user->setGoogleUserId($google_id);
        $this->app['hp.datastore']->updateUser($user);
      }
      $this->updateUserIdInSession($user->getId());
    }
    return $user;
  }


  /**
   * Set the Haiku+ user ID in the session.
   *
   * @param string $user_id The Haiku+ user ID of the current user.
   */
  private function updateUserIdInSession($user_id) {
    $this->app['session']->set(self::SESSION_USER_ID,
        $user_id);
  }


  /**
   * Updates the user's Google data in the Haiku+ DataStore, if the cached
   * data is more than one day old.
   *
   * @param User $user The current authenticated user.
   * @param stdClass $credential The associated credentials for the user.
   * @throws Google_Auth_Exception, Google_IO_Exception.
   */
  private function updateUserCache($user, $credential) {
    $user_id = $user->getId();
    $app = $this->app;

    // If the user's user was updated less than one day ago, do nothing.
    $now = time();
    $yesterday = $now - Date::ONE_DAY_IN_SECONDS;
    if ($user->isNewerThanUnixTime($yesterday)) {
      return $user;
    } else {
      try {
        self::fetchGoogleUserData($user, $credential);
        $app['hp.datastore']->updateUser($user);
      } catch (Google_Auth_Exception $e) {
        $app['hp.datastore']->deleteCredentialWithUserId($user_id);
        throw $e;
      }
    }
    return $user;
  }


  /*
   * Performs the Google+ people.get API call to refresh a user's cached
   * Google data. This data should never be stored permanently and should be
   * refreshed regularly (e.g. if it is older than 24 hours).
   *
   * @param User $user The current authenticated user.
   * @param stdClass $credential The associated credentials for the user.
   * @throws Google_Auth_Exception, Google_IO_Exception
   */
  private function fetchGoogleUserData($user, $credential) {
    $app = $this->app;
    $app['hp.client']->setAccessToken(json_encode($credential));

    // API call to Google+.
    $person = $app['hp.plus']->people->get('me');

    // A Person resource contains many things, but in this sample, we chose to
    // focus on the Google ID, the display name, and the URLs of the user's
    // profile and profile photo.
    $user->setGoogleUserId($person['id']);
    $user->setGoogleDisplayName($person['displayName']);
    $user->setGooglePhotoUrl($person['image']['url']);
    $user->setGoogleProfileUrl($person['url']);
    $user->setLastUpdated(Date::now());
  }


  /**
   * @param string $access_token.
   * @param string $client_id.
   * @return mixed[] token info.
   * @throws Google_Auth_Exception.
   */
  public static function verifyAccessToken($access_token, $client_id) {
    $endpoint = self::TOKEN_INFO_ENDPOINT;
    $reqUrl = $endpoint . '?access_token=' . urlencode($access_token);

    // Execute network request.
    $token_info = json_decode(file_get_contents($reqUrl));
    // $http_response_header is set from file_get_contents()
    $response_code = substr($http_response_header[0], 9, 3);

    if($response_code != '200') {
      throw new Google_Auth_Exception();
    } else {
      $project_regex = self::PROJECT_ID_REGEX;
      preg_match($project_regex, $client_id, $real_client_matches);
      $real_project_id = $real_client_matches[0];

      $unknown_client_id = $token_info->audience;
      preg_match($project_regex, $unknown_client_id, $unknown_client_matches);
      $unknown_project_id = $unknown_client_matches[0];

      if ($unknown_project_id != $real_project_id) {
        // This is not meant for this app. It is VERY important to check
        // the client ID in order to prevent attacks.
        throw new Google_Auth_Exception();
      }
    }
    return $token_info;
  }


  /**
   * Returns authorization code header, or null.
   *
   * @return string or null if the value does not exist.
   */
  private function authorizationCodeHeaderValue() {
    $code_header_name = self::CODE_AUTHORIZATION_HEADER_NAME;
    $code_regex = self::CODE_REGEX;
    $matches = $this->headerWithPattern($code_header_name, $code_regex);
    if ($matches) {
      return $matches[1];
    }
    return null;
  }


  /**
   * Returns redirect URI header value, or null.
   *
   * @return string or null if the value does not exist.
   */
  private function redirectUriHeaderValue() {
    $code_header_name = self::CODE_AUTHORIZATION_HEADER_NAME;
    $redirect_uri_regex = self::REDIRECT_URI_REGEX;
    $matches = $this->headerWithPattern($code_header_name, $redirect_uri_regex);
    if ($matches) {
      return $matches[1];
    }
    return null;
  }


  /**
   * Returns bearer authorization header, or null.
   * This will either be an access token or ID token.
   *
   * @return string or null if the value does not exist.
   */
  private function bearerTokenHeaderValue() {
    $id_header_name = self::BEARER_AUTHORIZATION_HEADER_NAME;
    $id_regex = self::BEARER_REGEX;
    $matches = $this->headerWithPattern($id_header_name, $id_regex);
    if ($matches) {
      return $matches[1];
    }
    return null;
  }


  /**
   * Returns the value in a header matches the regular expression.
   *
   * Returns text that matched the first captured parenthesized subpattern.
   * If no parenthesized subpattern is provided, returns the text that
   * matched the full pattern.
   *
   * @param string $header_name.
   * @param string $regex.
   * @return string the value of the header or null.
   */
  private function headerWithPattern($header_name, $regex) {
    $header_value = $this->request->headers->get($header_name);
    if ($header_value) {
      $matches = array();
      if (preg_match($regex, $header_value, $matches)) {
        return $matches;
      }
    }
    return null;
  }
}

