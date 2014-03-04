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
describe('API response tests (unauthorized)', function() {
  /**
   * The setup method called before each test.
   */
  beforeEach(function() {
    gapi = gapi || {};
    gapi.auth = gapi.auth || {};
    gapi.auth.signOut = function() {};

    server = sinon.fakeServer.create();

    // Disable parsing the endpoint responses.
    haikuPlus.Controller.parseResponses = false;
    haikuPlus.Model.init(undefined);
    haikuPlus.View.updateUiControls();
    haikuPlus.Model.getCurrentUser();

    var headers = {
      'X-OAuth-Code':
          "Bearer realm='https://www.google.com/accounts/AuthSubRequest'"
    };

    server.requests[0].respond(401, headers, JSON.stringify({}));

    document.cookie = '';
  });


  /**
   * The tear down method called to clean up any altered data.
   */
  afterEach(function() {
    server.restore();
    $('#' + haikuPlus.View.REAUTHORIZATION_MODAL).dialog('close');
  });


  /**
   * Basic test that the client is requesting the expected endpoint
   * at initialization.
   */
  it('testApiEndpoint', function() {
    // Test we are hitting the right endpoint
    expect(server.requests[0].url)
          .toBe('/api/users/me');
  });


  /**
   * Test that the client is not passing a valid header.
   */
  it('testValidHeader', function() {
    expect(server.requests[0].requestHeaders[C_AUTH_HEADER]).toBe(
        undefined);
  });


  /**
   * Test that the client disconnects if it is out of sync with the server.
   */
  it('testUnAuthReconnectClient', function() {
    if (haikuPlus.Model.DISCONNECT_CLIENTSIDE) {
      expect(server.requests[1].url).toBe(
        haikuPlus.Model.DISCONNECT_API_ENDPOINT +
        GAPI_AUTH_AUTHORIZE.access_token);
    }
  });


  /**
   * Test that the reconnect form is displayed on 401 X-OAuth-Code.
   */
  it('testUnAuthReconnectServer', function() {
    if (!haikuPlus.Model.DISCONNECT_CLIENTSIDE) {
      var reauthForm = $('#' + haikuPlus.View.REAUTHORIZATION_MODAL);

      var expected = true;
      var actual = reauthForm.dialog('isOpen');

      expect(actual).toBe(expected);
    }
  });


  /**
   * Test that the reconnect form is hidden.
   */
  it('testUnAuthReconnectServer', function() {
    if (!haikuPlus.Model.DISCONNECT_CLIENTSIDE) {
      var reauthForm = $('#' + haikuPlus.View.REAUTHORIZATION_MODAL);

      var expected = true;
      var actual = reauthForm.dialog('isOpen');

      expect(actual).toBe(expected);
    }
  });

  /**
   * R.c2 Respond to a 401 X-OAuth-Code server response with a new
   * request which includes an authorization code.
   *
   * Note that this test is for the response for when the server needs
   * the client to authenticate.
   */
  it('testXNoAuthCode', function() {
    // Test for authorization header getting defined.
    expect(server.requests[0].requestHeaders[C_X_OAUTH_CODE]).not
          .toBeDefined();
  });


  /**
   * R.c2 Respond to a 401 X-OAuth-Code server response with a new
   * request which includes an authorization code.
   *
   * Note that this test is for the response for when the server needs
   * the client to authenticate.
   */
  it('testXNoAuthCodePresent', function() {
    // Simulate reconnecting the client.
    haikuPlus.Model.authorizeClientWithCode(gapi.auth.getToken().code);

    expect(server.requests[1].requestHeaders[C_X_OAUTH_CODE])
        .toBe(GAPI_AUTH_GETTOKEN.code);
  });


  /**
   * R.c3 Client assumes that if it has a session ID from previous
   * interactions, it is authenticated unless it receives a 401
   * response.
   */
  it('testIsAuthWithoutSessionId', function() {
    haikuPlus.Model.init();
    expect(haikuPlus.Model.isAuthorized()).toBe(false);
  });


  /**
   * R.c6 Client Authorization Bearer response matches specification.
   */
  it('testBearerResponseTest', function() {
    haikuPlus.Model.getCurrentUser();

    server.requests[0].respond(200, {'Content-Type': 'application/json'},
        JSON.stringify({}));

    // Test we are hitting the right endpoint
    expect(server.requests[0].url)
          .toBe('/api/users/me');

    // Test for authorization header getting defined.
    expect(server.requests[0].requestHeaders['Authorization']).toBe(
        undefined);
  });


  /**
   * R.c6 Client Authorization Bearer response matches specification.
   */
  it('testBearerResponseAuthHeader', function() {
    haikuPlus.Model.getCurrentUser();

    server.requests[0].respond(200, {'Content-Type': 'application/json'},
        JSON.stringify({}));

    // Test for authorization header getting defined.
    expect(server.requests[0].requestHeaders['Authorization']).toBe(
        undefined);
  });


  /**
   * R.c7 Client Authorization X-OAuth-Client response matches specification.
   * This test is for when the server has an invalid refresh token.
   */
  it('testXOauthResponse', function() {
    haikuPlus.Model.getCurrentUser();

    server.requests[0].respond(200, {'Content-Type': 'application/json'},
        JSON.stringify({}));

    // Test we are hitting the right endpoint
    expect(server.requests[0].url)
          .toBe('/api/users/me');

    // Test for authorization header getting passed.
    expect(server.requests[0].requestHeaders['X-OAuth-Code']).not
          .toBeDefined();
  });
});


