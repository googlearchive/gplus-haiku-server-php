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
var haikuPlus = haikuPlus || function() { };


/**
 * Manages the Haiku+ application.
 * @constructor
 */
haikuPlus.Controller = function () {
  /**
   * Indicates whether the JSON from web responses needs to be parsed.
   * Should be set to false unless the API is not returning text/JSON
   * in API GET responses.
   */
  this.parseResponses = false;

  /**
   * Contains the browser's location object, used for getting parameters
   * passed to the page, the path to the current page, and redirects.
   */
  this.locationObject = undefined;
};


/**
 * Handles the callback response from the Google+ Sign-In button. Note: The
 * Sign-In callback does not work when running on localhost.
 *
 * @param {Object} resp The response object from the Google+ Sign-In button.
 */
haikuPlus.Controller.prototype.onSignInCallback = function(resp) {
  if (resp.error === undefined) {
    if (resp.status.method == "PROMPT"){
      haikuPlus.Helper.log("Sending authorization code");

      // Only send the authorization code if the user has been presented with
      // the authorization prompt because the code can be exchanged for a refresh
      // token if and only if the user has been prompted.
      //
      // For more information, see:
      // https://developers.google.com/+/web/signin/session-state.
      haikuPlus.Model.init(resp, true);
    }else{
      // Update the model with data from the response.
      haikuPlus.Model.init(resp);
    }
    haikuPlus.Model.hasAuth = true;
    this.signInUser();
  } else {
    // Error, any resolutions for the callback can go here and update the client.
    haikuPlus.Model.hasAuth = false;
    haikuPlus.View.hideAuthUI();
  }
};


/**
 * Gets the currently configured Google APIs client id.
 *
 * @return {string} The current client ID.
 */
haikuPlus.Controller.prototype.getClientId = function(){
  return G_CLIENT_ID;
};


/**
 * Lists the haikus from the server.
 *
 * @param {string} filter Set to 'filtered' to retrieve the list of the user's
 *   friends.
 */
haikuPlus.Controller.prototype.listHaikus = function(filtered){
  var callback = function(haikus){
    for (var index=0; index < haikus.length; index++){
      haikuPlus.View.addHaiku(haikus[index]);
    }
  }
  haikuPlus.Model.listHaikus(filtered, callback);
}


/**
 * List a single haiku from the model.
 *
 * @parameter {int} haikuId The id of the haiku to list.
 */
haikuPlus.Controller.prototype.getHaiku = function (haikuId) {
  var callback = function(haiku){
    haikuPlus.View.addHaiku(haiku, true);
  }

  haikuPlus.Model.getHaiku(haikuId, callback);
};


/**
 * Opens the "Create Haiku" dialog.
 */
haikuPlus.Controller.prototype.createHaiku = function() {
  $('#' + haikuPlus.View.CREATE_HAIKU_FORM).dialog('open');
};


/**
 * Returns the correct location object for the current browser.
 *
 * @param {Object} The updated locationObject.
 */
haikuPlus.Controller.prototype.updateLocationObject = function(newLocation) {
  if (this.locationObject != undefined){
    this.locationObject = newLocation;
    return;
  };

  // Use the location as default when the encapsulated one is unset.
  location = newLocation;
}


/**
 * Gets the location object for the browser.
 *
 * @return {Object} The location object for the browser.
 */
haikuPlus.Controller.prototype.getLocationObject = function(){
  if (this.locationObject != undefined){
    return this.locationObject;
  }

  // Use the location as default when the encapsulated one is unset.
  return location;
}


/**
 * Saves a Haiku to the server and renders the haiku in the client.
 *
 * @param {object} haiku The JSON object representing the haiku to save.
 */
haikuPlus.Controller.prototype.saveHaiku = function(haiku) {
  var callback = function() {
    if (haikuPlus.Helper.isSingleHaiku(undefined)){
      haikuPlus.Helper.redirect(undefined, haikuPlus.Model.HAIKU_PATH);
      return;
    }
    haikuPlus.View.addHaiku(haiku);
    haikuPlus.Helper.refresh(undefined);
  };

  haikuPlus.Model.storeHaiku(haiku, callback);
};


/**
 * Updates the client when the user successfully signs in.
 */
haikuPlus.Controller.prototype.signInUser = function() {
  var callback = function(user, status) {
    if (haikuPlus.Controller.parseResponses) {
      user = JSON.parse(user);
    }
    haikuPlus.View.showAuthUI();
    haikuPlus.View.updateUserHtml(user);
    haikuPlus.Model.currUser = user;
  };
  haikuPlus.Model.getCurrentUser(callback);
};


/**
 * Signs the user out.
 */
haikuPlus.Controller.prototype.signOutUser = function() {
  var callback = function () {
    gapi.auth.signOut();
    document.cookie = haikuPlus.Model.COOKIE_KEY +
      "; expires=Thu, 01 Jan 1970 00:00:01;";
    haikuPlus.Helper.redirect(undefined, '/haikus')
  };
  haikuPlus.Model.signOut(callback);
  haikuPlus.View.hideAuthUI();
};

/**
 * POSTs to the server to disconnect the user from this application.
 */
haikuPlus.Controller.prototype.disconnectUser = function() {
  var callback = function (resp) {
    haikuPlus.Helper.refresh(undefined);
  };
  haikuPlus.Model.disconnect(callback);
  haikuPlus.View.hideAuthUI();
};


/**
 * Disconnects the client and shows the reauthorization button.
 */
haikuPlus.Controller.prototype.reauthorize = function () {
  // One approach: disconnect the client.
  //haikuPlus.Helper.forceDisconnect();

  // Another approach, present the sign-in button configured to reauthorize
  // the user.
  haikuPlus.View.hideSignInButton();
  $('#' + haikuPlus.View.REAUTHORIZATION_MODAL).dialog('open');
};


/**
 * Performs a vote operation given the id of the haiku and redirects the user
 * to the page for the haiku they voted for.
 *
 * @param {Object} The browser's location object containing the browser's
 * location variable to be used in a redirect.
 * @param {int} The id of the haiku to vote on.
 */
haikuPlus.Controller.prototype.vote = function(haikuId) {
  var redirectURI = '/haikus/' + haikuId;

  var callback = function () {
    haikuPlus.Helper.redirect(undefined, haikuPlus.Model.HAIKU_PATH);
    return;
  };
  if (!haikuPlus.Helper.isSingleHaiku(undefined)){
    callback = function() {
      haikuPlus.Helper.redirect(undefined, redirectURI);
    };
  }

  haikuPlus.Model.voteOnHaiku(haikuId, callback);
};


/**
 * Creates an interactive post to promote the target haiku.
 */
haikuPlus.Controller.prototype.promote = function(haikuId) {
  // TODO (class)
};

haikuPlus.Controller = new haikuPlus.Controller();
