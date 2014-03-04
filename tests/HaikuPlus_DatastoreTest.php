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


class HaikuPlus_DatastoreTest extends \PHPUnit_Framework_TestCase {


  protected $datastore;
  protected $date;
  protected $user;
  protected $credential;
  protected $haiku;


  protected function setUp() {
    date_default_timezone_set('UTC');
    $date_string = '2013-11-21T04:00:00+0000';
    $this->date = HaikuPlus\Date::createFromString($date_string);
    $app = new Silex\Application();
    $app['debug'] = true;

    $test_database_name = '../data/phpunittest.db';
    HaikuPlus_TestUtils::registerCleanDatabase($app, $test_database_name);

    $this->datastore = new HaikuPlus\Datastore($app['db']);
    $test_user = new HaikuPlus\User();
    $test_user->setGoogleUserId('abcd');
    $test_user->setGoogleDisplayName('Chris Cartland');
    $test_user->setGooglePhotoUrl('http://placekitten.com/200/200');
    $test_user->setGoogleProfileUrl('http://placekitten.com/200/200');
    $test_user->setLastUpdated($this->date);
    $this->user = $test_user;

    $this->credential = json_decode('{}');
    $this->credential->refresh_token = 'REFRESHTOKEN';

    $this->haiku = new HaikuPlus\Haiku();
    $this->haiku->id = 'HAIKUID';
    $this->haiku->setAuthor($this->user);
    $this->haiku->setTitle('HAIKUTITLE');
    $this->haiku->setLineOne('LINEONE');
    $this->haiku->setLineTwo('LINETWO');
    $this->haiku->setLineThree('LINETHREE');
    $this->haiku->setVotes(100);
    $this->haiku->setCreationTime($date);
  }


  public function testUserNotInDatastore() {
    $google_id = $this->user->getGoogleUserId();
    $null_user = $this->datastore->loadUserWithGoogleId($google_id);
    $this->assertNull($null_user);
  }


  public function testUserCanBeAddedToDatastore() {
    $expected = $this->user;
    $google_id = $this->user->getGoogleUserId();
    $this->datastore->updateUser($expected);
    $user = $this->datastore->loadUserWithGoogleId($google_id);
    $this->assertEquals($expected, $user);
  }


  public function testUserCanBeRemovedFromDatastore() {
    $to_delete = $this->user;
    $user_id = $this->user->getId();
    $this->datastore->updateUser($this->user);
    $this->datastore->deleteUserWithId($user_id);
    $user = $this->datastore->loadUserWithId($user_id);
    $this->assertNull($user);
  }


  public function testCredentialNotInDatastore() {
    $google_id = $this->user->getGoogleUserId();
    $null_credential = $this->datastore->loadCredentialWithUserId($google_id);
    $this->assertNull($null_credential);
  }


  public function testCredentialCanBeAddedToDatastore() {
    $expected = $this->credential;
    $google_id = $this->user->getGoogleUserId();
    $this->datastore->setCredentialWithUserId($google_id, $expected);
    $credential = $this->datastore->loadCredentialWithUserId($google_id);
    $this->assertEquals($expected, $credential);
  }


  public function testCredentialCanBeRemovedFromDatastore() {
    $to_delete = $this->credential;
    $google_id = $this->user->getGoogleUserId();
    $this->datastore->setCredentialWithUserId($google_id, $to_delete);
    $this->datastore->deleteCredentialWithUserId($google_id);
    $credential = $this->datastore->loadCredentialWithUserId($google_id);
    $this->assertNull($credential);
  }


  public function testHaikuNotInDatastore() {
    $haiku_id = $this->haiku->getId();
    $null_haiku = $this->datastore->loadHaikuWithId($haiku_id);
    $this->assertNull($null_haiku);
  }


  public function testHaikuCanBeAddedToDatastore() {
    $expected = $this->haiku;
    $haiku_id = $this->haiku->getId();
    $this->datastore->updateUser($this->user);
    $this->datastore->setHaiku($expected);
    $haiku = $this->datastore->loadHaikuWithId($haiku_id);
    $this->assertEquals($expected, $haiku);
  }


  public function testHaikuCanBeRemovedFromDatastore() {
    $to_delete = $this->haiku;
    $haiku_id = $this->haiku->getId();
    $this->datastore->setHaiku($to_delete);
    $this->datastore->deleteHaikuWithId($haiku_id);
    $haiku = $this->datastore->loadHaikuWithId($haiku_id);
    $this->assertNull($haiku);
  }


  public function testHaikuCanBeRemovedFromDatastoreByUserId() {
    $to_delete = $this->haiku;
    $haiku_id = $this->haiku->getId();
    $this->datastore->setHaiku($to_delete);
    $this->datastore->updateUser($this->user);
    $user_id = $this->haiku->getAuthor()->getId();
    $this->datastore->deleteHaikusWithUserId($user_id);
    $haiku = $this->datastore->loadHaikuWithId($haiku_id);
    $this->assertNull($haiku);
  }


  public function testHaikuCanBeLoaded() {
    $haiku = $this->haiku;
    $this->datastore->updateUser($this->user);
    $this->datastore->setHaiku($haiku);
    $db_data = $this->datastore->loadHaikuWithId($haiku->getId());
    $this->assertEquals($haiku, $db_data);
  }


  public function testHaikusCanBeListed() {
    $this->datastore->updateUser($this->user);
    $haiku = $this->haiku;
    $ids = array('abc', 'def', 'ghi', 'jkl');
    foreach ($ids as $id) {
      $haiku->id = $id;
      $haiku->setAuthor($this->user);
      $this->datastore->setHaiku($haiku);
    }
    $db_data = $this->datastore->loadAllHaikus();
    $this->assertCount(4, $db_data);
    foreach (range(0, 3) as $i) {
      $haiku->id = $ids[$i];
      $this->assertEquals($haiku, $db_data[$i]);
    }
  }


  public function testEdgesDoNotExistForUser() {
    $edges = $this->datastore->loadEdgesForUser($this->user);
    $this->assertCount(0, $edges);
  }


  public function testEdgesCanBeSet() {
    $user = new HaikuPlus\User();
    $edges = array();
    foreach (range(0, 2) as $i) {
      $user->id = 'USERID' . $i;
      $user->setGoogleUserId('GOOGLEID' . $i);
      $edges[] = new HaikuPlus\DirectedUserToUserEdge($this->user, $user);
    }
    $this->datastore->setEdges($edges);
    $retrievedEdges = $this->datastore->loadEdgesForUser($this->user);
    $this->assertCount(3, $retrievedEdges);
  }


  public function testEdgesCanBeDeleted() {
    $user = new HaikuPlus\User();
    $edges = array();
    foreach (range(0, 2) as $i) {
      $user->id = 'USERID' . $i;
      $user->setGoogleUserId('GOOGLEID' . $i);
      $edges[] = new HaikuPlus\DirectedUserToUserEdge($this->user, $user);
    }
    $this->datastore->setEdges($edges);
    $this->datastore->deleteEdgesForUser($this->user);
    $retrievedEdges = $this->datastore->loadEdgesForUser($this->user);
    $this->assertCount(0, $retrievedEdges);
  }

}
