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
 * @copyright (C) shoptimax GmbH 2013-2014
 * @version 1.0.1
 * @author Stefan Moises <moises@shoptimax.de>
 */

'use strict';

// Declare app level module which depends on filters, and services
angular.module('myApp', ['myApp.controllers', 'myApp.filters', 'myApp.services', 'myApp.directives', 'ui.bootstrap', 'ngGrid', 'ajoslin.promise-tracker', 'localization']).
        config(['$routeProvider', '$locationProvider', '$httpProvider', '$provide',
    function($routeProvider, $locationProvider, $httpProvider, $provide) {

        //register the interceptor as a service, intercepts ALL angular ajax http calls
        $provide.factory('myHttpInterceptor', function($q, $window, $rootScope, $location) {
            return function(promise) {
                return promise.then(function(response) {
                    // do something on success
                    if($rootScope.debug) {
                        console.log("INTERCEPTOR SUCCESS: " + response.status);
                    }
                    /*
                     $.each(response.headers(), function(key, val) {
                     console.log(key + " - " + val);
                     });
                     */
                    return response;
                }, function(response) {
                    // do something on error
                    if (response.status === 401) {
                        console.error("INTERCEPTOR ERROR 401: " + response.status);
                        $rootScope.validLogin = false;
                        if ($location.path() !== '/login') {
                            $rootScope.reqPath = $location.path();
                        }
                        // redirect
                        $location.path('/login');
                        return $q.reject(response);
                    } else {
                        console.error("INTERCEPTOR ERROR OTHER: " + response.status);
                        return $q.reject(response);
                    }
                });
            };
        })
        $httpProvider.responseInterceptors.push('myHttpInterceptor');

        // ROUTES
        $routeProvider.when('/howto', {templateUrl: 'partials/howto.html', controller: 'HowtoCtrl'});
        $routeProvider.when('/inspector', {templateUrl: 'partials/inspector.html', controller: 'InspectorCtrl'});
        $routeProvider.when('/inspector/:type/:oxid', {templateUrl: 'partials/inspector.html', controller: 'InspectorCtrl'});
        $routeProvider.when('/login', {templateUrl: 'partials/login.html', controller: 'LoginCtrl'});
        $routeProvider.when('/list', {templateUrl: 'partials/list.html', controller: 'ListCtrl'});
        $routeProvider.when('/home', {templateUrl: 'partials/home.html', controller: 'LoginCtrl'});
        // default route
        $routeProvider.otherwise({redirectTo: '/home'});
    }])
        .run(
        ['$rootScope', '$location', '$http',
            function($rootScope, $location, $http) {
                console.log("Running app... " + $location.path());
                // set global vars
                $rootScope.debug = true;
                // set data to read only for demo
                $rootScope.readOnly = false;
                $rootScope.basePath = window.location.pathname.replace("/app/", "");
                $rootScope.basePath = $rootScope.basePath.replace("index.html", "");
                // this is only called on first load / full reload of the page,
                $http.get($rootScope.basePath + '/oxrest/action/checklogin').success(function(response) {
                    $rootScope.validLogin = true;
                }).error(function(err) {
                    $rootScope.validLogin = false;
                })
            }]);

