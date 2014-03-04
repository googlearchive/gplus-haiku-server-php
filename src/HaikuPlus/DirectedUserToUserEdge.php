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


/**
 *
 * Data members of this class will be exposed as JSON in the public API.
 *
 * @author cartland@google.com (Chris Cartland)
 */
class DirectedUserToUserEdge {


  /**
   * Primary identifier of this User. Specific to Haiku+.
   */
  public $id;


  /**
   * ID of the User with another user in their circles.
   */
  public $source_user_id;


  /**
   * ID of User in source user's circles.
   */
  public $target_user_id;


  /**
   * Instantiate an edge.
   *
   * @param string $source Haiku+ user ID.
   * @param string $target Haiku+ user ID.
   */
  public function __construct($source, $target) {
    // In practice, we recommend generating edge IDs from a sequence, but for
    // the sake of brevity in this sample, we are using a unique identifier
    // based on the current time in microseconds.
    $this->id = uniqid();
    if ($source) {
      $this->setSourceId($source->getId());
    }
    if ($target) {
      $this->setTargetId($target->getId());
    }
  }


  /**
   * Creates an edge from data.
   *
   * @param mixed[] $data Array of edge data.
   * @return DirectedUserToUserEdge.
   */
  public static function fromArray($data) {
    if (!$data) {
      return null;
    }
    $edge = new DirectedUserToUserEdge(null, null);
    $object_vars = get_object_vars($edge);
    foreach ($data as $key => $value) {
      if (array_key_exists($key, $object_vars)) {
        if ($value) {
          $edge->$key = $value;
        }
      }
    }
    return $edge;
  }


  public function setSourceId($id) {
    $this->source_user_id = $id;
  }


  public function setTargetId($id) {
    $this->target_user_id = $id;
  }


  public function getId() {
    return $this->id;
  }


  public function getSourceId() {
    return $this->source_user_id;
  }


  public function getTargetId() {
    return $this->target_user_id;
  }

}