/**
 * Tests related to 401 'WWW-Authenticate' responses from the server.
 */
describe('API response tests (Authentication)', function() {
  /**
   * The setup method called before each test.
   */
  beforeEach(function() {
    gapi = gapi || {};
    gapi.auth = gapi.auth || {};
    gapi.auth.signOut = function() {};
    server = sinon.fakeServer.create();

    // Initialize the haikuPlus namespace and trigger authorization.
    haikuPlus.Model.init(undefined);
    haikuPlus.Controller.parseResponses = false;
    haikuPlus.View.updateUiControls();
    haikuPlus.Model.getCurrentUser();
    haikuPlus.Model.hasAuth = true;
    haikuPlus.Model.init(GAPI_AUTH_AUTHORIZE);

    // Create headers object with not authenticated header and respond.
    var headers = {
      'Content-Type': 'application/json',
    };
    headers[haikuPlus.Model.S_401_HEADER_SESSION_NOT_AUTHENTICATED] =
      haikuPlus.Model.S_401_BEARER_GOOGLE;
    server.requests[0].respond(401, headers,
        JSON.stringify({}));
  });


  /**
   * The tear down method called to clean up any altered data.
   */
  afterEach(function() {
    server.restore();
    $('#' + haikuPlus.View.REAUTHORIZATION_MODAL).dialog('close');
  });


  /**
   * Respond to a 401 Bearer server response with a new request
   * which includes an ID token.
   */
  it('testBearerResponseAuthHeader', function() {
    server.requests[1].respond(200, {'Content-Type': 'application/json'},
        JSON.stringify({}));

    actual = server.requests[1].requestHeaders[haikuPlus.Model.C_AUTH_HEADER];
    expected = haikuPlus.Model.C_AUTH_BEARER + GAPI_AUTH_AUTHORIZE.id_token;
    expect(expected).toBe(actual);
  });


  /**
   * Respond to a 401 Bearer server response with a new request
   * which does not include an access token.
   */
  it('testBearerResponseAuthNotAccessToken', function() {
    server.requests[1].respond(200, {'Content-Type': 'application/json'},
        JSON.stringify({}));

    actual = server.requests[1].requestHeaders[haikuPlus.Model.C_AUTH_HEADER];
    expected = haikuPlus.Model.C_AUTH_BEARER + GAPI_AUTH_AUTHORIZE.access_token;
    expect(expected).not.toBe(actual);
  });


  /**
   * R.c1: Respond to a 401 Bearer server response with a new request
   * which does not include an authorization code.
   */
  it('testBearerResponseAuthNotAuthCode', function() {
    server.requests[1].respond(200, {'Content-Type': 'application/json'},
        JSON.stringify({}));

    actual = server.requests[1].requestHeaders[haikuPlus.Model.C_AUTH_HEADER];
    expected = haikuPlus.Model.C_AUTH_BEARER + GAPI_AUTH_AUTHORIZE.code;
    expect(expected).not.toBe(actual);
  });


  /**
   * R.c1: Test that we are calling the right endpoint.
   */
  it('testAuthenticateEndpoint', function() {
    // Test we are hitting the right endpoint
    expect(server.requests[0].url)
          .toBe('/api/users/me');
  });


  /**
   * R.c1: Test that we are resending to the right endpoint.
   */
  it('testAuthenticateEndpointResend', function() {
    // Test we are hitting the right endpoint
    expect(server.requests[1].url)
          .toBe('/api/users/me');
  });
});


/**
 * Perform the same tests as before, this time, mock an authorized client.
 */
