/*
 * Copyright 2014 Google Inc. All Rights Reserved.
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
var haikuPlus = haikuPlus || { };



/**
 * The API client for managing data with queries to the server.
 * @constructor
 */
haikuPlus.Model = function() {
  /**
   * The identifier for the session cookie.
   * @const
   */
  this.COOKIE_KEY = 'HaikuSessionId';

  /**
   * Client header for the authorization code.
   * @const
   */
  this.C_X_OAUTH_CODE = 'X-OAuth-Code';

  /**
   * Client header for authorization.
   * @const
   */
  this.C_AUTH_HEADER = 'Authorization';

  /**
   * Client header for passing authorization bearer tokens.
   * @const
   */
  this.C_AUTH_BEARER = 'Bearer ';

  /**
   * Server header indicating the client needs to re-authenticate.
   * @const
   */
  this.S_401_HEADER_SESSION_NOT_AUTHENTICATED = 'WWW-Authenticate';

  /**
   * Server WWW-Authenticate value indicating a Google realm bearer token
   * is acceptable because the client should send an ID token or access
   * token.
   * @const
   */
  this.S_401_BEARER_GOOGLE = this.C_AUTH_BEARER +
      "realm='https://www.google.com/accounts/AuthSubRequest'";

  /**
   * Server header indicating that the client needs to re-authorize. This can
   * happen if the server has neither a valid refresh token or access token
   * for this user, so the client should send an authorization code for a new
   * refresh token or a new access token.
   * @const
   */
  this.S_401_HEADER_SESSION_NOT_AUTHORIZED = 'X-OAuth-Code';

  /**
   * Server X-OAuth-Code value indicating that a Google realm authorization
   * code is acceptable.
   * @const
   */
  this.S_401_X_OAUTH_CODE_GOOGLE =
      "'realm='https://www.google.com/accounts/AuthSubRequest'";

  /**
   * The scopes to request from the Google+ API.
   * @const
   */
  this.SCOPES = 'https://www.googleapis.com/auth/plus.login';

  /**
   * If set to true, disconnect client-side; otherwise, call the server
   * endpoint.
   * @const
   */
  this.DISCONNECT_CLIENTSIDE = false;

  /**
   * Path to the user resource on the server.
   * @const
   */
  this.USER_ENDPOINT = '/api/users';

  /**
   * Path to the disconnect endpoint on the server.
   * @const
   */
  this.DISCONNECT_ENDPOINT = '/api/disconnect';

  /**
   * Path to the OAuth disconnect API endpoint.
   * @const
   */
  this.DISCONNECT_API_ENDPOINT = 'https://accounts.google.com/o/'+
      'oauth2/revoke?token=';

  /**
   * Path to the haikus resource endpoint on the server.
   * @const
   */
  this.HAIKU_PATH = '/haikus';

  /**
   * Path to the haikus resource endpoint on the server.
   * @const
   */
  this.HAIKU_ENDPOINT = '/api/haikus';

  /**
   * Path to the signout endpoint on the server.
   * @const
   */
  this.SIGNOUT_ENDPOINT = '/api/signout';

  /**
   * Path to the vote endpoint on the server.
   * @const
   */
  this.VOTE_ENDPOINT = '/api/haikus';

  /**
   * Number of times to retry for requesting resources.
   * @const
   */
  this.MAX_RETRIES = 3;

  /**
   * An authorization code that is undefined unless the client is authorized.
   */
  this.authCode = undefined;

  /**
   * An id token that is undefined unless the client is authorized.
   */
  this.idToken = undefined;

  /**
   * An access token that is undefined unless the client is authorized.
   */
  this.accessToken = undefined;

  /**
   * An object representing the current user that is undefined unless the
   * client is authorized.
   */
  this.currUser = undefined;

  /**
   * Indicates the client needs to reauthorize.
   */
  this.reAuth = false;

  /**
   * Indicates the client needs to reauthorize.
   */
  this.hasAuth = false;
};


/**
 * Stores the relevant credentials from an authorization response object.
 *
 * @param {Object} authResult The response object from the OAuth endpoint.
 * @param {bool} sendCodeToServer If set to true, will send an OAuth 2
 *   authorization code to the server to be exchanged for a refresh token.
 */
haikuPlus.Model.prototype.init = function(authResult, sendCodeToServer) {
  if (authResult === undefined) {
    this.idToken = undefined;
    this.accessToken = undefined;
    this.currUser = undefined;
  } else {
    this.idToken = authResult.id_token;
    this.accessToken = authResult.access_token;
    if (sendCodeToServer){
      haikuPlus.Model.authorizeClientWithCode(authResult.code);
    }
  }
};


/**
 * Performs an API call to authorize the server and client.
 *
 * @param {string} code The authorization code to set in the header.
 */
