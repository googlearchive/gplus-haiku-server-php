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


use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Google_Service_Plus_Moment;
use Google_Service_Plus_ItemScope;
use Google_Auth_Exception;
use Google_IO_Exception;
use Google_Service_Exception;

/**
 * Static methods that process Silex app requests related to haikus.
 *
 * Provides haiku listing, fetching, filtering, creation, and voting.
 *
 * @author cartland@google.com (Chris Cartland)
 */
class HaikuController {


  /**
   * Retrieves a haiku.
   *
   * @param Request $request Silex request.
   * @param Silex\Application $app Silex app.
   * @return Haiku or null if it does not exist.
   * @throws HttpException.
   */
  public static function getHaiku($request, $app, $haiku_id) {
    $haiku = $app['hp.datastore']->loadHaikuWithId($haiku_id);
    if (!$haiku) {
      throw new NotFoundHttpException('Haiku not found');
    }
    $haiku->updateUrlsAndDeepLinkIds($app['hp.config']['APP_BASE_URI']);
    return $haiku;
  }


  /**
   * Retrieves an unfiltered list of haikus.
   *
   * @param Request $request Silex request.
   * @param Silex\Application $app Silex app.
   * @return mixed[] Array of Haiku objects.
   * @throws HttpException.
   */
  public static function fetchHaikus($request, $app) {
    $handler = new HaikuController($request, $app);
    return $handler->fetchAllHaikus();
  }


  /**
   * Retrieves a list of haikus filtered by people in circles.
   *
   * @param Request $request Silex request.
   * @param Silex\Application $app Silex app.
   * @param User $user The user making the request.
   * @return mixed[] Array of Haiku objects.
   * @throws HttpException.
   */
  public static function fetchFilteredHaikus($request, $app, $user) {
    $handler = new HaikuController($request, $app);
    return $handler->handleFilterHaikus($user);
  }


  /**
   * Creates a haiku.
   *
   * @param Request $request Silex request.
   * @param Silex\Application $app Silex app.
   * @param User $author The user making the request.
   * @return Haiku object.
   * @throws HttpException.
   */
  public static function createFromData($request, $app, $author) {
    $handler = new HaikuController($request, $app);
    return $handler->createHaikuWithAuthor($author);
  }


  /**
   * Vote for a haiku.
   *
   * @param Request $request Silex request.
   * @param Silex\Application $app Silex app.
   * @param string $haiku_id The ID of the haiku receiving a vote.
   * @param User $user The user voting for the haiku.
   * @return Haiku object.
   * @throws HttpException.
   */
  public static function voteForHaiku($request, $app, $haiku_id, $user) {
    $handler = new HaikuController($request, $app);
    return $handler->voteForHaikuWithUser($haiku_id, $user);
  }


  /**
   * Silex request.
   */
  private $request;


  /**
   * Silex app.
   */
  private $app;


  /**
   * Private constructor to help manage request and app data in static methods.
   *
   * @param Request $request Silex request.
   * @param Silex\Application $app Silex app.
   */
  private function __construct($request, $app) {
    $this->request = $request;
    $this->app = $app;
  }


  /**
   * Helper method to retrieve all haikus in database.
   *
   * @return mixed[] Array of all haikus in database.
   */
  private function fetchAllHaikus() {
    $app = $this->app;
    $haikus = $app['hp.datastore']->loadAllHaikus();
    $base = $app['hp.config']['APP_BASE_URI'];
    foreach ($haikus as $haiku) {
      $haiku->updateUrlsAndDeepLinkIds($base);
    }
    return $haikus;
  }


  /**
   * Helper method for HaikuController::fetchFilteredHaikus().
   *
   * @param user $user The user making the request.
   * @return mixed[] Array of filtered haikus.
   */
  private function handleFilterHaikus($user) {
    $request = $this->request;
    $app = $this->app;
    if (!$user) {
      return array();
    }
    $haikus = $this->fetchAllHaikus();
    $user_id = $user->getId();
    $credential = $app['hp.datastore']->loadCredentialWithUserId($user_id);
    try {
      $this->updateCircleCache($user, $credential);
    } catch (Google_Auth_Exception $e) {
      // Fullfill the request even if the API call fails.
    }

    $edges = $app['hp.datastore']->loadEdgesForUser($user);
    $filtered = array();
    foreach ($haikus as $haiku) {
      foreach ($edges as $edge) {
        $author = $haiku->getAuthor();
        if ($author) {
          $author_id = $author->getId();
          $edge_target = $edge->getTargetId();
          if ($author_id == $edge_target) {
            // Add haiku to array.
            $filtered[] = $haiku;
          }
        }
      }
    }
    return $filtered;
  }


  /**
   * Helper method for HaikuController::createFromData().
   *
   * @param User $author The user making the request.
   * @return Haiku.
   */
  private function createHaikuWithAuthor($author) {
    if (!$author) {
      throw new HttpException(Auth::HTTP_INTERNAL_SERVER_ERROR,
          'No author found.');
    }
    $request = $this->request;
    $app = $this->app;
    $content = $request->getContent();
    $data = json_decode($content, true);
    $haiku = $this->cleanHaikuFromDataAndAuthor($data, $author);
    $app['hp.datastore']->setHaiku($haiku);

    $base = $this->app['hp.config']['APP_BASE_URI'];
    $haiku->updateUrlsAndDeepLinkIds($base);
    $url = $haiku->getContentUrl();
    $type = 'http://schemas.google.com/AddActivity';
    try {
      $this->writeAppActivity($url, $type);
    } catch (Google_Auth_Exception $e) {
      // Create the haiku even if the API call fails.
    } catch (Google_IO_Exception $e) {
      // Create the haiku even if the API call fails.
    } catch (Google_Service_Exception $e) {
      // Create the haiku even if the API call fails.
    }
    return $haiku;
  }


