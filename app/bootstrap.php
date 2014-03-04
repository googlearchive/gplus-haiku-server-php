<?php
/*
 *
 * Copyright 2014 Google Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */


/**
 * Bootstrap the app by creating app globals, registering template views, the
 * database, creating database tables if they do not exist, reading
 * configuration files, preparing the Google+ client object, and
 * creating the Silex app object.
 *
 * @author cartland@google.com (Chris Cartland)
 */


$app = new Silex\Application();


$app['debug'] = true;


/**
 * Views directory for templating.
 */
$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__ . '/views',
));


/**
 * Sessions.
 */
$app->register(new Silex\Provider\SessionServiceProvider());


/**
* Session ID cookie name.
*/
$app['session.storage.options'] = array('name' => 'HaikuSessionId');


$database_filename = __DIR__ . '/../data/database.db';


if (!is_writeable($database_filename)) {
  echo "Cannot write to database file '$database_filename'. ";
  echo "Have you created a writeable directory 'data'?";
}


// Database.
$app->register(new Silex\Provider\DoctrineServiceProvider(), array(
  'db.options' => array(
    'driver'   => 'pdo_sqlite',
    'path'     => $database_filename,
  ),
));


if (!file_exists($app['db.options']['path'])) {
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


/**
 * Update client_secrets.json from https://developers.google.com/console
 */
$client_secrets = json_decode(
    file_get_contents(__DIR__ . '/../client_secrets.json'), true);


/**
 * Client ID from the Google APIs console.
 */
$app['hp.client_id'] = $client_secrets['web']['client_id'];


/**
 * Client secret from the Google APIs console.
 */
$app['hp.client_secret'] = $client_secrets['web']['client_secret'];


/**
 * Optionally replace this with your application's name.
 */
$app['hp.application_name'] = 'Haiku+';


$client = new Google_Client();
$client->setApplicationName($app['hp.application_name']);
$client->setClientId($app['hp.client_id']);
$client->setClientSecret($app['hp.client_secret']);
$client->setRedirectUri('postmessage');


$app['hp.client'] = $client;


$app['hp.plus'] = new Google_Service_Plus($client);


$app['hp.datastore'] = new HaikuPlus\Datastore($app['db']);


/**
 * Configuration contains a DEMO flag.
 * Haikus cannot be created when set to True.
 */
$config = json_decode(
    file_get_contents(__DIR__ . '/../config.json'), true);


$app['hp.config'] = $config;


return $app;

