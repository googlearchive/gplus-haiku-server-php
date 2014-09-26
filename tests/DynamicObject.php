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


/*
 * Defines an object that can be assigned new methods.
 *
 * Example:
 *   $func = function() { return 'Success'; };
 *   $dynamic = new DynamicObject();
 *   $dynamic->newMethod = $func;
 *   echo $dynamic->newMethod(); // print 'Success'
 */
class DynamicObject {


  /**
   * Override magic method to route all calls to assigned methods.
   *
   * @param $method string Name of method on this object.
   * @param $args array To method call.
   * @return mixed[] Result of method call.
   */
  public function __call($method, $args) {
    if (isset($this->$method)) {
      $func = $this->$method;
      return call_user_func_array($func, $args);
    }
  }
}

