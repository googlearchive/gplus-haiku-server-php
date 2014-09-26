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


include_once __DIR__ . '/DynamicObject.php';
include_once __DIR__ . '/HaikuPlus_TestUtils.php';


use Symfony\Component\HttpKernel\Exception\HttpException;

class HaikuPlus_HaikuControllerTest extends \PHPUnit_Framework_TestCase {


  protected $app;
  protected $request;
  protected $datastore;
  protected $user;
  protected $haiku;
  protected $old_date;
  protected $mid_date;
  protected $new_date;


  protected function setUp() {
    date_default_timezone_set('UTC');
    $date_string = '2013-11-21T04:00:00+0000';
    $this->old_date = HaikuPlus\Date::createFromString($date_string);
    $date_string = '2013-11-22T04:00:00+0000';
    $this->mid_date = HaikuPlus\Date::createFromString($date_string);
    $date_string = '2013-11-23T04:00:00+0000';
    $this->new_date = HaikuPlus\Date::createFromString($date_string);
    $app = $this->getMock('Silex\Application', array('set'));
    $app['session'] = $this->getMock('Silex\Provider\SessionServiceProvider',
        array('get', 'set'));
    $this->app = $app;

    $this->request = new StdClass();
    $this->request = $this->getMock('Request', array('getContent', 'headers'));
    $this->request->headers = $this->getMock('MockHeaders', array('get'));

    $test_database_name = 'test.db';
    HaikuPlus_TestUtils::registerCleanDatabase($app, $test_database_name);

    $this->datastore = new HaikuPlus\Datastore($app['db']);
    $app['hp.datastore'] = $this->datastore;

    $this->user = new HaikuPlus\User();
    $this->user->setGoogleUserId('GOOGLEUSERID');
    $this->haiku = new HaikuPlus\Haiku();
    $this->haiku->setAuthor($this->user);

    $client_methods = array('setAccessToken', 'getAccessToken',
        'verifyIdToken', 'authenticate', 'revokeToken');
    $app['hp.client'] = $this->getMock('HaikuPlus\Google_Client',
        $client_methods);
    $app['hp.plus'] = new StdClass();
    $app['hp.plus']->people = $this->getMock('Google_PlusClient_People',
        array('listPeople'));
    $app['hp.plus']->moments = $this->getMock('Google_PlusClient_Moments',
        array('insert'));

    $app['hp.config'] = array('APP_BASE_URL' => 'https://BASEURL.example.com');

    $app['session'] = $this->getMock('Silex\Provider\SessionServiceProvider',
        array('get', 'set'));
  }


  public function testFetchEmptyListOfHaikus() {
    $app = $this->app;

    try {
      $haikus = HaikuPlus\HaikuController::fetchHaikus($this->request, $app);
    } catch (HttpException $e) {
      $this->fail('Exception should not be thrown.');
    }
    $this->assertNotNull($haikus);
  }


  public function testFetchHaikusReturnedChronologically() {
    $app = $this->app;
    $haiku = $this->haiku;
    $this->datastore->updateUser($this->user);
    $values = array(
      array('id' => 'def', 'time' => $this->mid_date),
      array('id' => 'abc', 'time' => $this->old_date),
      array('id' => 'ghi', 'time' => $this->new_date)
    );
    foreach ($values as $value) {
      $haiku->id = $value['id'];
      $haiku->setCreationTime($value['time']);
      $haiku->setAuthor($this->user);
      $this->datastore->setHaiku($haiku);
    }

    try {
      $haikus = HaikuPlus\HaikuController::fetchHaikus($this->request, $app);
    } catch (HttpException $e) {
      $this->fail('Exception should not be thrown.');
    }

    $this->assertCount(3, $haikus);
    $last_creation_time = null;
    foreach (range(0, 2) as $i) {
      $this->assertEquals($this->user, $haikus[$i]->getAuthor());
      $creation_time = $haikus[$i]->getCreationTime();
      if ($last_creation_time) {
        $this->assertLessThanOrEqual(
            $last_creation_time->getTimestamp(),
            $creation_time->getTimestamp());
      }
      $last_creation_time = $creation_time;
    }
  }


  public function testFetchFilteredEmptyListOfHaikus() {
    $app = $this->app;

    try {
      $haikus = HaikuPlus\HaikuController::fetchFilteredHaikus($this->request,
          $app, null);
    } catch (HttpException $e) {
      $this->fail('Exception should not be thrown.');
    }
    $this->assertNotNull($haikus);
  }


