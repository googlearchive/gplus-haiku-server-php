<?php
/*
 *
 * Copyright 2013 Google Inc.
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


include_once __DIR__ . '/HaikuPlus_TestUtils.php';


use Symfony\Component\HttpKernel\Exception\HttpException;

class HaikuPlus_AuthIosTest extends \PHPUnit_Framework_TestCase {


  protected $app;
  protected $request;
  protected $google_id;
  protected $user_id;
  protected $expected;
  protected $person;
  protected $id_token;
  protected $refresh_token;
  protected $access_token;
  protected $credential;
  protected $ios_user_agent;
  protected $client_id;


  protected function setUp() {
    date_default_timezone_set('UTC');

    $this->ios_user_agent = 'Haiku+Client-iOS';
    $this->client_id = 'CLIENTID';

    $app = $this->getMock('Silex\Application', array('set'));
    $app['session'] = $this->getMock('Silex\Provider\SessionServiceProvider',
        array('get', 'set'));

    $client_methods = array('setAccessToken', 'getAccessToken',
        'verifyIdToken', 'authenticate', 'revokeToken', 'setRedirectUri');
    $app['hp.client_id'] = $this->client_id;
    $app['hp.client'] = $this->getMock('HaikuPlus\Google_Client',
        $client_methods);

    $app['hp.plus'] = new StdClass();
    $app['hp.plus']->people = $this->getMock('Google_PlusClient_People',
        array('get'));

    $this->request = new StdClass();
    $this->request->headers = $this->getMock('MockHeaders', array('get'));

    $this->google_id = 'abcd';
    $this->user_id = 'zyxw';
    $display_name = 'Super Sally';
    $profile = 'profileurl';
    $photo = 'photourl';

    $expected = new HaikuPlus\User();
    $expected->updateUserId($this->user_id);
    $expected->setGoogleUserId($this->google_id);
    $expected->setGoogleDisplayName($display_name);
    $expected->setGoogleProfileUrl($profile);
    $expected->setGooglePhotoUrl($photo);
    $this->expected = $expected;

    $person = array();
    $person['id'] = $this->google_id;
    $person['displayName'] = $display_name;
    $person['image']['url'] = $photo;
    $person['url'] = $profile;
    $this->person = $person;

    $test_database_name = 'test.db';
    HaikuPlus_TestUtils::registerCleanDatabase($app, $test_database_name);
    $app['hp.datastore'] = new HaikuPlus\Datastore($app['db']);

    $app['hp.client_id'] = 'CLIENTID';

    $this->id_token = 'IDTOKEN';
    $this->refresh_token = 'REFRESHTOKEN';
    $this->access_token = 'ACCESSTOKEN';
    $this->credential = json_decode('{}');

    $this->app = $app;
  }


  public function testAuthenticateFullSuccessFetchingData() {
    $app = $this->app;
    $expected = $this->expected;
    $credential = $this->credential;

    // The request does not contain any auth headers.
    $value_map = array(
      array(HaikuPlus\Auth::BEARER_AUTHORIZATION_HEADER_NAME, null),
      array(HaikuPlus\Auth::USER_AGENT_HEADER_NAME, $this->ios_user_agent)
    );
    $this->request->headers->expects($this->any())
                           ->method('get')
                           ->will($this->returnValueMap($value_map));

    // Session is valid.
    $session_state = $this->user_id;
    $this->initAppSession($app, $session_state);

    // Access token is in database.
    $credential->access_token = $this->access_token;
    $app['hp.datastore']->setCredentialWithUserId($this->user_id, $credential);

    // User data is not fresh.
    $expected->setLastUpdated(null);
    $app['hp.datastore']->updateUser($expected);

    // Server should request data from Google using credential in database.
    $app['hp.client']->expects($this->once())
                     ->method('setAccessToken')
                     ->with(json_encode($credential));

    // Server should request user profile data.
    $app['hp.plus']->people->expects($this->once())
                           ->method('get')
                           ->with('me')
                           ->will($this->returnValue($this->person));

    try {
      $returned_user = HaikuPlus\Auth::authenticate($this->request, $app);
    } catch (HttpException $e) {
      $this->fail('Exception should not be thrown.' . $e);
    }

    $user = $app['hp.datastore']->loadUserWithId($returned_user->getId());
    $this->assertEquals($expected->getId(), $user->getId());
    $this->assertEquals($expected->getGoogleUserId(), $user->getGoogleUserId());
    $this->assertEquals($expected->getGoogleDisplayName(),
        $user->getGoogleDisplayName());
    $this->assertEquals($expected->getGooglePhotoUrl(),
        $user->getGooglePhotoUrl());
    $this->assertEquals($expected->getGoogleProfileUrl(),
        $user->getGoogleProfileUrl());
  }


  public function testAuthenticateFullSuccessCachedData() {
    $app = $this->app;
    $expected = $this->expected;
    $credential = $this->credential;

    // The request does not contain any auth headers.
    $value_map = array(
      array(HaikuPlus\Auth::BEARER_AUTHORIZATION_HEADER_NAME, null),
      array(HaikuPlus\Auth::USER_AGENT_HEADER_NAME, $this->ios_user_agent)
    );
    $this->request->headers->expects($this->any())
                           ->method('get')
                           ->will($this->returnValueMap($value_map));

    // Session is valid.
    $session_state = $this->user_id;
    $this->initAppSession($app, $session_state);

    // Access token is in database.
    $credential->access_token = $this->access_token;
    $app['hp.datastore']->setCredentialWithUserId($this->user_id, $credential);

    // User data is fresh.
    $expected->setLastUpdated(HaikuPlus\Date::now());
    $app['hp.datastore']->updateUser($expected);

    // Server should not request data from Google using credential in database.
    $app['hp.client']->expects($this->never())
                     ->method('setAccessToken');

    // Server should not request user profile data.
    $app['hp.plus']->people->expects($this->never())
                           ->method('get');

    try {
      $returned_user = HaikuPlus\Auth::authenticate($this->request, $app);
    } catch (HttpException $e) {
      $this->fail('Exception should not be thrown.' . $e);
    }

    $user = $app['hp.datastore']->loadUserWithGoogleId($this->google_id);
    $this->assertEquals($expected->getId(), $user->getId());
    $this->assertEquals($expected->getGoogleUserId(), $user->getGoogleUserId());
    $this->assertEquals($expected->getGoogleDisplayName(),
        $user->getGoogleDisplayName());
    $this->assertEquals($expected->getGooglePhotoUrl(),
        $user->getGooglePhotoUrl());
    $this->assertEquals($expected->getGoogleProfileUrl(),
        $user->getGoogleProfileUrl());
  }


  public function testAuthenticateFailGoogleApi() {
    $app = $this->app;
    $expected = $this->expected;
    $credential = $this->credential;

    // The request does not contain any auth headers.
    $value_map = array(
      array(HaikuPlus\Auth::BEARER_AUTHORIZATION_HEADER_NAME, null),
      array(HaikuPlus\Auth::CODE_AUTHORIZATION_HEADER_NAME, null),
      array(HaikuPlus\Auth::USER_AGENT_HEADER_NAME, $this->ios_user_agent)
    );
    $this->request->headers->expects($this->any())
                           ->method('get')
                           ->will($this->returnValueMap($value_map));

    // Session is valid.
    $session_state = $this->user_id;
    $this->initAppSession($app, $session_state);

    // Access token is in database.
    $credential->access_token = $this->access_token;
    $app['hp.datastore']->setCredentialWithUserId($this->user_id, $credential);

    // User data is not fresh.
    $expected->setLastUpdated(null);
    $app['hp.datastore']->updateUser($expected);

    // Server should request data from Google using credential in database.
    $app['hp.client']->expects($this->once())
                     ->method('setAccessToken')
                     ->with(json_encode($credential));

    // Request to Google will fail with Google_Auth_Exception.
    $fail_google_api = function () use ($credential) {
      throw new Google_Auth_Exception();
    };
    // Server should request user profile data.
    $app['hp.plus']->people->expects($this->once())
                           ->method('get')
                           ->with('me')
                           ->will($this->returnCallback($fail_google_api));


    try {
      $returned_user = HaikuPlus\Auth::authenticate($this->request, $app);
      $this->fail('HttpException was not thrown.');
    } catch (HttpException $e) {
      $expected_code = HaikuPlus\AUTH::HTTP_UNAUTHORIZED;
      $response_code = $e->getStatusCode();
      $this->assertEquals($expected_code, $response_code);
    }
  }


  public function testAuthenticateIO_ExceptionWithGoogleAPI() {
    $app = $this->app;
    $expected = $this->expected;
    $credential = $this->credential;

    // The request does not contain any auth headers.
    $value_map = array(
      array(HaikuPlus\Auth::BEARER_AUTHORIZATION_HEADER_NAME, null),
      array(HaikuPlus\Auth::USER_AGENT_HEADER_NAME, $this->ios_user_agent)
    );
    $this->request->headers->expects($this->any())
                           ->method('get')
                           ->will($this->returnValueMap($value_map));

    // Session is valid.
    $session_state = $this->user_id;
    $this->initAppSession($app, $session_state);

    // Access token is in database.
    $credential->access_token = $this->access_token;
    $app['hp.datastore']->setCredentialWithUserId($this->user_id, $credential);

    // User data is not fresh.
    $expected->setLastUpdated(null);
    $app['hp.datastore']->updateUser($expected);

    // Server should request data from Google using credential in database.
    $app['hp.client']->expects($this->once())
                     ->method('setAccessToken')
                     ->with(json_encode($credential));

    // Request to Google will fail with Google_IO_Exception.
    $fail_google_api = function () use ($credential) {
      throw new Google_IO_Exception();
    };
    // Server should request user profile data.
    $app['hp.plus']->people->expects($this->once())
                           ->method('get')
                           ->with('me')
                           ->will($this->returnCallback($fail_google_api));


    try {
      $returned_user = HaikuPlus\Auth::authenticate($this->request, $app);
      $this->fail('HttpException was not thrown.');
    } catch (HttpException $e) {
      $expected_code = HaikuPlus\AUTH::HTTP_INTERNAL_SERVER_ERROR;
      $response_code = $e->getStatusCode();
      $this->assertEquals($expected_code, $response_code);
    }

    // Credentials should not be removed when network fails.
    $remaining_credential =
        $app['hp.datastore']->loadCredentialWithUserId($this->user_id);
    $this->assertEquals($credential, $remaining_credential);
  }


  public function testAuthenticateFailsWithInvalidAccessToken() {
    $app = $this->app;
    $expected = $this->expected;
    $credential = $this->credential;
    $access_token = $this->access_token;
    $clientId = $app['hp.client_id'];
    $google_id = $this->google_id;
    $user_id = $this->user_id;

    $bearer_header = HaikuPlus\Auth::BEARER_AUTHORIZATION_HEADER_NAME;
    // The request contains an access token.
    $value_map = array(
      array($bearer_header, 'Bearer ' . $access_token),
      array(HaikuPlus\Auth::USER_AGENT_HEADER_NAME, $this->ios_user_agent)
    );
    $this->request->headers->expects($this->any())
                           ->method('get')
                           ->will($this->returnValueMap($value_map));

    // Session starts invalid, becomes valid after set.
    $session_state = null;
    $this->initAppSession($app, $session_state);

    // Access token is in database.
    $credential->access_token = $this->access_token;
    $app['hp.datastore']->setCredentialWithUserId($user_id, $credential);

    // User data is not fresh.
    $expected->setLastUpdated(null);
    $app['hp.datastore']->updateUser($expected);

    // Server should verify ID token, and it will fail.
    $verify_id_token_callback = function($id_token) {
      throw new Google_Auth_Exception();
    };
    $app['hp.client']->expects($this->any())
                     ->method('verifyIdToken')
                     ->with($this->anything())
                     ->will($this->returnCallback($verify_id_token_callback));

    try {
      $returned_user = HaikuPlus\Auth::authenticate($this->request, $app);
      $this->fail('HttpException was not thrown.');
    } catch (HttpException $e) {
      $expected_code = HaikuPlus\AUTH::HTTP_UNAUTHORIZED;
      $response_code = $e->getStatusCode();
      $this->assertEquals($expected_code, $response_code);
    }
  }


  public function testAuthenticateFailsWithNoAccessToken() {
    $app = $this->app;
    $expected = $this->expected;
    $credential = $this->credential;
    $google_id = $this->google_id;
    $user_id = $this->user_id;

    // The request does not contain any auth headers.
    $value_map = array(
      array(HaikuPlus\Auth::BEARER_AUTHORIZATION_HEADER_NAME, null),
      array(HaikuPlus\Auth::USER_AGENT_HEADER_NAME, $this->ios_user_agent)
    );
    $this->request->headers->expects($this->any())
                           ->method('get')
                           ->will($this->returnValueMap($value_map));

    // Session starts invalid, becomes valid after set.
    $session_state = null;
    $this->initAppSession($app, $session_state);

    // Access token is in database.
    $credential->access_token = $this->access_token;
    $app['hp.datastore']->setCredentialWithUserId($user_id, $credential);

    // User data is not fresh.
    $expected->setLastUpdated(null);
    $app['hp.datastore']->updateUser($expected);

    // Server should not request data from Google using credential in database.
    $app['hp.client']->expects($this->never())
                     ->method('setAccessToken');

    // Server should not request user profile data.
    $app['hp.plus']->people->expects($this->never())
                           ->method('get');

    try {
      $returned_user = HaikuPlus\Auth::authenticate($this->request, $app);
      $this->fail('HttpException was not thrown.');
    } catch (HttpException $e) {
      $expected_code = HaikuPlus\AUTH::HTTP_UNAUTHORIZED;
      $response_code = $e->getStatusCode();
      $this->assertEquals($expected_code, $response_code);
    }
  }


  public function testSignOutWhenSignedIn() {
    $app = $this->app;
    $expected = $this->expected;
    $credential = $this->credential;
    $google_id = $this->google_id;
    $user_id = $this->user_id;

    // The request does not contain any auth headers.
    $value_map = array(
      array(HaikuPlus\Auth::BEARER_AUTHORIZATION_HEADER_NAME, null),
      array(HaikuPlus\Auth::USER_AGENT_HEADER_NAME, null)
    );
    $this->request->headers->expects($this->any())
                           ->method('get')
                           ->will($this->returnValueMap($value_map));

    // Session starts valid.
    $session_state = $this->user_id;
    $this->initAppSession($app, $session_state);

    // Access token is in database.
    $credential->access_token = $this->access_token;
    $app['hp.datastore']->setCredentialWithUserId($user_id, $credential);

    // User data is not fresh.
    $expected->setLastUpdated(null);
    $app['hp.datastore']->updateUser($expected);

    // Server should request data from Google using credential in database.
    $app['hp.client']->expects($this->any())
                     ->method('setAccessToken')
                     ->with(json_encode($credential));

    // Server should request user profile data.
    $app['hp.plus']->people->expects($this->any())
                           ->method('get')
                           ->with('me')
                           ->will($this->returnValue($this->person));

    try {
      HaikuPlus\Auth::signOut($this->request, $app);
    } catch (HttpException $e) {
      $this->fail('Exception should not be thrown.');
    }

    $user = $app['hp.datastore']->loadUserWithGoogleId($google_id);
    $this->assertEquals($expected->getId(), $user->getId());
    $this->assertEquals($expected->getGoogleUserId(), $user->getGoogleUserId());
    $this->assertEquals($expected->getGoogleDisplayName(),
        $user->getGoogleDisplayName());
    $this->assertEquals($expected->getGooglePhotoUrl(),
        $user->getGooglePhotoUrl());
    $this->assertEquals($expected->getGoogleProfileUrl(),
        $user->getGoogleProfileUrl());

    try {
      $returned_user = HaikuPlus\Auth::authenticate($this->request, $app);
      $this->fail('HttpException was not thrown.');
    } catch (HttpException $e) {
      $expected_code = HaikuPlus\AUTH::HTTP_UNAUTHORIZED;
      $response_code = $e->getStatusCode();
      $this->assertEquals($expected_code, $response_code);
    }
  }


  public function testDisconnectWhenSignedIn() {
    $app = $this->app;
    $expected = $this->expected;
    $credential = $this->credential;
    $google_id = $this->google_id;
    $user_id = $this->user_id;

    // The request does not contain any auth headers.
    $value_map = array(
      array(HaikuPlus\Auth::BEARER_AUTHORIZATION_HEADER_NAME, null),
      array(HaikuPlus\Auth::USER_AGENT_HEADER_NAME, $this->ios_user_agent)
    );
    $this->request->headers->expects($this->any())
                           ->method('get')
                           ->will($this->returnValueMap($value_map));

    // Session starts valid.
    $session_state = $this->user_id;
    $this->initAppSession($app, $session_state);

    // Access token is in database.
    $credential->access_token = $this->access_token;
    $app['hp.datastore']->setCredentialWithUserId($user_id, $credential);

    // User data is not fresh.
    $expected->setLastUpdated(null);
    $app['hp.datastore']->updateUser($expected);

    // Server should request data from Google using credential in database.
    $app['hp.client']->expects($this->exactly(1))
                     ->method('revokeToken')
                     ->with($credential->access_token)
                     ->will($this->returnValue(true));

    // Server should request user profile data.
    $app['hp.plus']->people->expects($this->any())
                           ->method('get')
                           ->with('me')
                           ->will($this->returnValue($this->person));

    try {
      $user  = HaikuPlus\Auth::authenticate($this->request, $app);
      HaikuPlus\Auth::disconnect($this->request, $app, $user);
    } catch (HttpException $e) {
      $this->fail('Exception should not be thrown.' . $e);
    }

    $user = $app['hp.datastore']->loadUserWithGoogleId($google_id);
    $this->assertNull($user);

    $credential = $app['hp.datastore']->loadCredentialWithUserId($user_id);
    $this->assertNull($credential);
  }


  /**
   * Initializes mock session state.
   *
   * $app['session']->get() and ->set() will be called with
   * HaikuPlus\Auth::SESSION_USER_ID to read and write to the session.
   * This method prepares the object to behave as expected by reading
   * and writing to a fake session.
   *
   * @param $app PHPUnit_Framework_MockObject_MockObject Silex app mock.
   * @param $session_state string Initial user ID.
   */
  private function initAppSession($app, $session_state) {
    $session_get = function ($key) use (&$session_state) {
      return $session_state;
    };
    $session_set = function ($key, $value) use (&$session_state) {
      $session_state = $value;
    };
    $app['session']->expects($this->any())
                   ->method('get')
                   ->with(HaikuPlus\Auth::SESSION_USER_ID)
                   ->will($this->returnCallback($session_get));
    $app['session']->expects($this->any())
                   ->method('set')
                   ->with(HaikuPlus\Auth::SESSION_USER_ID, $this->anything())
                   ->will($this->returnCallback($session_set));
  }

}
