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


class HaikuPlus_UserTest extends \PHPUnit_Framework_TestCase {


  protected $id;
  protected $google_id;
  protected $display_name;
  protected $profile;
  protected $photo;
  protected $updated;


  protected function setUp() {
    date_default_timezone_set('UTC');
    $this->id = 'abcd';
    $this->google_id = 'efgh';
    $this->display_name = 'Super Sally';
    $this->profile = 'profileurl';
    $this->photo = 'photourl';
    $date_string = '2013-11-21T04:00:00+0000';
    $this->updated = HaikuPlus\Date::createFromString($date_string);
  }


  public function testDefaultData() {
    $empty_user = new HaikuPlus\User();
    $this->assertNotNull($empty_user->getId());
    $this->assertNull($empty_user->getGoogleUserId());
    $this->assertNull($empty_user->getGoogleDisplayName());
    $this->assertNull($empty_user->getGoogleProfileUrl());
    $this->assertNull($empty_user->getGooglePhotoUrl());
    $this->assertNull($empty_user->getLastUpdated());
  }


  public function testCreateUniqueIds() {
    $user1 = new HaikuPlus\User();
    $user2 = new HaikuPlus\User();
    $this->assertNotEquals($user1->getId(), $user2->getId());
  }


  public function testSettingData() {
    $test_user = new HaikuPlus\User();
    $test_user->updateUserId($this->id);
    $test_user->setGoogleUserId($this->google_id);
    $test_user->setGoogleDisplayName($this->display_name);
    $test_user->setGoogleProfileUrl($this->profile);
    $test_user->setGooglePhotoUrl($this->photo);
    $test_user->setLastUpdated($this->updated);
    $this->user = $test_user;

    $id = $test_user->getId();
    $google_id = $test_user->getGoogleUserId();
    $display_name = $test_user->getGoogleDisplayName();
    $profile = $test_user->getGoogleProfileUrl();
    $photo = $test_user->getGooglePhotoUrl();
    $updated = $test_user->getLastUpdated();

    $this->assertEquals($this->id, $id);
    $this->assertEquals($this->google_id, $google_id);
    $this->assertEquals($this->display_name, $display_name);
    $this->assertEquals($this->profile, $profile);
    $this->assertEquals($this->photo, $photo);
    $this->assertEquals($this->updated, $updated);
  }


  public function testSettingDataFromArray() {
    $test_user = new HaikuPlus\User();
    $test_user->updateUserId($this->id);
    $test_user->setGoogleUserId($this->google_id);
    $test_user->setGoogleDisplayName($this->display_name);
    $test_user->setGoogleProfileUrl($this->profile);
    $test_user->setGooglePhotoUrl($this->photo);
    $test_user->setLastUpdated($this->updated);

    $user_data = (array) $test_user;
    $user_from_data = HaikuPlus\User::fromArray($user_data);

    $id = $user_from_data->getId();
    $google_id = $user_from_data->getGoogleUserId();
    $display_name = $user_from_data->getGoogleDisplayName();
    $profile = $user_from_data->getGoogleProfileUrl();
    $photo = $user_from_data->getGooglePhotoUrl();
    $updated = $user_from_data->getLastUpdated();

    $this->assertEquals($this->id, $id);
    $this->assertEquals($this->google_id, $google_id);
    $this->assertEquals($this->display_name, $display_name);
    $this->assertEquals($this->profile, $profile);
    $this->assertEquals($this->photo, $photo);
    $this->assertEquals($this->updated, $updated);
  }


  public function testFreshData() {
    $test_user = new HaikuPlus\User();
    $created_string = '2013-11-21T04:00:00+0000';
    $created = HaikuPlus\Date::createFromString($created_string);
    $test_user->setLastUpdated($created);

    $date_string = '2013-11-21T02:00:00+0000';
    $time = HaikuPlus\Date::createFromString($date_string)->getTimestamp();
    $this->assertTrue($test_user->isNewerThanUnixTime($time));
  }


  public function testExpiredData() {
    $test_user = new HaikuPlus\User();
    $created_string = '2013-11-11T04:00:00+0000';
    $created = HaikuPlus\Date::createFromString($created_string);
    $test_user->setLastUpdated($created);

    $date_string = '2013-11-21T02:00:00+0000';
    $time = HaikuPlus\Date::createFromString($date_string)->getTimestamp();
    $this->assertFalse($test_user->isNewerThanUnixTime($time));
  }

}
