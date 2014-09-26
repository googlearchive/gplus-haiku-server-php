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


class HaikuPlus_DateTest extends \PHPUnit_Framework_TestCase {


  protected $date_string;
  protected $alternate_string;
  protected $other_string;


  protected function setUp() {
    date_default_timezone_set('UTC');
    $this->date_string = '2013-11-21T04:00:00+0000';
    $this->alternate_string = '2013-11-21T06:00:00+0200';
    $this->other_string = '2013-11-21T04:00:00+0800';
  }


  public function testHasOneDayInS() {
    $this->assertEquals(86400, HaikuPlus\Date::ONE_DAY_IN_SECONDS);
  }


  public function testCreateNow() {
    $now = HaikuPlus\Date::now();
    $this->assertNotNull($now);
  }


  public function testTimezone() {
    $expected = HaikuPlus\Date::createFromString($this->date_string);
    $alternate = HaikuPlus\Date::createFromString($this->alternate_string);
    $expected_timestamp = $expected->getTimestamp();
    $alternate_timestamp = $alternate->getTimestamp();
    $this->assertEquals($expected_timestamp, $alternate_timestamp);
  }


  public function testCreateFromString() {
    $normal_date = DateTime::createFromFormat(DateTime::ISO8601,
        $this->date_string);
    $expected = $normal_date->getTimestamp();

    $date = HaikuPlus\Date::createFromString($this->date_string);

    $this->assertNotNull($date);
    $this->assertEquals($expected, $date->getTimestamp());
  }


  public function testCreateFromNullString() {
    $null_date = HaikuPlus\Date::createFromString(null);
    $this->assertNull($null_date);
  }


  public function testJsonSerialize() {
    $expected = HaikuPlus\Date::createFromString($this->date_string);
    $not_expected = HaikuPlus\Date::createFromString($this->other_string);

    $serialized = $expected->jsonSerialize();
    $date = HaikuPlus\Date::createFromString($serialized);

    $this->assertNotNull($date);
    $this->assertEquals($expected, $date);
    $this->assertNotEquals($not_expected, $date);
  }

}
