'use strict';

/* Services */


var services = angular.module('myApp.services', [ 'ngCookies', 'ngResource' ]);

/**
 * Auth factory to handle logins / logouts etc.
 */
services.factory('Auth', function($http, $cookieStore, $cookies, $rootScope, $location) {
	return {
		/**
		 * Check if user is logged in, if not the Shiro filter will respond with
		 * a 401 status which our http interceptor will capture, see app.js!
		 */
		isLoggedIn : function() {
			$http.post('/rest/party/checklogin', "{}").success(function(response) {
                            $rootScope.validLogin = true;
			}).error(function(err) {
                            $rootScope.validLogin = false;
			})
		},
		
		/**
		 * call login REST
		 */
		login : function(user, success, error) {
			console.log("User: " + user.username);
			
                        var jsonData = '{}';
			var secStr = CryptoJS.enc.Utf8.parse(user.username + ":" + user.password);
			var base64 = CryptoJS.enc.Base64.stringify(secStr);
                        console.log("Auth header: " + 'Smx ' + base64);
			$http.get('/smxjson/action/login', {
				headers : {
					// use custom auth header
					Authorization : 'Smx ' + base64
				}
			}).success(function(response) {
				console.log("Login data: " + JSON.stringify(response));
				
				// save auth info to cookies?
				$cookieStore.put("sid", response.sid);
				
				// redirect to original request?
				if ($rootScope.reqPath) {
                                    console.log("Forwarding to " + $rootScope.reqPath);
                                    $location.path($rootScope.reqPath)
				} else {
                                    $location.path('/list');
				}

			}).error(function(response, status, headers, config) {
				$rootScope.validLogin = false;
			});
		},
		/**
		 * call logout REST
		 */
		logout : function(success, error) {
			// unsubscribe from Atmosphere channel
			
			var jsonData = '{}';
			$http.post('/smxjson/action/logout', jsonData).success(function(response) {
				$rootScope.validLogin = false;
				delete $cookies['PHPSESSIONID'];
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
services.value('version', '0.0.1');