/**
 *    This file is part of OXIDJson.
 *
 *    OXIDJson is free software: you can redistribute it and/or modify
 *    it under the terms of the GNU General Public License as published by
 *    the Free Software Foundation, either version 3 of the License, or
 *    (at your option) any later version.
 *
 *    OXIDJson is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU General Public License for more details.
 *
 *    You should have received a copy of the GNU General Public License
 *    along with this package.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @link      http://www.shoptimax.de
 * @package   oxjson
 * @copyright (C) shoptimax GmbH 2013
 * @version 1.0.0
 * @author Stefan Moises <moises@shoptimax.de>
 */

'use strict';

/* Services */

var services = angular.module('myApp.services', [ 'ngCookies', 'ngResource' ]);

/**
 * Auth factory to handle logins / logouts etc.
 */
services.factory('Auth', function($http, $cookieStore, $cookies, $rootScope, $location) {
	return {
		/**
		 * Check if user is logged in, if not the REST API will respond with
		 * a 401 status which our http interceptor will capture, see app.js!
		 */
		isLoggedIn : function() {
			$http.post($rootScope.basePath + '/oxrest/action/checklogin', "{}").success(function(response) {
                            $rootScope.validLogin = true;
			}).error(function(err) {
                            $rootScope.validLogin = false;
			})
		},
		
		/**
		 * call login REST
		 */
		login : function(user, success, error) {
                        var jsonData = '{}';
			var secStr = CryptoJS.enc.Utf8.parse(user.username + ":" + user.password);
			var base64 = CryptoJS.enc.Base64.stringify(secStr);
			$http.post($rootScope.basePath + '/oxrest/action/login', jsonData, {
				headers : {
                                    // use custom auth header
                                    Authorization : 'Ox ' + base64
				}
			}).success(function(response) {
                                if($rootScope.debug) {
                                    console.log("Login data: " + JSON.stringify(response));
                                }				
                                $rootScope.validLogin =  true;
				// save auth info to cookies?
				//$cookieStore.put("sid", response.sid);

                                // call the callback function :)
                                success();
                                
				// redirect to original request?
				if ($rootScope.reqPath) {
                                    if($rootScope.debug) {
                                        console.log("Forwarding to " + $rootScope.reqPath);
                                    }
                                    $location.path($rootScope.reqPath)
				} else {
                                    $location.path('/list');
				}

			}).error(function(response, status, headers, config) {
				$rootScope.validLogin = false;
                                // call the callback function :)
                                error();
			});
		},
		/**
		 * call logout REST
		 */
		logout : function(success, error) {
			var jsonData = '{}';
			$http.post($rootScope.basePath + '/oxrest/action/logout', jsonData).success(function(response) {
				$rootScope.validLogin = false;
				// forward
				$location.path('/');
			}).error(function(response, status, headers, config) {
			});
		}
	};
});

/**
 * Just a value :-)
 */
services.value('version', '0.9.0');