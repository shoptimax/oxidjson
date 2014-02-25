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

/* Controllers */
angular.module('myApp.controllers', [])

/**
 * Controller for Howto partial
 * @param {type} $scope
 * @returns {HowtoCtrl}
 */
.controller('HowtoCtrl', ['$scope', function($scope){
    $scope.oneAtATime = true;        
}])

/**
 * LoginCtrl - controller for login, register, ... actions
 */
.controller('LoginCtrl', ['$rootScope', '$scope', '$location', 'Auth', function($rootScope, $scope, $location, Auth){
    $scope.alerts = [];
    $scope.addAlert = function(type, msg) {
        $scope.alerts.push({type: type, msg: msg});
    };
    $scope.closeAlert = function(index) {
        $scope.alerts.splice(index, 1);
    };

    $scope.isLoggedIn = function() {
        return $rootScope.validLogin;
    }

    /**
     * Login function
     */
    $scope.login = function() {
        Auth.login({
            username: $scope.username,
            password: $scope.password
        },
        function(res) {
            console.log("YESSS");
        },
        function(err) {
            $scope.addAlert('error', "Error during login");
        });
    };
    /**
     * Logout function
     */
    $scope.logout = function() {
        Auth.logout(function() {
            $location.path('/login');

        }, function() {
            $rootScope.error = "Failed to logout";
        });
    };
}])

/**
 * Menu controller
 * @param {type} $scope
 * @returns {ListCtrl} 
 */
.controller('MenuCtrl', ['$rootScope', '$scope', '$modal', '$log', 'localize', function($rootScope, $scope, $modal, $log, localize){
    $scope.isLoggedIn = function() {
        return $rootScope.validLogin;
    };
    /**
     * Language selection
     */
    $scope.setLanguage = function(lang) {
        localize.setLanguage(lang);
    };

    $scope.items = ['item1', 'item2', 'item3'];
    /**
     * View modal credits window
     * @returns {undefined}
     */
    $scope.openCredits = function() {
        var modalInstance = $modal.open({
            templateUrl: 'creditsContent.html',
            controller: 'ModalInstanceCtrl',
            resolve: {
                items: function() {
                    return $scope.items;
                }
            }
        });
        modalInstance.result.then(function(selectedItem) {
            $scope.selected = selectedItem;
        }, function() {
            $log.info('Modal dismissed at: ' + new Date());
        });
    };
}])

/**
 * Controller for modal popus
 * @param {type} $rootScope
 * @param {type} $scope
 * @param {type} $modalInstance
 * @param {type} items
 * @returns {ModalInstanceCtrl}
 */
.controller('ModalInstanceCtrl', ['$scope', '$modalInstance', 'items', function($scope, $modalInstance, items){
    $scope.items = items;
    $scope.selected = {
        item: $scope.items[0]
    };
    $scope.ok = function() {
        $modalInstance.close($scope.selected.item);
    };
    $scope.cancel = function() {
        $modalInstance.dismiss('cancel');
    };
}]);
