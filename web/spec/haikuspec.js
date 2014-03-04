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
describe('List Haiku Tests', function() {
  /**
   * The setup method called before each test.
   */
  beforeEach(function() {
    // Authorize the client
    gapi.auth.authorize({}, haikuPlus.Model.init);

    // Simulate reauthorization
    server = sinon.fakeServer.create();

    haikuPlus.Model.getCurrentUser();

    server.requests[0].respond(200, {'Content-Type': 'application/json'},
        JSON.stringify(mockUser));
  });


  /**
   * The tear down method called to clean up any altered data.
   */
  afterEach(function() {
    server.restore();
  });


  /**
   * R.c17: Clicking the everyone / friends filter toggles the haiku list.
   */
  it('friendsClick', function() {
    haikuPlus.View.showAuthUI();
    expect($('#' + haikuPlus.View.FILTER_CONTAINER).html()).toBe(
      haikuPlus.View.AUTH_FILTER_BAR);
  });

  /**
   * R.u7: Listing Haikus calls the expected endpoint and updates the list of
   * Haikus.
   */
  it('testListHaikus', function() {
    haikuPlus.Controller.listHaikus(false);
    server.requests[1].respond(200, {'Content-Type': 'application/json'},
        JSON.stringify(mockHaikus));
  });
});


/**
 * Test listing haikus.
 */
describe('List Single Haiku Tests', function() {
  /**
   * The setup method called before each test.
   */
  beforeEach(function() {
    // Authorize the client
    gapi.auth.authorize({}, haikuPlus.Model.init);

    // Simulate reauthorization
    server = sinon.fakeServer.create();

    haikuPlus.Model.getCurrentUser();

    server.requests[0].respond(200, {'Content-Type': 'application/json'},
        JSON.stringify(mockUser));
  });


  /**
   * The tear down method called to clean up any altered data.
   */
  afterEach(function() {
    server.restore();
  });


  /**
   * Path set to /haikus/id will return only the set haiku.
   */
  it('testListHaikuPath', function() {
    haikuPlus.Controller.locationObject = locationObj;
    haikuPlus.Controller.locationObject.pathname = '/haikus/1';

    var expected = '1';
    var actual = haikuPlus.Helper.haikuIdFromPath();
    expect(actual).toBe(expected);
  });
});



/**
 * Test creation of haikus.
 */
describe('Create Haiku Tests', function() {
  /**
   * The setup method called before each test.
   */
  beforeEach(function() {
    haikuPlus.Controller.locationObject = locationObj;

    // Authorize the client
    gapi.auth.authorize({}, haikuPlus.Model.init);

    // Simulate reauthorization
    server = sinon.fakeServer.create();
    haikuPlus.Model.currUser = mockUser;

    // Trigger create haiku.
    haikuPlus.View.updateUiControls();
    haikuPlus.Controller.createHaiku();
  });


  /**
   * The tear down method called to clean up any altered data.
   */
  afterEach(function() {
    server.restore();
    var createForm = $('#' + haikuPlus.View.CREATE_HAIKU_FORM);
    createForm.dialog('close');
    haikuPlus.Controller.locationObject = locationObj;
  });


  /**
   * R.c18: Signed in user profile information should be served from the client
   * cache if available.
   */
  it('testCachedUser', function() {
    // Callback for getCurrentUser that tests current user matches user stored
    // in client cache without an API call to /api/users/me.
    var callback = function(resp){
      expect(JSON.stringify(mockUser)).toBe(resp);
    };

    haikuPlus.Model.getCurrentUser(callback);
  });


  /**
   * Clicking create haiku causes modal to show.
   */
  it('testCreateHaikuShowsModal', function() {
    var createForm = $('#' + haikuPlus.View.CREATE_HAIKU_FORM);

    // Test that the modal appears.
    var expected = true;
    var actual = createForm.dialog('isOpen');
    expect(actual).toBe(expected);
  });

  /**
   * Save haiku callback with form close disabled leaves modal up.
   */
  it('testCreateHaiku', function() {
    var createForm = $('#' + haikuPlus.View.CREATE_HAIKU_FORM);

    haikuPlus.View.saveHaikuCallback(false, false);
    server.requests[0].respond(200, {'Content-type': 'application/json'},
        JSON.stringify({}));

    // Test that the modal is still open.
    var expected = true;
    var actual = createForm.dialog('isOpen');
    expect(actual).toBe(expected);

    createForm.dialog('close');
  });


  /**
   * R.c20: Clicking 'close' results in the dialog being hidden.
   */
  it('testCancelCreateHaiku', function() {
    haikuPlus.View.updateUiControls();
    haikuPlus.Controller.createHaiku();
    var createForm = $('#' + haikuPlus.View.CREATE_HAIKU_FORM);

    // Test that the modal appears.
    var expected = true;
    var actual = createForm.dialog('isOpen');
    expect(actual).toBe(expected);

    // Simulate cancelling the form.
    createForm.dialog('close');

    // Test that the modal was hidden.
    var expected = false;
    var actual = createForm.dialog('isOpen');
    expect(actual).toBe(expected);
  });


  /**
   * R.c19: Clicking create haiku causes a POST to /api/haikus.
   */
  it('testCreateHaikuPosts', function() {
    haikuPlus.Controller.locationObject = locationObj;

    var createForm = $('#' + haikuPlus.View.CREATE_HAIKU_FORM);

    haikuPlus.View.saveHaikuCallback(false, false);

    server.requests[0].respond(200, {'Content-type': 'application/json'},
        JSON.stringify({}));

    createForm.dialog('close');

    var postObject = server.requests[0].requestBody;
    expect(postObject).toBe(JSON.stringify(mockHaiku));
  });


  /**
   * Interactive posts were rendered to client haikus.
   */
  it('testPromote', function(){
    haikuPlus.Controller.locationObject = locationObj;
    gapi.interactivepost.wasRender = false;

    haikuPlus.View.addHaiku(mockHaiku);

    expect(gapi.interactivepost.wasRender).toBe(true);
  });


  /**
   * Clicking create haiku refreshes the page.
   */
  it('testCreateHaikuRefresh', function() {
    haikuPlus.Controller.locationObject;
    haikuPlus.Controller.locationObject.pathname = '/haikus/'

    haikuPlus.Controller.locationObject.reloadCalled = false;
    haikuPlus.Controller.saveHaiku(mockHaiku);
    server.requests[0].respond(200, {'Content-type': 'application/json'},
        JSON.stringify({}));
    expect(haikuPlus.Controller.locationObject.reloadCalled).toBe(true);
  });
});


