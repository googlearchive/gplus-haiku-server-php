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
 * Various helper methods for the Haiku+ JavaScript client.
 * @constructor
 */
haikuPlus.Helper = function() { };


/**
 * Wrapper for console.log used for browser compatibility.
 *
 * @param {Object} message The object to log.
 */
haikuPlus.Helper.prototype.log = function(message) {
  var availConsole = console || window.console;
  try{
    if (message === undefined) {
      return;
    }

    var stackTrace = undefined;
    try {
      // Force an exception.
      fakeException.forStacktrace.error = 0;
    }catch(e){
      // Reduce exception stack to the last called line.
      stackTrace = e.stack.split('at')[2].substring(1);
    }

    if (availConsole) {
      availConsole.log(message);
      availConsole.log(stackTrace);
    }
  } catch(e) {
    if (availConsole) {
      availConsole.log('Portable console failure: ' + e);
    }
  }
};


/**
 * Extract the parameter string from the page's location.
 *
 * @param {Object} locationObject The browser's location object that contains
 *   the querystring in the search attribute.
 * @returns {Object} Key value pair of parameters and values.
 */
haikuPlus.Helper.prototype.getParameters = function(locationObject) {
  if (locationObject === undefined){
    locationObject = haikuPlus.Controller.getLocationObject();
  }

  // In case the query string is UTF-8 encoded.
  var queryString = decodeURIComponent(locationObject.search.substring(1));

  // Callback for mapping key / value pairs to objects.
  var keyValueSplitter = function(keyValueStr) {
    var keyValueArr = keyValueStr.split('=');

    var keyValueObject = {};
    keyValueObject[keyValueArr[0]] = keyValueArr[1];
    return keyValueObject;
  };

  // Split the query string on '&' and map the objects using the following
  // function.
  return queryString.split('&').map(keyValueSplitter);
}

/**
 * Return a nicely formatted time string.
 *
 * @param {timeString} A parseable string for the time to prettify.
 * @return {string} The formatted string representing the time.
 */
haikuPlus.Helper.prototype.getPrettyTime = function(timeString) {
  var date = new Date(timeString);

  var monthNumToMonthString = {
    0: 'Jan',
    1: 'Feb',
    2: 'Mar',
    3: 'Apr',
    4: 'May',
    5: 'June',
    6: 'July',
    7: 'Aug',
    8: 'Sep',
    9: 'Oct',
    10: 'Nov',
    11: 'Dec'
  };

  var daySuffix = 'th ';
  if ((date.getDate() % 10) == 1) daySuffix = 'st ';
  if ((date.getDate() % 10) == 2) daySuffix = 'nd ';
  if (date.getDate > 20 || date.getDate < 10){
    if ((date.getDate() % 10) == 3) daySuffix = 'rd ';
  }


  return monthNumToMonthString[date.getMonth()] + ' ' + date.getDate() +
      daySuffix + date.getFullYear();
}


/**
 * Retrieve the GET parameters and return the value of the specified
 * parameter.
 *
 * @param {Object} locationObject The browser's location object that contains
 *   the querystring in the search attribute.
 * @param {string} parameter The parameter to search for.
 * @returns {string} The value of the specified parameter if it is present;
 * otherwise, returns null.
 */
haikuPlus.Helper.prototype.searchParameters = function(locationObject,
    parameter) {
  locationObject = locationObject || location;

  var allParameters = haikuPlus.Helper.getParameters(locationObject);

  for (var index=0; index < allParameters.length; index++) {
    if (allParameters[index][parameter] != undefined){
      return allParameters[index][parameter];
    }
  }

  return null;
};

/**
 * Tests whether the filtered parameter has been passed to the client.
 *
 * @returns {bool} True if this is a filtered request.
 */
haikuPlus.Helper.prototype.isFiltered = function(){
  return this.searchParameters(undefined, 'filter') == 'circles';
}

/**
 * Retrieves the page's path given a location object.
 *
 * @param {Object} locationObject The browser's location object that contains
 *   the URI path in the pathname attribute.
 * @returns {string} The url path.
 */
haikuPlus.Helper.prototype.getPath = function(locationObject){
  if (locationObject) {
    return locationObject.pathname;
  }
  return haikuPlus.Controller.getLocationObject().pathname;
}


/**
 * Returns a haiku ID from the path.
 *
 * @param {object} The object containing the location path.
 * @return {string} Id of the haiku; otherwise, returns -1;
 */
haikuPlus.Helper.prototype.haikuIdFromPath = function(locationObject) {
  var splitPath = [];

  if (locationObject) {
    splitPath = this.getPath(locationObject).split('/');
  }else{
    splitPath =
        this.getPath(haikuPlus.Controller.getLocationObject()).split('/');
  }

  var haikuEndpointPart =
      this.searchAndReplace(haikuPlus.Model.HAIKU_PATH, '/', '');

  // Suppport any path that has /haikus/{id} in it.
  for (var i=0; i < splitPath.length; i++){
    if (splitPath[i] == haikuEndpointPart && splitPath.length >= 2){
      if (splitPath[i+1] != '' && splitPath[i+1] != undefined){
        return splitPath[i+1] + '';
      }
    }
  }
  return -1;
}

/**
 * Returns true if the current path is to a single haiku.
 *
 * @param {Object} locationObject The browser's location object.
 * @return {bool} True if currently on a single haiku (not a list).
 */
haikuPlus.Helper.prototype.isSingleHaiku = function(locationObject){
  return (this.haikuIdFromPath(locationObject) != -1);
};


/**
 * Redirects the browser to the given URI.
 *
 * @param {Object} locationObject The object representing the browser's
 * location.
 * @param {string} uriString String representing the URI to redirect to.
 */
haikuPlus.Helper.prototype.redirect = function(locationObject, uriString) {
  if (locationObject) {
    locationObject = uriString;
    return;
  }
  // Update location on the controller if we don't have a location object.
  haikuPlus.Controller.updateLocationObject(uriString);
};


/**
 * Refreshes the page.
 *
 * @param {Object} locationObject The object representing the browser's
 * location.
 */
haikuPlus.Helper.prototype.refresh = function(locationObject) {
  if (locationObject) {
    locationObject.reload();
    return;
  }
  haikuPlus.Controller.getLocationObject().reload();
};


/**
 * Disconnects the user's account from this app client-side.
 */
haikuPlus.Helper.prototype.forceDisconnect = function() {
  haikuPlus.Model.DISCONNECT_CLIENTSIDE = true;
  haikuPlus.Model.disconnect(
    function(resp){
      haikuPlus.Helper.log('Forcing client-side disconnect.');
      haikuPlus.Helper.log(resp);
    }
  );
};


/**
 * Search and replace values in strings.
 *
 * @param {string} str The string to search.
 * @param {string} search The string to search for.
 * @param {string} replace The string to replace with.
 * @return {string} The original string with the searched values replaced.
 */
haikuPlus.Helper.prototype.searchAndReplace = function(str, search, replace){
  return str.split(search).join(replace);
};


/**
 * Gets the host's path.
 *
 * @param {object} locationObject The browser's location object.
 * @return {string} The current host's path.
 */
haikuPlus.Helper.prototype.getOrigin = function(locationObject){
  locationObject = locationObject ||
      haikuPlus.Controller.getLocationObject();

  return locationObject.protocol + '//' + locationObject.host;
}


/**
 * Attach to the haikuPlus namespace.
 */
haikuPlus.Helper = new haikuPlus.Helper();