haikuPlus.Model.prototype.authorizeClientWithCode = function(code) {
  // Force the client to hit an authorized endpoint on the server.
  var uri = haikuPlus.Model.USER_ENDPOINT + '/me';

  $.ajax(uri,
    {
      async: true,
      data: '',
      beforeSend: function(request) {
        request.setRequestHeader(haikuPlus.Model.C_X_OAUTH_CODE,
            code + ' redirect_uri=\'postmessage\'');
      },
      error: function(jqhr, status, error) {
        haikuPlus.Helper.log("Error on authorize" + error);
      },
      success: function() {
        haikuPlus.Model.reAuth = false;
      },
      type: "GET"
    });
}


/**
 * Wraps API calls to simplify error handling.
 *
 * @param {string} endpoint The API endpoint to use without leading slash.
 * @param {string} verb The HTTP verb to use.
 * @param {function} callback The callback triggered on success.
 * @param {string} paramString The parameters to pass to the API call.
 * @param {string} data Data to send with the request.
 * @param {number} retryCount The number of times this call has been made.
 */
haikuPlus.Model.prototype.apiCall = function(endpoint, verb, callback, paramString,
    data, retryCount) {

  if (endpoint == undefined) endpoint = '';
  if (paramString == undefined) paramString = '';
  if (verb == undefined) verb = 'GET';
  if (data == undefined) data = '';
  if (callback == undefined) {
    callback = function(resp) {
      haikuPlus.Helper.log(resp);
    };
  }

  retryCount++;

  if (retryCount > haikuPlus.Model.MAX_RETRIES) {
    // User should not be signed in.
    haikuPlus.Helper.log('Retries reached:');
    haikuPlus.Helper.log('Calls to gapi.auth.authorize are not not ' +
        'getting valid tokens from gapi; or, the server has ' +
        'credentials that do not match the client.');
    return;
  }

  var uri = encodeURI(endpoint + paramString);
  $.ajax(uri,
      {
        async: true,
        beforeSend: function(request) {
          // Only send the authorization headers if the client is not connected.
          if (haikuPlus.Model.hasSessionCookie() && !haikuPlus.Model.reAuth) {
            // Session is authorized, no need to send a bearer token.
          } else {
            haikuPlus.Model.reAuth = false;

            request.setRequestHeader(haikuPlus.Model.C_AUTH_HEADER,
                haikuPlus.Model.C_AUTH_BEARER + haikuPlus.Model.idToken);
          }
        },
        data: data,
        error: function(jqhr, status, error){
          haikuPlus.Model.resolveError(jqhr, jqhr.status, error, endpoint, verb,
              callback, paramString, data, retryCount);
        },
        type: verb,
        success: callback
      });
};


/**
 * Resolves errors returned from the server.
 *
 * @param {string} status The status code returned from the server.
 * @param {string} error The error string from the server.
 * @param {string} endpoint The endpoint from the API call.
 * @param {string} verb The HTTP verb used to make the API call.
 * @param {function} callback The callback to be called after success.
 * @param {string} paramString The parameter string to pass to the API call.
 * @param {string} data A content body to pass as data for the API call.
 * @param {int} retryCount The number of times the API call has been attempted.
 */
haikuPlus.Model.prototype.resolveError = function(jqhr, status, error, endpoint,
    verb, callback, paramString, data, retryCount) {
  var authenticatedErrString =
      jqhr.getResponseHeader(
          haikuPlus.Model.S_401_HEADER_SESSION_NOT_AUTHENTICATED);
  var authorizedErrString =
      jqhr.getResponseHeader(
          haikuPlus.Model.S_401_HEADER_SESSION_NOT_AUTHORIZED);

  if (jqhr.status != 404 && jqhr.status != 500) {
    if (authorizedErrString !== undefined && authorizedErrString != null) {
      // Need to resend an authorization code by forcing the appearance
      // of the consent dialog.
      haikuPlus.Controller.reauthorize();
    } else if (authenticatedErrString !== undefined && authenticatedErrString != null) {
      // A 401 Authorization Bearer request has been made.

      if (!haikuPlus.Model.hasAuth) {
        // The user hasn't authorized the client yet.
        return;
      }

      // Force the client to reauthoize with the server.
      haikuPlus.Model.reAuth = true;
      // Refresh and send all tokens again.
      haikuPlus.Model.apiCall(endpoint, verb, callback, paramString, data,
          retryCount);
    } else {
      haikuPlus.Helper.log('Error and missing not authorized header: ' +
          status + " : " + error);
      if (endpoint != haikuPlus.Model.SIGNOUT_ENDPOINT){
        haikuPlus.Controller.signOutUser();
      }else{
        haikuPlus.Helper.refresh(undefined);
      }
    }
  } else {
    haikuPlus.Helper.log('Error: ' + status + ' on API call - ' + error);
  }
}


/**
 * Disconnects the currently signed in user from this app.
 *
 * @param {function} callback Function called after async call finishes.
 */