/**
 * Test voting.
 */
describe('Vote Tests', function() {
  /**
   * The setup method called before each test.
   */
  beforeEach(function() {
    server = sinon.fakeServer.create();

    // Authorize the client
    gapi.auth.authorize({}, haikuPlus.Model.init);
    haikuPlus.Model.getCurrentUser();

    haikuPlus.Controller.locationObject = locationObj;

    document.cookie = '';
  });


  /**
   * The tear down method called to clean up any altered data.
   */
  afterEach(function() {
    server.restore();
  });


  /**
   * R.c21: Clicking vote results in a POST.
   */
  it('testVoteUrl', function(){
    haikuPlus.Controller.vote(0);
    var request = server.requests[0];
    if (server.requests[1] != undefined){
      request = server.requests[1];
    }

    expect(request.url).toBe('/api/haikus/0/vote');
  });


  /**
   * Test that vote is to right endpoint.
   */
  it('testVotePost', function(){
    haikuPlus.Controller.vote(0);
    var request = server.requests[0];
    if (server.requests[1] != undefined){
      request = server.requests[1];
    }

    expect(request.url).toBe('/api/haikus/0/vote');
  });


  /**
   * Test that vote is to right endpoint.
   */
  it('testVotePost', function(){
    haikuPlus.Controller.vote(0);
    var request = server.requests[0];
    if (server.requests[1] != undefined){
      request = server.requests[1];
    }

    expect(request.url).toBe('/api/haikus/0/vote');
  });


  /**
   * Test that vote redirects the user to /haikus after they voted on
   * haiku from single haiku UI.
   */
  it ('testVoteRedirectFromSingle', function(){
    var haikuId = 0;
    haikuPlus.Controller.locationObject.pathname = '/haikus/' + haikuId;

    haikuPlus.Controller.vote(haikuId);
    server.requests[0].respond(200, {'Content-Type': 'application/json'},
        JSON.stringify(mockUser));

    var actual = haikuPlus.Controller.locationObject;
    var expected = '/haikus';

    expect(actual).toBe(expected);
  });


  /**
   * Test that vote redirects the user to /haikus after they voted on
   * haiku from the haiku list.
   */
  it ('testVoteRedirect', function(){
    var haikuId = 0;
    haikuPlus.Controller.locationObject.pathname = '/haikus';

    haikuPlus.Controller.vote(haikuId);
    server.requests[0].respond(200, {'Content-Type': 'application/json'},
        JSON.stringify(mockUser));

    var actual = haikuPlus.Controller.locationObject;
    var expected = '/haikus/' + haikuId;

    expect(actual).toBe(expected);
  });

});