  private function cleanHaikuFromDataAndAuthor($haiku_data, $author) {
    // Only keep fields with these keys.
    $keys = array('title', 'line_one', 'line_two', 'line_three');
    $data = array_intersect_key($haiku_data, array_flip($keys));
    $haiku = Haiku::fromArray($data);

    $haiku->setAuthor($author);
    $haiku->setVotes(0);
    $haiku->setCreationTime(Date::now());
    return $haiku;

  }


  /**
   * Increases vote count for haiku by 1.
   *
   * @param string $haiku_id The ID of the haiku receiving a vote.
   * @param User $user The user voting for the haiku.
   * @return Haiku object.
   * @throws HttpException.
   */
  private function voteForHaikuWithUser($haiku_id, $user) {
    $app = $this->app;
    $haiku = $app['hp.datastore']->incrementVotesForHaikuId($haiku_id);
    if (!$haiku) {
      throw new NotFoundHttpException('Haiku not found');
    }
    $base = $app['hp.config']['APP_BASE_URI'];
    $haiku->updateUrlsAndDeepLinkIds($base);
    $url = $haiku->getContentUrl();
    $type = 'http://schemas.google.com/ReviewActivity';
    try {
      $this->writeAppActivity($url, $type);
    } catch (Google_Auth_Exception $e) {
      // Vote for the haiku even if the API call fails.
    } catch (Google_IO_Exception $e) {
      // Vote for the haiku even if the API call fails.
    } catch (Google_Service_Exception $e) {
      // Vote for the haiku even if the API call fails.
    }
    return $haiku;
  }


  /**
   * Write an app activity to Google.
   *
   * A list of available app activity types can be found at
   * https://developers.google.com/+/api/moment-types/
   *
   * @param string $type The type of app activity.
   * @throws Google_Auth_Exception, Google_IO_Exception,
   *     Google_Service_Exception
   */
  private function writeAppActivity($url, $type) {
    $moment = new Google_Service_Plus_Moment();
    $moment->setType($type);
    $item_scope = new Google_Service_Plus_ItemScope();
    $item_scope->setUrl($url);
    $moment->setTarget($item_scope);
    $result = $this->app['hp.plus']->moments->insert('me', 'vault', $moment);
  }


  /**
   * Updates the circle data in the Haiku+ DataStore.
   *
   * @param User $user The current authenticated user.
   * @param stdClass $credential The associated credentials for the Google user.
   * @throws Google_Auth_Exception, Google_IO_Exception.
   */
  private function updateCircleCache($user, $credential) {
    $app = $this->app;
    try {
      $edges = $this->fetchGoogleCircleData($user, $credential);
      $app['hp.datastore']->deleteEdgesForUser($user);
      $app['hp.datastore']->setEdges($edges);
    } catch (Google_Auth_Exception $e) {
      $app['hp.datastore']->deleteCredentialWithUserId($google_id);
      throw $e;
    }
  }

  /**
   * Performs the Google+ people.list API call to refresh a user's cached
   * Google data. This data should never be stored permanently and should be
   * refreshed regularly (i.e. if it is older than 24 hours).
   *
   * @param User $user the current authenticated user.
   * @param stdClass $credential the associated credentials for the Google user.
   * @return mixed[] Array of DirectedUserToUserEdge.
   * @throws Google_Auth_Exception, Google_IO_Exception.
   */
  private function fetchGoogleCircleData($user, $credential) {
    $app = $this->app;

    // Supply credentials to Google_Client so that it can make authenticated
    // requests to Google's APIs.
    $app['hp.client']->setAccessToken(json_encode($credential));
    // Make the API request to get a list of people in the user's circles.
    $result = $app['hp.plus']->people->listPeople('me', 'connected', array());

    $people = $result['items'];
    if (!$people) {
      return array();
    }

    // The list of people in $result['items'] might only contain the beginning
    // of the full list of visible users. In order to retrieve all visible
    // users, we must page through the results using the page token available
    // in each response.
    $nextPageToken = $result['nextPageToken'];
    while ($nextPageToken) {
      $result = $app['hp.plus']->people->listPeople('me', 'visible',
          array('pageToken' => $nextPageToken));
      $people = array_merge($people, $result['items']);
      $nextPageToken = $result['nextPageToken'];
    }

    // Haiku+ stores its own representation of the directed user to user edge.
    $edges = array();
    $datastore = $app['hp.datastore'];
    foreach ($people as $person) {
      $other_google_id = $person['id'];
      $otherUser = $datastore->loadUserWithGoogleId($other_google_id);
      if ($otherUser) {
        // We only store a user edge if the other use was found in the
        // Haiku+ database.
        $edges[] = new DirectedUserToUserEdge($user, $otherUser);
      }
    }
    return $edges;
  }
}

