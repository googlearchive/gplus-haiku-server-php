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


$app = require_once __DIR__ . '/bootstrap.php';


use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Route to render index page in HTML.
 *
 * @return string HTML response.
 */
$app->get('', function () use ($app) {
  return $app['twig']->render('splash.html', array(
    'YOUR_CLIENT_ID' => $app['hp.client_id'],
    'APP_BASE_URI' => $app['hp.config']['APP_BASE_URI'],
    'PAGE_METADATA' => ''
  ));
});


/**
 * Route to render haikus page in HTML.
 *
 * @return string HTML response.
 */
$app->get('/haikus', function () use ($app) {
  return $app['twig']->render('template.html', array(
    'YOUR_CLIENT_ID' => $app['hp.client_id'],
    'APP_BASE_URI' => $app['hp.config']['APP_BASE_URI'],
    'PAGE_METADATA' => ''
  ));
});


/**
 * Route to render a single haiku.
 *
 * The HTML response must contain Schema.org meta-data for the haiku.
 *
 * @param Request $request Silex request.
 * @param string $id Haiku ID.
 * @return string HTML response.
 */
$app->get('/haikus/{id}', function (Request $request, $id) use ($app) {
  $haiku = HaikuPlus\HaikuController::getHaiku($request, $app, $id);
  if ($haiku) {
    $haiku_id = $haiku->getId();
    $title = $haiku->getTitle();
    $line_one = $haiku->getLineOne();
    $line_two = $haiku->getLineTwo();
    $line_three = $haiku->getLineThree();
    $author = $haiku->getAuthor();
    $author_name = $author->getGoogleDisplayName();
    if ($author) {
      $title = 'Haiku+: ' . $title . ' by ' . $author_name;
    } else {
      $title = 'Haiku+: ' . $title;
    }
    $description = $line_one . ' ' . $line_two . ' ' . $line_three;
    $image_url = $author->getGooglePhotoUrl();
    $haiku_url = $app['hp.config']['APP_BASE_URI'] . '/haikus/' . $haiku_id;
    $schema_meta_tags = <<<METADATA
<meta itemprop="name" content="$title"/>
<meta itemprop="description" content="$description"/>
<meta itemprop="image" content="$image_url"/>
<link itemprop="url" href="$haiku_url">
METADATA;
  }
  // TODO(cartland): Put in template after format is finalized.
  return $app['twig']->render('template.html', array(
    'YOUR_CLIENT_ID' => $app['hp.client_id'],
    'APP_BASE_URI' => $app['hp.config']['APP_BASE_URI'],
    'PAGE_METADATA' => $schema_meta_tags
  ));
});


/**
 * Provides an API endpoint for retrieving the profile of the currently
 * signed in user.
 *
 *   GET /api/users/me
 *
 * Returns a user resource for the currently authenticated user.
 *
 * {
 *   "id":"",
 *   "google_plus_id":"",
 *   "google_display_name":"",
 *   "google_photo_url":"",
 *   "google_profile_url":"",
 *   "last_updated":""
 * }
 *
 * @param Request $request Silex request.
 * @return JsonResponse JSON response with user information.
 * @throws HttpException
 */
$app->get('/api/users/me', function (Request $request) use ($app) {
  $user = HaikuPlus\Auth::authenticate($request, $app);
  return $app->json($user, HaikuPlus\Auth::HTTP_OK);
});


/**
 * Provides an API endpoint for retrieving haikus.
 *
 *   GET /api/haikus?[filter=circles]
 *
 * Returns a list of haiku resources.
 *
 * [
 *   {
 *     "id":"",
 *     "author": User,
 *     "title":"",
 *     "line_one":"",
 *     "line_two":"",
 *     "line_three":"",
 *     "votes":"",
 *     "creation_time":""
 *   }
 * ]
 *
 * @param Request $request Silex request.
 * @return JsonResponse JSON response with a list of haikus.
 * @throws HttpException
 */
$app->get('/api/haikus', function (Request $request) use ($app) {
  $haikus = array();
  $filter = $request->query->get('filter');
  if ($filter == 'circles') {
    $user = HaikuPlus\Auth::authenticate($request, $app);
    $haikus = HaikuPlus\HaikuController::fetchFilteredHaikus($request, $app,
        $user);
  } else {
    $haikus = HaikuPlus\HaikuController::fetchHaikus($request, $app);
  }
  return $app->json($haikus, HaikuPlus\Auth::HTTP_OK);
});


