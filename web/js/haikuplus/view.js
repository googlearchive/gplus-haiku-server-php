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
 * Manages generating HTML and updating UI for Haiku+.
 * @constructor
 */
haikuPlus.View = function() {

  /**
   * ID for the element to hold the user's profile.
   * @const
   */
  this.USER_PROFILE_CONTAINER = 'user-profile-container';

  /**
   * ID for the element to hold the user's haikus.
   * @const
   */
  this.HAIKUS_CONTAINER = 'haikus-container';

  /**
   * ID for the element to hold the Sign-In button.
   * @const
   */
  this.SIGNIN_CONTAINER = 'signin-container';

  /**
   * ID for the element to hold the "sign in" callout message.
   * @const
   */
  this.SIGNIN_CALLOUT = 'signin-calloutarea';

  /**
   * ID for the element to hold the Sign-In button.
   * @const
   */
  this.FILTER_CONTAINER = 'filter-controls';

  /**
   * The ID for the form used on the create haiku modal.
   * @const
   */
  this.CREATE_HAIKU_FORM = 'create-haiku-form';

  /**
   * The ID for the reauthorization modal
   * @const
   */
  this.REAUTHORIZATION_MODAL = 'reauthorization-modal';

  /**
   * ID for the elemnent in the create haiku form for the title input field.
   * @const
   */
  this.TITLE_INPUT = 'create-haiku-title';

  /**
   * ID for the elemnent in the create haiku form for the haiku's first phrase
   * input field.
   * @const
   */
  this.HAIKU_LINE_1 = 'phrase-1';

  /**
   * ID for the elemnent in the create haiku form for the haiku's second phrase
   * input field.
   * @const
   */
  this.HAIKU_LINE_2 = 'phrase-2';

  /**
   * ID for the elemnent in the create haiku form for the haiku's third phrase
   * input field.
   * @const
   */
  this.HAIKU_LINE_3 = 'phrase-3';

  /**
   * ID for the element to hold the create Haiku button.
   * @const
   */
  this.CREATE_BUTTON = 'create-button';

  /**
   * ID for the element to hold the authorized area.
   * @const
   */
  this.AUTH_AREA_CONTAINER = 'auth-area-container';

  /**
   * UI string for the unauthorized filter bar.
   * @const
   */
  this.UNAUTH_FILTER_BAR = '<a class="active-button disable-link" href="/haikus">EVERYONE</a>' +
      '<span class="inactive-button disable-link">CIRCLES</span>';

  /**
   * UI string for the authorized filter bar.
   * @const
   */
  this.AUTH_FILTER_BAR = '<a class="active-button" href="/haikus">EVERYONE</a>' +
      '<a class="inactive-button" href="/haikus?filter=circles">CIRCLES</a>';

  this.AUTH_FILTER_BAR_CIRCLES = '<a class="inactive-button" href="/haikus">' +
      'EVERYONE</a>' + '<a class="active-button" ' +
      'href="/haikus?filter=circles">CIRCLES</a>'

  this.CALLOUT_CONTENT = 'Sign in to view friends\'<br/>' +
      'Haikus & create your own';
};


/**
 * Resets the application UI when the user signs out or disconnects.
 */
haikuPlus.View.prototype.resetUI = function() {
  $('#' + this.REAUTHORIZATION_MODAL).dialog('close');
  $('#' + this.USER_PROFILE_CONTAINER).hide();
  $('#' + this.USER_PROFILE_CONTAINER).html('');
  $('#' + this.SIGNIN_CONTAINER).hide();
  $('#' + this.FILTER_CONTAINER).html(this.UNAUTH_FILTER_BAR);
  $('#' + 'create-button').addClass('disable-link');
};


/**
 * Enables the UI for when a user is authorized.
 */
haikuPlus.View.prototype.showAuthUI = function() {
  var filterContent = this.AUTH_FILTER_BAR;
  if (haikuPlus.Helper.isFiltered()) {
    filterContent = this.AUTH_FILTER_BAR_CIRCLES;
  }

  $('#' + this.SIGNIN_CONTAINER).hide();
  $('#' + this.FILTER_CONTAINER).html(filterContent);
  $('#' + this.CREATE_BUTTON).removeClass('disable-link');
  $('#' + this.AUTH_AREA_CONTAINER).show();
  $('#' + this.REAUTHORIZATION_MODAL).dialog('close');
  $('#' + this.SIGNIN_CALLOUT).html('');
};


/**
 * Disables the UI for when a user is authorized.
 */
haikuPlus.View.prototype.hideAuthUI = function() {
  $('#' + this.SIGNIN_CONTAINER).show();
  $('#' + this.CREATE_BUTTON).addClass('disable-link');
  $('#' + this.AUTH_AREA_CONTAINER).hide();
  $('#' + this.USER_PROFILE_CONTAINER).html('');
  $('#' + this.SIGNIN_CALLOUT).html(this.CALLOUT_CONTENT);
};


