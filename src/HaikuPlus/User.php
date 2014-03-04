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
 * User of the Haiku+ application, including their cached Google user data and
 * their Haiku+ user ID.
 *
 * Data members of this class will be exposed as JSON in the public API.
 *
 * @author cartland@google.com (Chris Cartland)
 */
class User {


  /**
   * Primary identifier of this User. Specific to Haiku+.
   */
  public $id;

  /**
   * Google ID for this User.
   */
  public $google_plus_id;

  /**
   * Display name that this User has chosen for Google products.
   */
  public $google_display_name;

  /**
   * Public Google+ profile photo URL for this User.
   */
  public $google_photo_url;

  /**
   * Public Google+ profile URL for this User.
   */
  public $google_profile_url;

  /**
   * Used to determine whether the User's cached Google data is "fresh" (less
   * than one day old).
   */
  public $last_updated = null;


  public function __construct() {
    // In practice, we recommend generating user IDs from a sequence, but for
    // the sake of brevity in this sample, we are using a unique identifier
    // based on the current time in milliseconds.
    $this->id = uniqid();
  }


  /**
   * Creates a User object from an array of data.
   *
   * @param mixed[] $data Array of key-value information.
   * @return User A new user.
   */
  public static function fromArray($data) {
    if (!$data) {
      return null;
    }
    $user = new User();
    $object_vars = get_object_vars($user);
    foreach ($data as $key => $value) {
      if (array_key_exists($key, $object_vars)) {
        if ($key == 'last_updated') {
          $user->$key = Date::createFromString($value);
        } else if ($value) {
          $user->$key = $value;
        }
      }
    }
    return $user;
  }


  /**
   * @param integer $time The number of seconds since the Unix Epoch.
   * @return boolean True if the cached Google user data newer than $time.
   */
  public function isNewerThanUnixTime($time) {
    if ($this->last_updated === null) {
      return false;
    }
    return $this->last_updated->getTimestamp() > $time;
  }

  public function updateUserId($id) {
    $this->id = $id;
  }

  public function setGoogleUserId($google_id) {
    $this->google_plus_id = $google_id;
  }

  public function setGoogleDisplayName($display_name) {
    $this->google_display_name = $display_name;
  }

  public function setGooglePhotoUrl($photo_url) {
    $this->google_photo_url = $photo_url;
  }

  public function setGoogleProfileUrl($profile_url) {
    $this->google_profile_url = $profile_url;
  }

  public function setLastUpdated($date) {
    $this->last_updated = $date;
  }

  public function getId() {
    return $this->id;
  }

  public function getGoogleUserId() {
    return $this->google_plus_id;
  }

  public function getGoogleDisplayName() {
    return $this->google_display_name;
  }

  public function getGooglePhotoUrl() {
    return $this->google_photo_url;
  }

  public function getGoogleProfileUrl() {
    return $this->google_profile_url;
  }

  public function getLastUpdated() {
    return $this->last_updated;
  }
}

