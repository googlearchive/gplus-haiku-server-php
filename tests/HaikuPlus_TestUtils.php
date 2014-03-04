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


include_once __DIR__ . '/DynamicObject.php';


class HaikuPlus_TestUtils {


  /*
   * Deletes database at $name and creates an empty test database.
   * @param app Silex application.
   * @param filename filename of test database.
   */
  public static function registerCleanDatabase($app, $filename) {
    // Database.
    $app->register(new Silex\Provider\DoctrineServiceProvider(), array(
      'db.options' => array(
        'driver'   => 'pdo_sqlite',
        'path'     => __DIR__ . "/$filename",
      ),
    ));
    if (file_exists($app['db.options']['path'])) {
      unlink($app['db.options']['path']);
    }
    $db_init_query = <<<QUERY
CREATE TABLE credentials
(
  user_id VARCHAR(64),
  credential VARCHAR(65536)
);
QUERY;
    $statement = $app['db']->executeQuery($db_init_query);
    $statement->fetch();

    $db_init_query = <<<QUERY
CREATE TABLE users
(
  id VARCHAR(64),
  google_plus_id VARCHAR(32),
  google_display_name VARCHAR(128),
  google_photo_url VARCHAR(512),
  google_profile_url VARCHAR(512),
  last_updated DATE
);
QUERY;
    $statement = $app['db']->executeQuery($db_init_query);
    $statement->fetch();

    $db_erase_query = 'DELETE FROM users WHERE 1;';
    $statement = $app['db']->executeQuery($db_erase_query);
    $statement->fetch();

    $db_init_query = <<<QUERY
CREATE TABLE haikus
(
  id VARCHAR(64),
  author_id VARCHAR(64),
  title VARCHAR(512),
  line_one VARCHAR(512),
  line_two VARCHAR(512),
  line_three VARCHAR(512),
  votes INT,
  creation_time DATE
);
QUERY;
    $statement = $app['db']->executeQuery($db_init_query);
    $statement->fetch();

    $db_init_query = <<<QUERY
CREATE TABLE edges
(
  id VARCHAR(64),
  source_user_id VARCHAR(64),
  target_user_id VARCHAR(64)
);
QUERY;
    $statement = $app['db']->executeQuery($db_init_query);
    $statement->fetch();
  }


  public static function getVerifiedIdTokenResponse($jwt) {
    // Server should verify ID token.
    $getAttributes = function() use ($jwt) {
      return $jwt;
    };
    $verified = new DynamicObject();
    $verified->getAttributes = $getAttributes;
    return $verified;
  }
}
