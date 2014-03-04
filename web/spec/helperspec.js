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
describe('Helper and utility tests', function() {
  /**
   * The setup method called before each test.
   */
  beforeEach(function() {
    haikuPlus.Controller.locationObject = locationObj;
    server = sinon.fakeServer.create();

    haikuPlus.Model.init(GAPI_AUTH_AUTHORIZE);
  });


  /**
   * The tear down method called to clean up any altered data.
   */
  afterEach(function() {
    server.restore();
  });


  /**
   * Test getting query strings.
   */
  it('testGetQueryStrings', function(){
    var queryString = '?field1=value1&field2=value2&field3=value3';
    var mockLocation = {};
    mockLocation.search = queryString;

    var actual = haikuPlus.Helper.getParameters(mockLocation);
    var expected = [{'field1':'value1'}, {'field2':'value2'},
        {'field3':'value3'}];

    var actual = haikuPlus.Helper.getParameters(mockLocation);

    expect(JSON.stringify(actual) == JSON.stringify(expected)).toBe(true);
  });


  /**
   * Test getting UTF-8 query strings.
   */
  it('testGetUTFQueryStrings', function(){
    var queryString =
      '?first=this+is+a+field&second=was+it+clear+%28already%29%3F'

    var mockLocation = {};
    mockLocation.search = queryString;

    var actual = haikuPlus.Helper.getParameters(mockLocation);
    var expected = [{'first':'this+is+a+field'},
        {'second':'was+it+clear+(already)?'}];

    var actual = haikuPlus.Helper.getParameters(mockLocation);

    expect(JSON.stringify(actual) == JSON.stringify(expected)).toBe(true);
  });


  /**
   * Test that getting the path works.
   */
  it('testGetPath', function() {
    expect(haikuPlus.Helper.getPath(locationObj)).toBe(locationObj.pathname);
  });


  /**
   * Test force disconnect.
   */
  it ('testForceDisconnect', function() {
    haikuPlus.Helper.forceDisconnect();

    var expected = haikuPlus.Model.DISCONNECT_API_ENDPOINT +
        GAPI_AUTH_GETTOKEN.access_token;

    expect(server.requests[0].url).toBe(expected);
  });


  /**
   * Test that the path parser works on /haikus/.
   */
  it('testGetHaikusSlash', function() {
    var testPath = '/haikus/';
    var expected = -1;
    var actual = haikuPlus.Helper.haikuIdFromPath({'pathname': testPath});

    expect(expected).toBe(actual);
  });


  /**
   * Test that the path parser boolean works on /haikus/.
   */
  it('testIsHaikusNoSlash', function() {
    var testPath = '/haikus/';
    haikuPlus.Helper.haikuIdFromPath({'pathname': testPath});

    var expected = false;
    var actual = haikuPlus.Helper.isSingleHaiku({'pathname': testPath});

    expect(actual).toBe(expected);
  });


  /**
   * Test that the path parser works on /haikus.
   */
  it('testGetHaikus', function() {
    var testPath = '/haikus';
    haikuPlus.Helper.haikuIdFromPath({'pathname': testPath});

    var expected = -1;
    var actual = haikuPlus.Helper.haikuIdFromPath({'pathname': testPath});

    expect(actual).toBe(expected);
  });


  /**
   * Test that the path parser boolean works on /haikus.
   */
  it('testIsHaikusNoSlash', function() {
    var testPath = '/haikus';
    haikuPlus.Helper.haikuIdFromPath({'pathname': testPath});

    var expected = false;
    var actual = haikuPlus.Helper.isSingleHaiku({'pathname': testPath});

    expect(actual).toBe(expected);
  });


  /**
   * Test that the path parser works on /haikus/1.
   */
  it('testGetHaikuFromPathIntNoSlash', function() {
    var testPath = 'haikus/1';

    var expected = '1';
    var actual = haikuPlus.Helper.haikuIdFromPath({'pathname': testPath});

    expect(actual).toBe(expected);
  });


  /**
   * Test that the path parser works on /haikus/1/.
   */
  it('testGetHaikuFromPathInt', function() {
    var testPath = 'haikus/1/';
    haikuPlus.Helper.haikuIdFromPath({'pathname': testPath});

    var expected = '1';
    var actual = haikuPlus.Helper.haikuIdFromPath({'pathname': testPath});

    expect(actual).toBe(expected);
  });

  /**
   * Test that the path parser works on /haikus/{string}/.
   */
  it('testGetHaikuFromPathString', function() {
    var testPath = 'haikus/abacabb/';
    haikuPlus.Helper.haikuIdFromPath({'pathname': testPath});

    var expected = 'abacabb';
    var actual = haikuPlus.Helper.haikuIdFromPath({'pathname': testPath});

    expect(actual).toBe(expected);
  });


  /**
   * Test that the path parser works on /haikus/{string}.
   */
  it('testGetHaikuFromPathStringNoSlash', function() {
    var testPath = 'haikus/abacabb';
    haikuPlus.Helper.haikuIdFromPath({'pathname': testPath});

    var actual = haikuPlus.Helper.haikuIdFromPath({'pathname': testPath});
    var expected = 'abacabb';    

    expect(actual).toBe(expected);
  });


  /**
   * Test is single haiku in first URL pattern without leading slash.
   */
  it('isSingleHaikuNoSlash', function() {
    var testPath = 'haikus/1';
    haikuPlus.Helper.haikuIdFromPath({'pathname': testPath});

    var actual = haikuPlus.Helper.isSingleHaiku({'pathname': testPath});
    var expected = true;

    expect(actual).toBe(expected);
  });

  /**
   * Test is single haiku in alternate URL pattern with leading slash.
   */
  it('isSingleHaiku', function() {
    var testPath = '/haikus/1';
    haikuPlus.Helper.haikuIdFromPath({'pathname': testPath})

    var actual = haikuPlus.Helper.isSingleHaiku({'pathname': testPath});
    var expected = true;

    expect(actual).toBe(expected);
  });


  /**
   * Test is single haiku in string pattern without leading slash.
   */
  it('isSingleHaikuNoSlashAlpha', function() {
    var testPath = 'haikus/abd';

    var actual = haikuPlus.Helper.isSingleHaiku({'pathname': testPath});
    var expected = true;
    expect(actual).toBe(expected);
  });

  /**
   * Test is single haiku in alternate URL pattern with leading slash.
   */
  it('isSingleHaikuAlpha', function() {
    var testPath = '/haikus/abc';

    var actual = haikuPlus.Helper.isSingleHaiku({'pathname': testPath});
    var expected = true;
    expect(actual).toBe(expected);
  });


  /**
   * Test refreshing the page.
   */
  it ('testRefresh', function() {
    haikuPlus.Controller.locationObject = locationObj;
    haikuPlus.Controller.locationObject.reloadCalled = false;
    haikuPlus.Helper.refresh();

    expect(haikuPlus.Controller.locationObject.reloadCalled).toBe(true);
  });


  /**
   * TODO: Test creating specifically formatted date strings.
   */
});
