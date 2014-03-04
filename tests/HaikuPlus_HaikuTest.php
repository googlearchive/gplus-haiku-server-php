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


/*
 *   {
 *     "id":"",
 *     "author":User,
 *     "title":"",
 *     "line_one":"",
 *     "line_two":"",
 *     "line_three":"",
 *     "votes":"",
 *     "creation_time":Date
 *   }
 */
class HaikuPlus_HaikuTest extends \PHPUnit_Framework_TestCase {


  protected $id;
  protected $author;
  protected $title;
  protected $line_one;
  protected $line_two;
  protected $line_three;
  protected $votes;
  protected $creation_time;


  protected function setUp() {
    date_default_timezone_set('UTC');
    $this->id = 'abcd';
    $this->author = new HaikuPlus\User();
    $this->title = 'TITLE';
    $this->line_one = 'LINEONE';
    $this->line_two = 'LINETWO';
    $this->line_three = 'LINETHREE';
    $this->votes = 100;
    $date_string = '2013-11-21T04:00:00+0000';
    $this->creation_time= HaikuPlus\Date::createFromString($date_string);
  }


  public function testDefaultData() {
    $empty_haiku = new HaikuPlus\Haiku();
    $this->assertNotNull($empty_haiku->getId());
    $this->assertNull($empty_haiku->getAuthor());
    $this->assertNull($empty_haiku->getTitle());
    $this->assertNull($empty_haiku->getLineOne());
    $this->assertNull($empty_haiku->getLineTwo());
    $this->assertNull($empty_haiku->getLineThree());
    $this->assertTrue($empty_haiku->getVotes() === 0);
    $this->assertNull($empty_haiku->getCreationTime());
  }


  public function testCreateUniqueIds() {
    $haiku1 = new HaikuPlus\Haiku();
    $haiku2 = new HaikuPlus\Haiku();
    $this->assertNotEquals($haiku1->getId(), $haiku2->getId());
  }


  public function testSettingData() {
    $test_haiku = new HaikuPlus\Haiku();
    $test_haiku->id = $this->id;
    $test_haiku->setAuthor($this->author);
    $test_haiku->setTitle($this->title);
    $test_haiku->setLineOne($this->line_one);
    $test_haiku->setLineTwo($this->line_two);
    $test_haiku->setLineThree($this->line_three);
    $test_haiku->setVotes($this->votes);
    $test_haiku->setCreationTime($this->creation_time);
    $this->haiku = $test_haiku;

    $id = $test_haiku->getId();
    $author = $test_haiku->getAuthor();
    $title = $test_haiku->getTitle();
    $line_one = $test_haiku->getLineOne();
    $line_two = $test_haiku->getLineTwo();
    $line_three = $test_haiku->getLineThree();
    $votes = $test_haiku->getVotes();
    $creation_time = $test_haiku->getCreationTime();

    $this->assertEquals($this->id, $id);
    $this->assertEquals($this->author, $author);
    $this->assertEquals($this->title, $title);
    $this->assertEquals($this->line_one, $line_one);
    $this->assertEquals($this->line_two, $line_two);
    $this->assertEquals($this->line_three, $line_three);
    $this->assertEquals($this->votes, $votes);
    $this->assertEquals($this->creation_time, $creation_time);
  }


  public function testSettingDataFromArray() {
    $test_haiku = new HaikuPlus\Haiku();
    $test_haiku->id = $this->id;
    $test_haiku->setAuthor($this->author);
    $test_haiku->setTitle($this->title);
    $test_haiku->setLineOne($this->line_one);
    $test_haiku->setLineTwo($this->line_two);
    $test_haiku->setLineThree($this->line_three);
    $test_haiku->setVotes($this->votes);
    $test_haiku->setCreationTime($this->creation_time);

    $data = (array) $test_haiku;
    $haiku = HaikuPlus\Haiku::fromArray($data);

    $id = $haiku->getId();
    $author = $haiku->getAuthor();
    $title = $haiku->getTitle();
    $line_one = $haiku->getLineOne();
    $line_two = $haiku->getLineTwo();
    $line_three = $haiku->getLineThree();
    $votes = $haiku->getVotes();
    $creation_time = $haiku->getCreationTime();

    $this->assertEquals($this->id, $id);
    $this->assertEquals($this->author, $author);
    $this->assertEquals($this->title, $title);
    $this->assertEquals($this->line_one, $line_one);
    $this->assertEquals($this->line_two, $line_two);
    $this->assertEquals($this->line_three, $line_three);
    $this->assertEquals($this->votes, $votes);
    $this->assertEquals($this->creation_time, $creation_time);
  }


  public function testSettingNullVotesFromArray() {
    $test_haiku = new HaikuPlus\Haiku();

    $data = (array) $test_haiku;
    $data['votes'] = null;
    $haiku = HaikuPlus\Haiku::fromArray($data);

    $votes = $haiku->getVotes();

    $this->assertEquals(0, $votes);
  }

}
