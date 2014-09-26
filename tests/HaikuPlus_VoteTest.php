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


include_once __DIR__ . '/HaikuPlus_TestUtils.php';


use Symfony\Component\HttpKernel\Exception\HttpException;

class HaikuPlus_VoteTest extends \PHPUnit_Framework_TestCase {


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


  public function testCanVoteForHaiku() {
    $app = $this->app;
    $request = $this->request;
    $haiku = $this->haiku;
    $haiku_id = $haiku->getId();
    $this->datastore->setHaiku($haiku);

    try {
      $modifiedHaiku = HaikuPlus\HaikuController::voteForHaiku($request, $app,
          $haiku_id, $this->user);
    } catch (HttpException $e) {
      $this->fail('Exception should not be thrown.');
    } catch (Exception $e) {
      $this->fail('Exception should not be thrown.' . $e);
    }
    $this->assertEquals($haiku->getVotes()+1, $modifiedHaiku->getVotes());
  }

}
