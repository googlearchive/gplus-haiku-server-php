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


use HaikuPlus\DirectedUserToUserEdge;

/**
 * A datastore abstraction API which understands the three data types that 
 * correspond to the resource types:
 *
 * Resources loaded from the datastore will always be copies.
 *
 * @author cartland@google.com (Chris Cartland)
 */
class Datastore {


  /**
   * SQLite database.
   */
  protected $db = null;


  /**
   * Create a datastore object from database object.
   *
   * @param Doctrine\DBAL\Connection $db database.
   */
  public function __construct($db) {
    $this->db = $db;
  }


  /**
   * Stores user data in database.
   *
   * Creates a new user with user['id'] if none exists.
   *
   * @param User $user User to be stored.
   */
  public function updateUser($user) {
    $data = (array) $user;
    $this->setWithId('users', $data);
  }


  /**
   * Loads user from database.
   *
   * @param string $id Haiku+ user ID.
   * @return User.
   */
  public function loadUserWithId($id) {
    $data = $this->loadWithId('users', $id);
    return User::fromArray($data);
  }


  /**
   * Loads user from database.
   *
   * @param string $google_id Google user ID.
   * @return User.
   */
  public function loadUserWithGoogleId($google_id) {
    $sql = 'SELECT * FROM users WHERE google_plus_id = ?';
    $data = $this->db->fetchAssoc($sql, array($google_id));
    return $data ? User::fromArray($data) : null;
  }


  /**
   * Deletes user.
   *
   * @param string $user_id Haiku+ user ID.
   */
  public function deleteUserWithId($user_id) {
    $this->deleteWithId('users', $user_id);
  }


  /**
   * Stores credential.
   *
   * @param string $user_id Haiku+ user ID.
   * @param stdClass $credential Object containing credential information.
   */
  public function setCredentialWithUserId($user_id, $credential) {
    $credential = array('credential' => json_encode($credential));
    $this->setWithUserId('credentials', $user_id, $credential);
  }


  /**
   * Load credential.
   *
   * @param string $user_id Haiku+ user ID.
   */
  public function loadCredentialWithUserId($user_id) {
    $data = $this->loadWithUserId('credentials', $user_id);
    return json_decode($data['credential']);
  }


  /**
   * Delete credential.
   *
   * @param string $user_id Haiku+ user ID.
   */
  public function deleteCredentialWithUserId($user_id) {
    $this->deleteWithUserId('credentials', $user_id);
  }


  /**
   * Creates or updates haiku.
   *
   * @param Haiku $haiku.
   */
  public function setHaiku($haiku) {
    $data = $this->convertHaikuObjectToData($haiku);
    $this->setWithId('haikus', $data);
  }


  /**
   * Convert Haiku to data for database.
   *
   * @param Haiku $haiku.
   * @return mixed[] Array of data to be put in database.
   */
  private function convertHaikuObjectToData($haiku) {
    // Convert haiku object to an array of values.
    $haiku_data = (array) $haiku;
    $keys = array('id', 'title', 'line_one', 'line_two', 'line_three', 'votes',
        'creation_time');
    $data = array_intersect_key($haiku_data, array_flip($keys));
    $data['author_id'] = isset($haiku_data['author']) ?
        $haiku_data['author']->getId() : null;
    return $data;
  }


  /**
   * Load haiku.
   *
   * @param string $id Haiku ID.
   * @return Haiku Haiku or null.
   */
  public function loadHaikuWithId($id) {
    $data = $this->loadWithId('haikus', $id);
    return $this->convertDataToHaikuObject($data);
  }


  /**
   * Convert haiku data from database.
   *
   * @param mixed[] Array of haiku data from datbase.
   * @return Haiku Haiku or null.
   */
  private function convertDataToHaikuObject($data) {
    if (!$data) {
      return null;
    }
    $author_id = $data['author_id'];
    // The haikus table only stores the author ID.
    // The author data is loaded separately.
    $data['author'] = (array) $this->loadUserWithId($author_id);
    // Ensure that votes are of type int.
    $data['votes'] = (int) $data['votes'];
    // Create a haiku object using the retrieved data.
    $haiku = Haiku::fromArray($data);
    return $haiku;
  }


  /**
   * Load all haikus.
   *
   * @return mixed[] Array of Haiku.
   */
  public function loadAllHaikus() {
    $list_data = $this->loadAll('haikus');
    $haikus = array();
    if (!$list_data) {
      return $haikus;
    }
    foreach ($list_data as $data) {
      $haikus[] = $this->convertDataToHaikuObject($data);
    }
    return $haikus;
  }


  /**
   * Increase vote by 1 and return haiku.
   *
   * @param string $id Haiku ID.
   * @return Haiku.
   */
  public function incrementVotesForHaikuId($id) {
    $sql = 'UPDATE haikus SET votes = votes + 1 WHERE id = ?';
    $count = $this->db->executeUpdate($sql, array($id));
    // $count is the number of modified entries.
    if ($count) {
      $haiku = $this->loadHaikuWithId($id);
      return $haiku;
    }
    return null;
  }


