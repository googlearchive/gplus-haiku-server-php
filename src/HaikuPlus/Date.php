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


use DateTime;
use JsonSerializable;

/**
 * Serializable date object using standard format ISO8601.
 *
 * This object should be immutable. It wraps a DateTime object in order
 * to provide a serializeable interface in the correct format.
 *
 * @author cartland@google.com (Chris Cartland)
 */
class Date implements JsonSerializable {


  /**
   * The DateTime object that this Date class wraps.
   */
  private $date_time;


  const ONE_DAY_IN_SECONDS = 86400;


  /**
   * Private constructor takes DateTime.
   *
   * @param DateTime $date_time.
   */
  private function __construct($date_time) {
    $this->date_time = $date_time;
  }


  /**
   * Construct a new object with the current time.
   *
   * @return Date Current time.
   */
  public static function now() {
    $date = new Date(new DateTime('NOW'));
    return $date;
  }


  /**
   * Construct a new object from a string.
   *
   * @param string $time Formatted as ISO8601, e.g. "2013-11-21T20:59:06+0000".
   * @return Date Time in string.
   */
  public static function createFromString($time) {
    if (!$time) {
      return null;
    }
    $date_time = DateTime::createFromFormat(DateTime::ISO8601, $time);
    $date = new Date($date_time);
    return $date;
  }


  /**
   * Returns the Unix timestamp in milliseconds.
   *
   * @return integer Milliseconds.
   */
  public function getTimestamp() {
    return $this->date_time->getTimestamp();
  }


  /**
   * Serialize the date object using as ISO8601.
   *
   * @return string, e.g. "2013-11-21T20:59:06+0000"
   */
  public function jsonSerialize() {
    return $this->date_time->format(DateTime::ISO8601);
  }


  /**
   * Date as ISO8601.
   */
  public function __toString() {
    return $this->jsonSerialize();
  }
}

