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


class HaikuPlus_DirectedUserToUserEdgeTest extends \PHPUnit_Framework_TestCase {


  protected $id;
  protected $source_user_id;
  protected $target_user_id;


  protected function setUp() {
    $this->id = 'abcd';
    $this->source_user_id = 'efgh';
    $this->target_user_id = 'ijkl';
  }


  public function testDefaultData() {
    $empty_edge = new HaikuPlus\DirectedUserToUserEdge(null, null);
    $this->assertNotNull($empty_edge->getId());
    $this->assertNull($empty_edge->getSourceId());
    $this->assertNull($empty_edge->getTargetId());
  }


  public function testCreateUniqueIds() {
    $edge1 = new HaikuPlus\DirectedUserToUserEdge(null, null);
    $edge2 = new HaikuPlus\DirectedUserToUserEdge(null, null);
    $this->assertNotEquals($edge1->getId(), $edge2->getId());
  }


  public function testSettingData() {
    $test_edge = new HaikuPlus\DirectedUserToUserEdge(null, null);
    $test_edge->id = $this->id;
    $test_edge->setSourceId($this->source_user_id);
    $test_edge->setTargetId($this->target_user_id);
    $this->edge = $test_edge;

    $id = $test_edge->getId();
    $source_user_id = $test_edge->getSourceId();
    $target_user_id = $test_edge->getTargetId();

    $this->assertEquals($this->id, $id);
    $this->assertEquals($this->source_user_id, $source_user_id);
    $this->assertEquals($this->target_user_id, $target_user_id);
  }


  public function testSettingDataFromArray() {
    $test_edge = new HaikuPlus\DirectedUserToUserEdge(null, null);
    $test_edge->id = $this->id;
    $test_edge->setSourceId($this->source_user_id);
    $test_edge->setTargetId($this->target_user_id);
    $this->edge = $test_edge;

    $edge_data = (array) $test_edge;
    $edge_from_data = HaikuPlus\DirectedUserToUserEdge::fromArray($edge_data);

    $id = $edge_from_data->getId();
    $source_user_id = $edge_from_data->getSourceId();
    $target_user_id = $edge_from_data->getTargetId();

    $this->assertEquals($this->id, $id);
    $this->assertEquals($this->source_user_id, $source_user_id);
    $this->assertEquals($this->target_user_id, $target_user_id);
  }


  public function testSettingFromUsers() {
    $source = new HaikuPlus\User();
    $source->id = $this->source_user_id;
    $target = new HaikuPlus\User();
    $target->id = $this->target_user_id;
    $edge = new HaikuPlus\DirectedUserToUserEdge($source, $target);

    $source_user_id = $edge->getSourceId();
    $target_user_id = $edge->getTargetId();

    $this->assertEquals($this->source_user_id, $source_user_id);
    $this->assertEquals($this->target_user_id, $target_user_id);
    return $edge;
  }

}