/**
 * Provides an API endpoint for creating haikus.
 *
 *   POST /api/haikus
 *
 * POST body
 *   {
 *     "title":"",
 *     "line_one":"",
 *     "line_two":"",
 *     "line_three":"",
 *   }
 *
 * Returns the created haiku resource.
 *   {
 *     "id":"",
 *     "author": User,
 *     "title":"",
 *     "line_one":"",
 *     "line_two":"",
 *     "line_three":"",
 *     "votes":"",
 *     "creation_time":""
 *   }
 *
 * @param Request $request Silex request.
 * @return JsonResponse JSON response with newly created haiku.
 * @throws HttpException
 */
$app->post('/api/haikus', function (Request $request) use ($app) {
  if ($app['hp.config']['DEMO']) {
    throw new HttpException(HaikuPlus\Auth::HTTP_METHOD_NOT_ALLOWED,
        'Cannot create haikus during demo.');
  }
  $user = HaikuPlus\Auth::authenticate($request, $app);
  $haiku = HaikuPlus\HaikuController::createFromData($request, $app, $user);
  return $app->json($haiku, HaikuPlus\Auth::HTTP_OK);
});


/**
 * Provides an API endpoint for getting a single haiku.
 *
 *   GET /api/haikus/{ID}
 *
 * Returns the haiku resource.
 *   {
 *     "id":"",
 *     "author": User,
 *     "title":"",
 *     "line_one":"",
 *     "line_two":"",
 *     "line_three":"",
 *     "votes":"",
 *     "creation_time":""
 *   }
 *
 * @param Request $request Silex request.
 * @param string $id Haiku ID.
 * @return JsonResponse JSON response with haiku.
 */
$app->get('/api/haikus/{id}', function (Request $request, $id) use ($app) {
  $haiku = HaikuPlus\HaikuController::getHaiku($request, $app, $id);
  return $app->json($haiku, HaikuPlus\Auth::HTTP_OK);
});


/**
 * Provides an API endpoint for voting for a haiku.
 *
 *   GET /api/haikus/{ID}/vote
 *
 * Returns the haiku resource.
 *   {
 *     "id":"",
 *     "author": User,
 *     "title":"",
 *     "line_one":"",
 *     "line_two":"",
 *     "line_three":"",
 *     "votes":"",
 *     "creation_time":""
 *   }
 *
 * @param Request $request Silex request.
 * @param string $id Haiku ID.
 * @return JsonResponse JSON response with haiku that received the vote.
 */
$app->post('/api/haikus/{id}/vote', function (Request $request, $id)
    use ($app) {
  $user = HaikuPlus\Auth::authenticate($request, $app);
  $haiku = HaikuPlus\HaikuController::voteForHaiku($request, $app, $id, $user);
  return $app->json($haiku, HaikuPlus\Auth::HTTP_OK);
});


/**
 * Provides an API endpoint for signing out the current user.
 *
 *   POST /api/signout
 *
 * Disassociates any authentication information with the current session.
 *
 * @param Request $request Silex request.
 * @return JsonResponse empty JSON response with HTTP status 200.
 */
$app->post('/api/signout', function (Request $request) use ($app) {
  HaikuPlus\Auth::signOut($request, $app);
  return $app->json(null, HaikuPlus\Auth::HTTP_OK);
});


/**
 * Provides an API endpoint for revoking Google authorization and signing out.
 *
 *   POST /api/disconnect
 *
 * Deletes all Google API data and haikus.
 *
 * @param Request $request Silex request.
 * @return JsonResponse empty JSON response with HTTP status 200.
 */
$app->post('/api/disconnect', function (Request $request) use ($app) {
  $user = HaikuPlus\Auth::authenticate($request, $app);
  HaikuPlus\Auth::disconnect($request, $app, $user);
  return $app->json(array('message' => 'Successfully disconnected.'),
      HaikuPlus\Auth::HTTP_OK);
});


/**
 * Exceptions should return a JSON message.
 *
 * @param HaikuPlus\HttpException $e Contains the desired response.
 * @param integer $code Status code supplied by Silex.
 * @return Response Contains error information.
 */
$app->error(function (HttpException $e, $code) use ($app) {
  $message = array('message' => $e->getMessage());
  $status_code = $e->getStatusCode();
  return $app->json($message, $status_code, $e->getHeaders());
});


return $app;