  /**
   * Delete haiku.
   *
   * @param string $id Haiku ID.
   * @return Haiku.
   */
  public function deleteHaikuWithId($haiku_id) {
    $this->deleteWithId('haikus', $haiku_id);
  }


  /**
   * Delete all haikus with author ID.
   *
   * @param string $user_id Haiku+ user ID.
   */
  public function deleteHaikusWithUserId($user_id) {
    $table_name = 'haikus';
    $this->db->delete($table_name, array('author_id' => $user_id));
  }


  /**
   * Store user to user edges in database.
   *
   * @param mixed[] $edges Array of DirectedUserToUserEdge.
   */
  public function setEdges($edges) {
    foreach ($edges as $edge) {
      $this->setWithId('edges', (array) $edge);
    }
  }


  /**
   * Load user to user edges from database.
   *
   * @param string $user User source.
   * @return mixed[] Array of DirectedUserToUserEdge.
   */
  public function loadEdgesForUser($user) {
    $id = $user->id;

    $sql = 'SELECT * FROM edges WHERE source_user_id = ?';
    $data = $this->db->fetchAll($sql, array($id));
    if (!$data) {
      return array();
    }

    $edges = array();
    foreach ($data as $datum) {
      $edges[] = DirectedUserToUserEdge::fromArray($datum);
    }
    return $edges;
  }


  /**
   * Delete edges for user.
   *
   * @param User $user Source user.
   */
  public function deleteEdgesForUser($user) {
    $id = $user->id;

    $sql = 'DELETE FROM edges WHERE source_user_id = ?';
    $this->db->fetchAll($sql, array($id));
  }


  /**
   * Put $data into $table_name with attribute user_id = $user_id.
   *
   * Creates a new entry or updates existing data with the same $user_id.
   *
   * @param string $table_name Database table name.
   * @param string $user_id Haiku+ user ID.
   * @param mixed[] $data Array of data to add or update.
   */
  protected function setWithUserId($table_name, $user_id, $data) {
    if (!array_key_exists('user_id', $data)) {
      $data['user_id'] = $user_id;
    }
    $existing_data = $this->loadWithUserId($table_name, $user_id);
    if ($existing_data) {
      $this->db->update($table_name, $data, array('user_id' => $user_id));
    } else {
      $this->db->insert($table_name, $data);
    }
  }


  /**
   * Loads $data from $table_name if $user_id is set.
   *
   * @param string $table_name Database table name. Must be sanitized.
   * @param string $user_id the Google data ID of the data to load.
   * @return mixed[] Array of data or null.
   */
  protected function loadWithUserId($table_name, $user_id) {
    $sql = 'SELECT * FROM ' . $table_name . ' WHERE user_id = ?';
    $data = $this->db->fetchAssoc($sql, array($user_id));
    return $data ? $data : null;
  }


  /**
   * Deletes a data from the datastore with user ID.
   *
   * @param string $table_name Database table name.
   * @param string $user_id Haiku+ user ID.
   */
  protected function deleteWithUserId($table_name, $user_id) {
    $this->db->delete($table_name, array('user_id' => $user_id));
  }


  /**
   * Updates existing data or adds new data.
   *
   * @param string $table_name Database table name.
   * @param mixed[] $data Array of data to add or update.
   */
  protected function setWithId($table_name, $data) {
    $id = $data['id'];
    $existing_data = $this->loadWithId($table_name, $id);
    if ($existing_data) {
      $this->db->update($table_name, $data,
                        array('id' => $id));
    } else {
      $this->db->insert($table_name, $data);
    }
  }


  /**
   * Loads a copy of a data from the datastore.
   *
   * @param string $table_name Database table name. Must be sanitized.
   * @param string $id ID of data.
   * @return mixed[] Array of data or null.
   */
  protected function loadWithId($table_name, $id) {
    $sql = 'SELECT * FROM ' . $table_name . ' WHERE id = ?';
    $data = $this->db->fetchAssoc($sql, array($id));
    return $data ? $data : null;
  }


  /**
   * Deletes a data from the datastore.
   *
   * @param string $id ID of the data to delete.
   */
  protected function deleteWithId($table_name, $id) {
    $this->db->delete($table_name, array('id' => $id));
  }


  /**
   * Loads all items from a table.
   *
   * @param string $table_name Database table name. Must be sanitized.
   * @return mixed[] Array of data in table or null.
   */
  protected function loadAll($table_name) {
    $sql = 'SELECT * FROM ' . $table_name . ' ORDER BY creation_time DESC';
    $data = $this->db->fetchAll($sql);
    return $data ? $data : null;
  }
}