describe('API response tests (authorized)', function() {
  /**
   * The setup method called before each test.
   */
  beforeEach(function() {
    haikuPlus.Model.init(GAPI_AUTH_AUTHORIZE);

    document.cookie = haikuPlus.Model.COOKIE_KEY + '="abcdefg"';

    // Simulate reauthorization
    server = sinon.fakeServer.create();

    haikuPlus.Model.authorizeClientWithCode(GAPI_AUTH_AUTHORIZE.code);


    server.requests[0].respond(200, {'Content-Type': 'application/json'},
        JSON.stringify({}));

    document.cookie = '';
  });


  /**
   * The tear down method called to clean up any altered data.
   */
  afterEach(function() {
    server.restore();

    var mydate = new Date();
    mydate.setTime(mydate.getTime() - 1);
    document.cookie = "username=; expires=" + mydate.toGMTString();
  });


  /**
   * R.c1 Respond to a 401 Bearer server response with a new request
   * which includes an ID token or access token.
   */
  it('testAuthBearer', function() {
    // Test we are hitting the right endpoint
    expect(server.requests[0].url)
          .toBe('/api/users/me');
  });


  /**
   * R.c2 Respond to a 401 X-OAuth-Code server response with a new
   * request which includes an authorization code.
   */
  it('testXAuthCode', function() {
    // Test we are hitting the right endpoint
    expect(server.requests[0].url).toBe('/api/users/me');

    // Test for authorization header getting defined.
    expect(server.requests[0].requestHeaders[C_X_OAUTH_CODE]).toBe(
        GAPI_AUTH_AUTHORIZE.code);
  });


  /**
   * R.c3 Client assumes that if it has a session ID from previous
   * interactions, it is authenticated unless it receives a 401
   * response. This should
   */
  it('testIsAuthWithSessionId', function() {
    expect(haikuPlus.Model.isAuthorized()).toBe(true);
  });


  /**
   * R.c4 Haiku clients maintain a session identifier in a cookie.
   */
  it('testHasSessionIdentifierNoAuth', function() {
    expect(haikuPlus.Model.isAuthorized()).toBe(true);
  });
});


/*
 * u3 UTestUserLoginUnaut
 */
describe('Test user login (unauthorized)', function() {
  /**
   * The setup method called before each test.
   */
  beforeEach(function() {
    haikuPlus.Controller.signOutUser();
  });


  /**
   * The tear down method called to clean up any altered data.
   */
  afterEach(function() { });


  /**
   * R.c9 Client displays a Google+ sign in button if the user is not
   * currently authenticated.
   */
  it('testSignInTriggersHideButton', function() {
    expect($('#' + haikuPlus.View.SIGNIN_CONTAINER).css('display')).not.
        toBe('none');
  });


  /**
     * R.c12 Client !displays create haiku button if the user is currently
     * authenticated.
     */
  it('testSignInTriggersCreateHaiku', function() {
    expect($('#' + haikuPlus.View.CREATE_BUTTON).attr('disabled')).
        toBe('disabled');
  });
});


/**
 * u5 UTestGetUser
 *   isAuth = false
 * Retrieve a user with authorized session
 * a. User object for the current user is returned
 * b. User is the correct user
 * c. User is valid
 * u6
 *
 * UTestGetUser
 *   isAuth = true
 * Retrieve a user with unauthorized session
 * a. Session not authenticated
 */
describe('Test user login (authorized)', function() {
  /**
   * The setup method called before each test.
   */
  beforeEach(function() {
    console.log( $('#create-button').attr('class') );
    gapi = gapi || {};
    gapi.auth = gapi.auth || {};
    gapi.auth.signOut = function() {};

    server = sinon.fakeServer.create();
    mockDate = new Date();

    haikuPlus.View.updateUiControls();
    haikuPlus.Controller.onSignInCallback(GAPI_AUTH_AUTHORIZE);

    server.requests[0].respond(200, {'Content-Type': 'application/json'},
        JSON.stringify(mockUser));
  });


  /**
   * The tear down method called to clean up any altered data.
   */
  afterEach(function() {
    server.restore();
    haikuPlus.View.resetUI();
    haikuPlus.Model.currUser = undefined;
  });


  /**
   * R.c9 Client displays a Google+ sign in button if the user is not
   * currently authenticated.
   */
  it('testSignInTriggersHideButton', function() {
    expect($('#signin-container').css('display')).toBe('none');
  });


  /**
   * R.c10 Client requests user data from /api/users/me if the user is
   * currently authenticated and displays the results.
   */
  it('testSignInTriggersDisplayMe', function() {
    var expected = '<span id="profileDetails">Welcome back<br>test user name!</span><span id="123x08hsdf">  <a href="http://">    <img src="http://">  </a></span><span id="auth-buttons-area">  <a href="#" onclick="haikuPlus.Controller.signOutUser()">    Sign out  </a><br>  <a href="#" onclick="haikuPlus.Controller.disconnectUser()">    Disconnect  </a></span>';
    var actual = $('#' + haikuPlus.View.USER_PROFILE_CONTAINER).html();

    /*
    var profile =
        '<span id="' + mockUser.google_plus_id + '">' +
        '  <a href="' + mockUser.google_profile_url + '">' +
        '    <img src="' + mockUser.google_photo_url + '">' +
        '  </a>' +
        '</span>' +
        '<span id="profileDetails">' + mockUser.google_display_name +
        '</span>';
        console.log($('#' + haikuPlus.View.USER_PROFILE_CONTAINER).html());
    */

    expect(actual).toBe(expected);
  });


  /**
   * R.c12 Client displays create haiku button if the user is currently
   * authenticated.
   */
  it('testSignInTriggersCreateHaiku', function() {
    expect($('#create-button').attr('class')).not.toBeDefined;
  });


  /**
   * R.cx1: Clients will load the data required to display a view once
   * when the view is initially loaded.  Reloading the data will require
   * the view to be explicitly reloaded by the user.
   */
  it('testSignInTriggersHaikuList', function() {
    mockHtml = '';
    expect($('#create-button').attr('disabled')).not.toBeDefined;
  });
});


