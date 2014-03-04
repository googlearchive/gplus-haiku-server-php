/**
 * The authorization code header.
 * @const
 */
var C_X_OAUTH_CODE = 'X-OAuth-Code';

/**
 * The authorization header.
 * @const
 */
var C_AUTH_HEADER = 'Authorization';

/**
 * The JSON returned from the server for the currently signed in user.
 */
var mockUser = {
  'id': 'zerozero',
  'google_plus_id': '123x08hsdf',
  'google_display_name': 'test user name',
  'google_photo_url': 'http://',
  'google_profile_url': 'http://',
  'last_updated': Date()
};

/**
 * Mock data for a haiku.
 */
var mockHaiku  = {
  "title":"",
  "line_one":"",
  "line_two":"",
  "line_three":"",
  "votes":0,
  "creation_time":"",
  "author": mockUser
};

/**
 * Mock data for a haiku list
 */
var mockHaikus = [ mockHaiku, mockHaiku, mockHaiku ];

/**
 * The global containing the client id that is set in the template.
 */
 var G_CLIENT_ID = '268XXXXX2829.apps.googleusercontent.com';
/**
 * The JSON returned from a valid call to gapi.auth.authorize.
 */
var GAPI_AUTH_AUTHORIZE = {
  'state': '',
  'access_token': 'ya29.XXXXXX',
  'token_type': 'Bearer',
  'expires_in': '3600',
  'code': '4/ZZZZ.YYYY-T7QgwI',
  // Might want to leave this as a valid, decodable, token
  'id_token': 'AAAA.bbbb.CCCC',
  'authuser': '0',
  'session_state': 'd13f99f357e550a63a686da..02e8',
  'prompt': 'none',
  status: {
    'method': '',
    'error': '',
  },
  'client_id': G_CLIENT_ID,
  'scope': 'https://www.googleapis.com/auth/plus.login',
  'cookie_policy': 'single_host_origin',
  'response_type': 'code token id_token gsession',
  'issued_at': '1390550905',
  'expires_at': '1390554505',
  '_aa': '0'
};

/**
 * The JSON returned from a call to gapi.auth.getToken().
 */
var GAPI_AUTH_GETTOKEN = {
  "state":"",
  "access_token":"ya29.XXXXXX",
  "token_type":"Bearer",
  "expires_in":"3600",
  "code":"4/ZZZZ.YYYY-T7QgwI",
  "scope":"https://www.googleapis.com/auth/plus.login " +
    "https://www.googleapis.com/auth/plus.moments.write " +
    " https://www.googleapis.com/auth/plus.me " +
    " https://www.googleapis.com/auth/plus.circles.members.read",
  "id_token":"abcd.egkkk.jjj",
  "authuser":"0",
  "prompt":"consent",
  "session_state": "..2c15",
  "client_id": G_CLIENT_ID,
  "g_user_cookie_policy":"single_host_origin",
  "cookie_policy":"single_host_origin",
  "response_type":"code token id_token gsession",
  "issued_at":"1390550905",
  "expires_at":"1390554505",
  "g-oauth-window":"status",
  "google_logged_in":true,
  "signed_in":true,
  "method":"PROMPT"
};

/**
 * A stub for the Google API client library.
 */
var gapi = {
  auth: {
    authorize:
    function(object, callback) {
      // The object is JSON for the callback is triggered with returned
      // data representing the string of gapi.auth.authorize.
      callback(GAPI_AUTH_AUTHORIZE);
    },
    getToken:
    function(object, callback) {
      // The object is JSON for the callback is triggered with returned
      // data representing the string of gapi.auth.getToken.
      return GAPI_AUTH_GETTOKEN;
    },
    signOut: function (){}
  },
  interactivepost: {
    wasRender: false,
    render:
    function(object, callback){
      gapi.interactivepost.wasRender = true;
    }
  }

};

/**
 * A stub for the browser location object.
 */
var locationObj = {
  reloadCalled: false,
  reload: function() {
    this.reloadCalled = true;
  },
  pathname: 'foo/bar/foo',
};

haikuPlus.Controller.locationObject = locationObj;