/**
 * Hides the sign-in button.
 */
haikuPlus.View.prototype.hideSignInButton = function(){
  $('#' + this.SIGNIN_CONTAINER).hide();
};


/**
 * Updates the HTML for the user area of the Haiku+ app.
 *
 * @param {Object} user The User object to use for creating the HTML.
 */
haikuPlus.View.prototype.updateUserHtml = function(user) {
  if (user === undefined) {
    haikuPlus.Model.getCurrentUser(this.updateUserHtml);
  }
  $('#' + this.USER_PROFILE_CONTAINER).html(haikuPlus.View.getUserHtml(user));
};


/**
 * Returns HTML given a User object.
 *
 * @param {Object} user The JSON representing a Haiku.
 * @return {string} HTML for the Haiku.
 */
haikuPlus.View.prototype.getUserHtml = function(user) {
  var profile =
      '<span id="profileDetails">' +
         'Welcome back<br/>' +
         user.google_display_name +
      '!</span>'+
      '<span id="' + user.google_plus_id + '">' +
      '  <a href="' + user.google_profile_url + '">' +
      '    <img src="' + user.google_photo_url + '">' +
      '  </a>' +
      '</span>' +
      '<span id="auth-buttons-area">' +
      '  <a href="#" onClick="haikuPlus.Controller.signOutUser()">' +
      '    Sign out' +
      '  </a><br>' +
      '  <a href="#" onClick="haikuPlus.Controller.disconnectUser()">' +
      '    Disconnect' +
      '  </a>' +
      '</span>';
  return profile;
};


/**
 * Updates the HTML for the haiku area of the Haiku+ app.
 *
 * @param {Object} haiku The array of objects representing the list of haikus.
 */
haikuPlus.View.prototype.updateHaikuHtml = function(haikus) {
  for (var index=0; index < haikus.length; index++) {
    addHaiku(haikus[index]);
  }
};


/**
 * Creates the Promote button for a given haiku.
 *
 * @param {object} haiku The haiku to create the interactive post for.
 */
haikuPlus.View.prototype.addPromote = function (haiku) {
    // Calculate the CTA URLs
    var baseUrl = haikuPlus.Helper.getOrigin();
    var contentUrl = haiku.content_url || (haiku.baseUrl +
        haikuPlus.Model.HAIKU_PATH  + '/' + haiku.id);
    var ctaUrl = haiku.call_to_action_url || (baseUrl +
        haikuPlus.Model.HAIKU_PATH  + '/' + haiku.id);
    var contentDeepLinkId = haiku.content_deep_link_id ||
        ('/haikus/' + haiku.id);
    var ctaDeepLinkId = haiku.call_to_action_deep_link_id ||
        ('/haikus/' + haiku.id);

    var options = {
      contenturl: contentUrl,
      contentdeeplinkid: contentDeepLinkId,
      clientid: haikuPlus.Controller.getClientId(),
      cookiepolicy: 'single_host_origin',
      prefilltext: 'Vote for my Haiku+!',
      calltoactionlabel: 'VOTE',
      calltoactionurl: ctaUrl,
      calltoactiondeeplinkid: ctaDeepLinkId
    };

    // Call the render method when appropriate within your app to display
    // the button.
    gapi.interactivepost.render(haikuPlus.View.getPromoteId(haiku), options);
};


/**
 * Adds a haiku to the top of the list of haikus.
 *
 * @param {object} haiku The HTML representing the haiku to add.
 * @param {bool} showHaikuContent If set to yes, will render the haiku
 *   with the text; otherwise, just displays the title.
 */
haikuPlus.View.prototype.addHaiku = function(haiku, showHaikuContent){
  var existingHTML = $('#' + this.HAIKUS_CONTAINER).html();
  $('#' + haikuPlus.View.HAIKUS_CONTAINER).html(
      haikuPlus.View.getHaikuHtml(haiku,showHaikuContent) +
      existingHTML);
  try{
    haikuPlus.View.addPromote(haiku);
  }catch (gapiMissingException) {
    // This can occur in a race condition on gapi.
    haikuPlus.Helper.log(gapiMissingException);
  }
}


/**
 * Returns HTML given a JSON object for a Haiku.
 *
 * @param {Object} haiku The JSON representing a Haiku.
 * @param {bool} showHaikuContent.
 * @return {string} HTML for the Haiku.
 */