  public function testFetchFilteredHaikusReturnedChronologically() {
    $app = $this->app;
    $haiku = $this->haiku;
    $user_id = $this->user->getId();

    $this->datastore->updateUser($this->user);
    $values = array(
      array('id' => 'def', 'time' => $this->mid_date),
      array('id' => 'abc', 'time' => $this->old_date),
      array('id' => 'ghi', 'time' => $this->new_date)
    );
    $user = new HaikuPlus\User();
    $edges = array();
    foreach ($values as $value) {
      $haiku->id = $value['id'];
      $haiku->setCreationTime($value['time']);
      $user->updateUserId('AUTHORID' . $value['id']);
      $user->setGoogleUserId('GOOGLEID' . $value['id']);
      $this->datastore->updateUser($user);
      $haiku->setAuthor($user);
      $this->datastore->setHaiku($haiku);
    }

    $people = array(
      'items' => array(
        array('id' => 'GOOGLEIDdef'),
        array('id' => 'GOOGLEIDabc')
      )
    );
    // Server should request people.
    $app['hp.plus']->people->expects($this->exactly(1))
                           ->method('listPeople')
                           ->with('me', 'connected', $this->anything())
                           ->will($this->returnValue($people));

    try {
      $haikus = HaikuPlus\HaikuController::fetchFilteredHaikus($this->request,
          $app, $user);
    } catch (HttpException $e) {
      $this->fail('Exception should not be thrown.');
    }

    $this->assertCount(2, $haikus);
    $last_creation_time = null;
    foreach (range(0, 1) as $i) {
      $creation_time = $haikus[$i]->getCreationTime();
      if ($last_creation_time) {
        $this->assertLessThanOrEqual(
            $last_creation_time->getTimestamp(),
            $creation_time->getTimestamp());
      }
      $last_creation_time = $creation_time;
    }
  }


  public function testCreateHaiku() {
    $app = $this->app;
    $google_id = $this->user->getGoogleUserId();
    $this->datastore->updateUser($this->user);

    // Session starts valid.
    $session_state = $google_id;
    $session_get = function ($key) use (&$session_state) {
      return $session_state;
    };
    $session_set = function ($key, $value) use (&$session_state) {
      $session_state = $value;
    };

    $app['hp.plus']->moments->expects($this->any())
                            ->method('insert');

    $data = array();
    $data['title'] = 'title';
    $data['line_one'] = 'line_one';
    $data['line_two'] = 'line_two';
    $data['line_three'] = 'line_three';
    $content = json_encode($data);
    $getContent = function() use (&$content) {
      return $content;
    };
    $request = $this->request;
    $request->expects($this->any())
                           ->method('getContent')
                           ->will($this->returnCallback($getContent));

    $haiku = HaikuPlus\HaikuController::createFromData($request, $app,
        $this->user);
    $this->assertNotNull($haiku);
  }


  public function testGetHaiku() {
    $app = $this->app;
    $haiku_id = $this->haiku->getId();
    $this->datastore->setHaiku($this->haiku);

    $haiku = HaikuPlus\HaikuController::getHaiku($this->request, $app,
        $haiku_id);
    $this->assertNotNull($haiku);
  }


  public function testGetNonexistentHaikuDoesNotCrash() {
    $app = $this->app;
    $haiku_id = $this->haiku->getId();
    $this->datastore->setHaiku($this->haiku);

    try {
      $haiku = HaikuPlus\HaikuController::getHaiku($this->request, $app,
        'GARBAGE');
    } catch (HttpException $e) {
      $expected_code = 404;
      $response_code = $e->getStatusCode();
      $this->assertEquals($expected_code, $response_code);
    }
  }


  public function testNullAuthorDoesNotCrash() {
    $app = $this->app;
    $haiku = $this->haiku;
    $google_id = $this->user->getGoogleUserId();

    $this->datastore->updateUser($this->user);
    $values = array(
      array('id' => 'def', 'time' => $this->mid_date),
      array('id' => 'abc', 'time' => $this->old_date),
      array('id' => 'ghi', 'time' => $this->new_date)
    );
    $user = new HaikuPlus\User();
    $edges = array();
    foreach ($values as $value) {
      $haiku->id = $value['id'];
      $haiku->setCreationTime($value['time']);
      $user->updateUserId('AUTHORID' . $value['id']);
      $user->setGoogleUserId('GOOGLEID' . $value['id']);
      $this->datastore->updateUser($user);
      $haiku->setAuthor(null);
      $this->datastore->setHaiku($haiku);
    }

    $people = array(
      'items' => array(
        array('id' => 'GOOGLEIDdef'),
        array('id' => 'GOOGLEIDabc')
      )
    );
    // Server should request people.
    $app['hp.plus']->people->expects($this->exactly(1))
                           ->method('listPeople')
                           ->with('me', 'connected', $this->anything())
                           ->will($this->returnValue($people));

    try {
      $haikus = HaikuPlus\HaikuController::fetchFilteredHaikus($this->request,
          $app, $this->user);
    } catch (HttpException $e) {
      $this->fail('Exception should not be thrown.');
    }

    $this->assertCount(0, $haikus);
  }

}