haikuPlus.Model.prototype.disconnect = function(callback) {
  // HARDCODE to disconnect directly for now.
  if (haikuPlus.Model.DISCONNECT_CLIENTSIDE) {
    var disconnectURI = haikuPlus.Model.DISCONNECT_API_ENDPOINT +
        gapi.auth.getToken().access_token;

    // Revoke the access token.
    $.ajax(disconnectURI,
        {
          async: true,
          type: 'GET',
          success: function(result) {
            // User has diconnected successfully.
            haikuPlus.Helper.refresh(undefined);
          },
          error: function(e) {
            haikuPlus.Helper.log(e);
          }
      });
  } else {
    this.apiCall(haikuPlus.Model.DISCONNECT_ENDPOINT, 'POST', callback, '', '',
        1);
  }
};


/**
 * Get the currently signed in user.
 *
 * @param {function} callback The callback to be called upon completion.
 */
haikuPlus.Model.prototype.updateCurrentUser = function(callback) {
  this.apiCall(haikuPlus.Model.USER_ENDPOINT, 'GET', callback, '/me', '', 1);
};


/**
 * Get the current user and call the passed in callback with the current user
 * as JSON.
 *
 * @param {function} callback The callback to be handed the
 *   current user JSON.
 */
haikuPlus.Model.prototype.getCurrentUser = function(callback) {
  if (callback === undefined){
    callback = function (){};
  }

  if (this.currUser != undefined) {
    callback(JSON.stringify(this.currUser));
  } else {
    this.updateCurrentUser(callback);
  }
};


/**
 * Retrieves Haikus using the client.
 *
 * @param {boolean} visible Indicates whether the results should be filtered
 *   only to people visible to this app or to all Haikus in the datastore.
 *   If set to true, only returns Haikus from the people visible to this app.
 * @param {function} callback Called after the API retrieves the haikus.
 */
haikuPlus.Model.prototype.listHaikus = function(visible, callback) {
  var paramString = visible ? '?filter=circles' : '';
  this.apiCall(haikuPlus.Model.HAIKU_ENDPOINT, 'GET', callback, paramString, '',
      1);
};


/**
 * Retrieves a haiku using the client.
 *
 * @param {int} haikuId The haiku to list.
 * @param {function} callback Called after the API retrieves the haikus.
 */
haikuPlus.Model.prototype.getHaiku = function(haikuId, callback) {
  var paramString = '/' + haikuId;
  this.apiCall(haikuPlus.Model.HAIKU_ENDPOINT, 'GET', callback, paramString, '',
      1);
};


/**
 * Signs the current user out.
 *
 * @param {function} callback Called after the API call is made to the server.
 */
haikuPlus.Model.prototype.signOut = function(callback) {
  this.apiCall(haikuPlus.Model.SIGNOUT_ENDPOINT, 'POST', callback, '', 1);
};


/**
 * Stores a haiku to the server.
 *
 * @param {Object} haiku The haiku to store on the server.
 */
haikuPlus.Model.prototype.storeHaiku = function(haiku, callback){
  haikuString = JSON.stringify(haiku);
  this.apiCall(haikuPlus.Model.HAIKU_ENDPOINT, 'POST', callback, '',
      haikuString, 1);
};


/**
 * Votes on a Haiku.
 *
 * @param {string} haikuId The identifier for the Haiku to vote on.
 * @param {function} callback Called after voting.
 */
haikuPlus.Model.prototype.voteOnHaiku = function(haikuId, callback) {
    this.apiCall(haikuPlus.Model.VOTE_ENDPOINT, 'POST', callback,
        '/' + haikuId + '/vote', '', 1);
};


/**
 * Gets the authorization state of this Haiku+ client.
 *
 * @return {boolean} Returns true of the client is authorized.
 */
haikuPlus.Model.prototype.isAuthorized = function() {
  if ((this.authCode !== undefined || this.idToken !== undefined ||
      this.accessToken !== undefined) && this.hasSessionCookie()) {
    return true;
  }

  return false;
};


/**
 * Tests whether the session cookie is present.
 *
 * @return {boolean} Returns true of the session cookie exists on the client.
 */
haikuPlus.Model.prototype.hasSessionCookie = function() {
  var currSessionId = this.getCurrentSessionId();
  return currSessionId !== undefined;
};


/**
 * Retrieves the current session identifier.
 *
 * Cookie string looks like:
 *   key1=value1:key2=value2:key3=value3:keyN=valueN
 *
 * @return {String} Return the session identifier on success; otherwise,
 *   returns undefined.
 */
haikuPlus.Model.prototype.getCurrentSessionId = function() {
  var keyValPairs = document.cookie.split(';');
  for (var i=0; i < keyValPairs.length; i++){
    var key = keyValPairs[i].split('=')[0];
    if (key != undefined) key = key.trim();

    var value = keyValPairs[i].split('=')[1];
    if (value != undefined) value = value.trim();

    if (key == haikuPlus.Model.COOKIE_KEY) {
      var vvalue = value;
      return value;
    }
  }
  return undefined;
};


/**
 * Attach to the namespace.
 */
haikuPlus.Model = new haikuPlus.Model();