haikuPlus.View.prototype.getHaikuHtml = function(haiku, showHaikuContent) {
  if (haiku === undefined){
    haiku = {
      'title': '',
      'votes': 0,
      'line_one': '',
      'line_two': '',
      'line_three': '',
      'id': 0
    };
  }

  if (haiku.title.length == 0) {
    haiku.title = "[Untitled]";
  }

  if (haiku.author === null || haiku.author === undefined ||
      haiku.author.google_profile_url === undefined){
    haiku.author = {
      'google_profile_url': '',
      'google_photo_url': '',
      'google_display_name': ''
    };
  }

  var haikuHtml = '';
  var closingHr = '<hr>';
  var voteButton = '';

  if (showHaikuContent) {
    haikuHtml = '<span class="haiku-body">' + haiku.line_one + '</span>\n' +
        '<span class="haiku-body">' + haiku.line_two + '</span>\n' +
        '<span class="haiku-body">' + haiku.line_three + '</span>\n' +
        '<hr>';

    voteButton = '<section class="vote"><a class="vote-button" href="#" ' +
        'onClick="haikuPlus.Controller.vote(\'' + haiku.id +
        '\')" >VOTE</a></section>';

    closingHr = '';
  }

  return '<section class="haiku">\n' +
      '  <h2 class="title"><a href="/haikus/' + haiku.id +'">' +
      haiku.title + '</a></h2>\n' +
      haikuHtml +
      '  <img class="avatar" src="' + haiku.author.google_photo_url + '" />' +
      '  <span class="author">' + haiku.author.google_display_name +
      '  </span>\n' +
      '  <span class="date">on ' +
      haikuPlus.Helper.getPrettyTime(new Date(haiku.creation_time)) +
      '  </span>\n' +
      '  <span class="votes">' + haiku.votes + ' Votes</span>' +
      closingHr +
      '</section>' +
      voteButton;


};


/**
 * Gets the promote button's id string given a haiku ID.
 *
 * @param {object} haiku The haiku to get the promote button id for.
 * @return {string} The id for the promote button.
 */
haikuPlus.View.prototype.getPromoteId = function (haiku) {
  return 'promote-' + haiku.id;
}


/**
 * Initializes any jQueryUI controls.
 */
haikuPlus.View.prototype.updateUiControls = function() {
  // Modal for creating haikus.
  $('#' + haikuPlus.View.CREATE_HAIKU_FORM).dialog({
    autoOpen: false,
    buttons: {
      'Create': haikuPlus.View.saveHaikuCallback,
      Cancel: function() {
        $(this).dialog('close');
      }
    },
    close: function() {
      $('#' + haikuPlus.View.TITLE_INPUT).val('');
      $('#' + haikuPlus.View.HAIKU_LINE_1).val('')
      $('#' + haikuPlus.View.HAIKU_LINE_2).val('')
      $('#' + haikuPlus.View.HAIKU_LINE_3).val('')
    },
    height: 300,
    modal: true,
    open: function(event, ui) { $(".ui-dialog-titlebar-close", ui.dialog || ui).hide(); },
    width: 450
  });

  // Modal for reauthorization.
  $('#' + haikuPlus.View.REAUTHORIZATION_MODAL).dialog({
    autoOpen: false,
    height: 225,
    width: 450,
    modal: true
  });
};


/**
 * Closes the reauthorization modal.
 */
haikuPlus.View.prototype.closeReauthModal = function() {
  // Modal for reauthorization.
  var reauthModal = $('#' + haikuPlus.View.REAUTHORIZATION_MODAL);

  if (reauthModal.dialog('isOpen')){
    reauthModal.dialog('close');
  }
};


/**
 * Called when the user clicks the Save Haiku button.
 *
 * @param {bool} closeDialog Set to true if you want the callback to close the
 *   dialog.
 * @param {bool} insertCreationTime Set to true if you want to set the
 *   creation time on the client for the haiku; defaults to true.
 */
haikuPlus.View.prototype.saveHaikuCallback = function(closeDialog, insertCreationTime) {
  if (insertCreationTime === undefined) insertCreationTime = true;

  // Called after the user is loaded from the model.
  var lastHaiku = {
    'title' : $('#' + haikuPlus.View.TITLE_INPUT).val(),
    'line_one' : $('#' + haikuPlus.View.HAIKU_LINE_1).val(),
    'line_two' : $('#' + haikuPlus.View.HAIKU_LINE_2).val(),
    'line_three' : $('#' + haikuPlus.View.HAIKU_LINE_3).val(),
    'votes' : 0,
    'creation_time' : (insertCreationTime) ? new Date().toISOString() : ''
  };

  // Save the form data before clearing it when the dialog closes.
  var callback = function(user){
    lastHaiku.author = JSON.parse(user);
    haikuPlus.Helper.log(JSON.stringify(lastHaiku));
    haikuPlus.Controller.saveHaiku(lastHaiku);
  }
  haikuPlus.Model.getCurrentUser(callback);
  if (closeDialog) $(this).dialog('close');
};


/**
 * Attach the view to the haikuPlus namespace.
 */
haikuPlus.View = new haikuPlus.View();
