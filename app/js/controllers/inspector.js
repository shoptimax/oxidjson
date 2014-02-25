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
var myApp = angular.module('myApp.controllers');

/**
 * Controller for single object inspection
 * @param {type} $scope
 * @returns {InspectorCtrl}
 */
myApp.controller('InspectorCtrl', ['$scope', '$rootScope', '$http', 'Auth', '$routeParams', 'localize', 'OxRest', 'promiseTracker', function($scope, $rootScope, $http, Auth, $routeParams, localize, OxRest, promiseTracker){
    /**
     * PromiseTracker for displaying a wait animation during 
     * async. AJAX tasks
     */  
    $scope.ajaxTracker = promiseTracker('atracker');
    
    $scope.checkLogin = function() {
        return Auth.isLoggedIn();
    };
    $scope.checkLogin();

    $scope.alerts = [];
    $scope.addAlert = function(type, msg) {
        $scope.alerts.push({type: type, msg: msg});
    };
    $scope.closeAlert = function(index) {
        $scope.alerts.splice(index, 1);
    };

    $scope.currClass = "oxarticle";
    $scope.currId = '';
    if ($routeParams.oxid) {
        $scope.currId = $routeParams.oxid;
    }
    if ($routeParams.type) {
        $scope.currClass = $routeParams.type;
    }
    /**
     * load object
     * @returns {undefined}
     */
    $scope.fetchObject = function() {
        $scope.currReqUrl = $rootScope.basePath + "/oxrest/oxobject/" + $scope.currClass + "/" + $scope.currId;
        var responsePromise = OxRest.getRestObject($scope.currClass, $scope.currId);
        // display waiting animation while processing....
        $scope.ajaxTracker.addPromise(responsePromise);
        // whenever we are finished, process data...
        responsePromise.then(function(result) {
            $scope.addAlert('success', localize.getLocalizedString('_MsgObjectLoaded_'));
            $scope.oxobject = result.data;
        }, function(error) {
            $scope.addAlert('error',  error.data + " (Error " + error.status + ")");
            $scope.oxobject = {};
            $scope.currId = '';
        });
    };
    if ($scope.currId !== '') {
        $scope.fetchObject();
    }

    /**
     * Saving data back to server after editing object
     * @returns {undefined}
     */
    $scope.updateData = function() {
        if($rootScope.readOnly) {
            alert("Disabled in demo mode");
            return;
        }
        
        $scope.currReqUrl = $rootScope.basePath + "/oxrest/oxobject/" + $scope.currClass + "/" + $scope.currId;
        var responsePromise = OxRest.putRestObject($scope.currClass, $scope.currId, $scope.oxobject);
        // display waiting animation while processing....
        $scope.ajaxTracker.addPromise(responsePromise);
        // whenever we are finished, process data...
        responsePromise.then(function(result) {
            $scope.addAlert('success', localize.getLocalizedString('_MsgObjectSaved_'));
            $scope.currId = result.data.oxid;
            $scope.oxobject = result.data;
        }, function(error) {
            $scope.addAlert('error',  error.data + " (Error " + error.status + ")");
        });
    }
    /**
     * Saving new object
     * @returns {undefined}
     */
    $scope.newData = function() {
        if($rootScope.readOnly) {
            alert("Disabled in demo mode");
            return;
        }
        $scope.currReqUrl = $rootScope.basePath + "/oxrest/oxobject/" + $scope.currClass + "/" + $scope.currId;
        var responsePromise = OxRest.postRestObject($scope.currClass, $scope.currId, $scope.oxobject);
        // display waiting animation while processing....
        $scope.ajaxTracker.addPromise(responsePromise);
        // whenever we are finished, process data...
        responsePromise.then(function(result) {
            $scope.addAlert('success', localize.getLocalizedString('_MsgObjectCreated_'));
            $scope.currId = result.data.oxid;
            $scope.oxobject = result.data;
        }, function(error) {
            $scope.addAlert('error',  error.data + " (Error " + error.status + ")");
        });
    }
    /**
     * Deleting object
     * @returns {undefined}
     */
    $scope.delData = function() {
        if($rootScope.readOnly) {
            alert("Disabled in demo mode");
            return;
        }
        $scope.currReqUrl = $rootScope.basePath + "/oxrest/oxobject/" + $scope.currClass + "/" + $scope.currId;
        var responsePromise = OxRest.deleteRestObject($scope.currClass, $scope.currId, $scope.oxobject);
        // display waiting animation while processing....
        $scope.ajaxTracker.addPromise(responsePromise);
        // whenever we are finished, process data...
        responsePromise.then(function(result) {
            //console.log("result: " + JSON.stringify(result));
            $scope.addAlert('success', localize.getLocalizedString('_MsgObjectDeleted_'));
            $scope.oxobject = {};
            $scope.currId = '';
        }, function(error) {
            $scope.addAlert('error',  error.data + " (Error " + error.status + ")");
        });
    }
}]);