/**
 * Test signout and disconnect.
 */
describe('Signout and disconnect', function() {
  /**
   * The setup method called before each test.
   */
  beforeEach(function() {
    // Authorize the client.
    gapi.auth.authorize({}, haikuPlus.Model.init);

    // Simulate reauthorization
    server = sinon.fakeServer.create();

    haikuPlus.Model.getCurrentUser();

    server.requests[0].respond(200, {'Content-Type': 'application/json'},
        JSON.stringify(mockUser));

    document.cookie = '';
  });


  /**
   * The tear down method called to clean up any altered data.
   */
  afterEach(function() {
    server.restore();
  });


  /**
   * R.c15: Clicking the sign out button signs the user out, hides the UI,
   * and removes the session cookie.
   */
  it('testSignOut', function(){
    haikuPlus.Controller.signOutUser();

    expect($('#' + haikuPlus.View.CREATE_BUTTON).attr('disabled')).
        toBe('disabled');
    expect($('#' + haikuPlus.View.SIGNIN_CONTAINER).css('display')).not.
        toBe('none');
    expect($('#' + haikuPlus.View.USER_PROFILE_CONTAINER).html()).toBe('');
  });


  /**
   * R.c15: Clicking the sign out button signs the user out, hides the UI,
   * and removes the session cookie.
   */
  it('testSignOut', function(){
    haikuPlus.Controller.signOutUser();

    var cookieStrings = document.cookie.split(';');

    for (var index=0; index < cookieStrings.length; index++){
      keyVal = cookieStrings[index].split['='];
      if (keyVal != undefined && keyVal[0] == haikuPlus.Model.COOKIE_KEY){
        var sessionId = cookieStrings[1];
        console.log(sessionId);
        expect(sessionId == undefined || (sessionId.length <= 0)).toBe(true);
      }
    }
  });


  /**
   * R.c16: Clicking disconnect performs a call to the server /api/disconnect.
   */
  it('testDisconnectHidesUI', function(){
    haikuPlus.Controller.disconnectUser();

    server.requests[1].respond(200, {'Content-Type': 'application/json'},
        JSON.stringify({}));

    expect($('#' + haikuPlus.View.CREATE_BUTTON).attr('disabled')).
        toBe('disabled');
    expect($('#' + haikuPlus.View.SIGNIN_CONTAINER).css('display')).not.
        toBe('none');
    expect($('#' + haikuPlus.View.USER_PROFILE_CONTAINER).html()).toBe("");
  });


  /**
   * Clicking disconnect performs a call to the server /api/disconnect.
   */
  it('testDisconnectCallsEndpoint', function(){
    haikuPlus.Controller.disconnectUser();

    server.requests[1].respond(200, {'Content-Type': 'application/json'},
        JSON.stringify({}));

    var actual = server.requests[1].url;
    var expected = haikuPlus.Model.DISCONNECT_ENDPOINT;

    expect(actual).toBe(expected);
  });


  /**
   * Test disconnect is making a POST call to the endpoint.
   */
  it('testDisconnectPostsEndpoint', function(){
    haikuPlus.Controller.disconnectUser();

    server.requests[1].respond(200, {'Content-Type': 'application/json'},
        JSON.stringify({}));

    var actual = server.requests[1].method;
    var expected = 'POST';

    expect(actual).toBe(expected);
  });
});


  /**
   * Test that API calls to each endpoint will trigger the callback.
   */
