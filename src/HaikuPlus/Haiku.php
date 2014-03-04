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
 * Haiku
 *
 * Data members of this class will be exposed as JSON in the public API.
 *
 * @author cartland@google.com (Chris Cartland)
 */
class Haiku {

  /**
   * Primary identifier of this Haiku.
   */
  public $id;

  /**
   * User.
   */
  public $author;

  /**
   * Haiku title.
   */
  public $title;

  /**
   * Line one.
   */
  public $line_one;

  /**
   * Line two.
   */
  public $line_two;

  /**
   * Line three.
   */
  public $line_three;

  /**
   * Number of votes.
   */
  public $votes;

  /**
   * Date.
   */
  public $creation_time;

  /**
   * Content URL for sharing.
   */
  public $content_url;

  /**
   * Content deep link ID for sharing.
   */
  public $content_deep_link_id;

  /**
   * Call-to-action URL for sharing.
   */
  public $call_to_action_url;

  /**
   * Call-to-action deep link ID for sharing.
   */
  public $call_to_action_deep_link_id;

  /**
   * Creates a new haiku with 0 votes and a unique ID.
   */
  public function __construct() {
    // In practice, we recommend generating haiku IDs from a sequence, but for
    // the sake of brevity in this sample, we are using a unique identifier
    // based on the current time in milliseconds.
    $id = uniqid();
    // Sets URLs and deep link IDs.
    $this->setId($id);
    $this->votes = 0;
  }


  /**
   * Creates a haiku from data.
   *
   * @param mixed[] $data Array of haiku data.
   * @return Haiku.
   */
  public static function fromArray($data) {
    if (!$data) {
      return null;
    }
    $haiku = new Haiku();
    $object_vars = get_object_vars($haiku);
    foreach ($data as $key => $value) {
      if (array_key_exists($key, $object_vars)) {
        switch ($key) {
          case 'creation_time':
            $haiku->$key = Date::createFromString($value);
            break;
          case 'author':
            $haiku->$key = User::fromArray($value);
            break;
          case 'votes':
            $haiku->$key = (int) $value;
            break;
          case 'id':
            $haiku->setId($value);
            break;
          case 'title':
            $value = $value or '';
            $haiku->setTitle($value);
            break;
          case 'line_one':
            $value = $value or '';
            $haiku->setLineOne($value);
            break;
          case 'line_two':
            $value = $value or '';
            $haiku->setLineTwo($value);
            break;
          case 'line_three':
            $value = $value or '';
            $haiku->setLineThree($value);
            break;
          default:
            if ($value) {
              $haiku->$key = $value;
            }
        }
      }
    }
    return $haiku;
  }

  public function setId($id) {
    $this->id = $id;
  }

  /**
   * Sets object properties with sharing links. Example $base_url:
   * 'https://example.com'
   * Content Deep Link ID: /haikus/ID
   * Content URL: https://example.com/haikus/ID
   * Call-to-action Deep Link ID: /haikus/ID?action=vote
   * Call-to-action URL: https://example.com/haikus/ID?action=vote
   *
   * @param $base_url string App base URI without trailing slash.
   */
  public function updateUrlsAndDeepLinkIds($base_url) {
    $content_deep_link_id = '/haikus/' . $this->getId();
    $call_to_action_deep_link_id = $content_deep_link_id . '?action=vote';
    $content_url = $base_url . $content_deep_link_id;
    $call_to_action_url = $base_url . $call_to_action_deep_link_id;
    $this->setContentUrl($content_url);
    $this->setContentDeepLinkId($content_deep_link_id);
    $this->setCallToActionUrl($call_to_action_url);
    $this->setCallToActionDeepLinkId($call_to_action_deep_link_id);
  }

  public function setAuthor($author) {
    $this->author = $author;
  }

  public function setTitle($title) {
    $this->title = $title;
  }

  public function setLineOne($line_one) {
    $this->line_one = $line_one;
  }

  public function setLineTwo($line_two) {
    $this->line_two = $line_two;
  }

  public function setLineThree($line_three) {
    $this->line_three = $line_three;
  }

  public function setVotes($votes) {
    $this->votes = $votes;
  }

  public function setCreationTime($creation_time) {
    $this->creation_time = $creation_time;
  }

  public function setContentUrl($content_url) {
    $this->content_url = $content_url;
  }

  public function setContentDeepLinkId($content_deep_link_id) {
    $this->content_deep_link_id = $content_deep_link_id;
  }

  public function setCallToActionUrl($call_to_action_url) {
    $this->call_to_action_url = $call_to_action_url;
  }

  public function setCallToActionDeepLinkId($call_to_action_deep_link_id) {
    $this->call_to_action_deep_link_id = $call_to_action_deep_link_id;
  }

  public function getId() {
    return $this->id;
  }

  public function getAuthor() {
    return $this->author;
  }
  public function getTitle() {
    return $this->title;
  }

  public function getLineOne() {
    return $this->line_one;
  }

  public function getLineTwo() {
    return $this->line_two;
  }

  public function getLineThree() {
    return $this->line_three;
  }

  public function getVotes() {
    return $this->votes;
  }

  public function getCreationTime() {
    return $this->creation_time;
  }

  public function getContentUrl() {
    return $this->content_url;
  }

  public function getContentDeepLinkId() {
    return $this->content_deep_link_id;
  }

  public function getCallToActionUrl() {
    return $this->call_to_action_url;
  }

  public function getCallToActionDeepLinkId() {
    return $this->call_to_action_deep_link_id;
  }

}

