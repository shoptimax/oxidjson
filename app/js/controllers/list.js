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
 * List controller
 * @param {type} $scope
 * @returns {ListCtrl} 
 */
myApp.controller('ListCtrl', ['$scope', '$http', '$rootScope', '$location', 'promiseTracker', '$timeout', 'localize', 'OxRest', function($scope, $http, $rootScope, $location, promiseTracker, $timeout, localize, OxRest){
    $scope.listTracker = promiseTracker('list');
    $scope.saveTracker = promiseTracker('listsave');
    /**
     * Return language for ngGrid
     * we are using stuff like "en-US", but ngGrid expects only "en" e.g.
     * so we need to strip at the "-"
     * @returns string
     */
    $scope.getGridLang = function() {
        var currLang = localize.getLanguage();
        var idx = 2;
        if(currLang.indexOf('-') > 0) {
            idx = currLang.indexOf('-');
        }
        return currLang.substr(0, idx);
        
    };
    // TODO: improve localization
    $scope.allListTypes =  {'de': [{id:'1', name:'Artikel', value:'oxarticlelist'},
                         {id:'2', name:'Bestellungen', value:'oxorder'},
                         {id:'3', name:'Kategorien', value:'oxcategorylist'},
                         {id:'4', name:'Benutzer', value:'oxuserlist'},
                         {id:'5', name:'CMS-Seiten', value:'oxcontents'},
                         {id:'6', name:'Länder', value:'oxcountrylist'}
                        ],
                        'en': [{id:'1', name:'Articles', value:'oxarticlelist'},
                         {id:'2', name:'Orders', value:'oxorder'},
                         {id:'3', name:'Categories', value:'oxcategorylist'},
                         {id:'4', name:'Users', value:'oxuserlist'},
                         {id:'5', name:'CMS pages', value:'oxcontents'},
                         {id:'6', name:'Countries', value:'oxcountrylist'}
                        ]
    };
    $scope.listTypes = $scope.allListTypes[$scope.getGridLang()];
    $scope.listtype = $scope.listTypes[0];
    
    $scope.currReqUrl = '';
    
    $scope.listVariation = "list";// "oxlist" (fully loaded objects) or "list" (arrays only)
    
    $scope.alerts = [];
    $scope.addAlert = function(type, msg) {
        $scope.alerts.push({type: type, msg: msg});
    };
    $scope.closeAlert = function(index) {
        $scope.alerts.splice(index, 1);
    };

    $scope.filterOptions = {
        filterText: "",
        useExternalFilter: false
    };
    // sort
    $scope.sortOptions = {
        fields: ["oxid", "oxtimestamp", "oxordernr", "oxtitle", "oxvarselect", "oxprice"],
        directions: ["ASC"]
    };
    $scope.totalServerItems = 0;
    $scope.pagingOptions = {
        pageSizes: [10, 25, 50, 100, 250],
        pageSize: 10,
        currentPage: 1
    };
    /**
     * Sets new paging data
     * @param {type} data
     * @param {type} page
     * @param {type} pageSize
     * @param {type} numTotal
     * @returns {undefined}
     */
    $scope.setPagingData = function(data, page, pageSize, numTotal) {
        $scope.myData = data;
        $scope.totalServerItems = numTotal;
        if (!$scope.$$phase) {
            $scope.$apply();
        }
    };
    
    /**
     * Update the grid with button click
     * @returns {undefined}
     */
    $scope.updateGrid = function() {
        // reset filter and page
        $scope.gridOptions.$gridScope.filterText = '';
        $scope.pagingOptions.currentPage = 0;
        //$scope.sortOptions = {fields: ["oxid"], directions: ["ASC"]};
        // refresh data
        $scope.getPagedDataAsync($scope.pagingOptions.pageSize, $scope.pagingOptions.currentPage);
    }
    

    /**
     * React on language change
     * E.g. change grid language here
     */
    $scope.$on('localizeResourcesUpdates', function(event, args) {
        //console.log($scope.gridOptions.$gridScope);
        var gridLang = $scope.getGridLang();
        $scope.gridOptions.$gridScope.i18n = window.ngGrid.i18n[gridLang];
        $scope.listTypes = $scope.allListTypes[gridLang];
    });
    
    /**
     * Loads data from the REST service
     * @param {type} pageSize
     * @param {type} page
     * @param {type} searchText
     * @param {type} sortInfo
     * @returns {undefined}
     */
    $scope.getPagedDataAsync = function(pageSize, page, searchText, sortInfo) {
        var wait = $timeout(function() {
            // server side paging starts at 0
            if (page > 0) {
                page = page - 1;
            }
            var data;
            var mlist = $scope.listtype.value;
            // sort by
            var sb = [];
            var sorting = '';
            if (sortInfo) {

                for (var i = 0; i < sortInfo.fields.length; i++) {
                    if ($rootScope.debug) {
                        console.log("sorting: " + i + ") " + sortInfo.fields[i]);
                    }
                    sb.push(sortInfo.fields[i]);
                    sb.push(sortInfo.directions[i]);
                }
                sorting = "/" + sb.join(" ");
            }
            if (searchText) {
                var ft = searchText.toLowerCase();
                
                $scope.currReqUrl = $rootScope.basePath + "/oxrest/" + $scope.listVariation + "/" + mlist + "/" + page + "/" + pageSize + sorting;
                var responsePromise = OxRest.getPagedData($scope.listVariation, mlist, page, pageSize, sorting);
                // display waiting animation while processing....
                $scope.listTracker.addPromise(responsePromise);
                // whenever we are finished, process data...
                responsePromise.then(function(result) {
                    var aaData = '[';
                    var count = 0;
                    var numTotal = result.data.numObjects;
                    var numCurr = result.data.numCurr;
                    if ($rootScope.debug) {
                        console.log("searchText: " + searchText + " numTotal: " + numTotal + " Page: " + page + " Pagesize: " + pageSize + " Order by: " + sb.join(" ") + " numCurr: " + numCurr);
                    }
                    $.each(result.data.result, function(idx, hit) {
                        aaData = aaData + JSON.stringify(hit);
                        ++count;
                        if (count < pageSize && count < numCurr) {
                            aaData = aaData + ',';
                        }
                    });
                    aaData = aaData + ']'
                    var largeLoad = $.evalJSON(aaData);

                    data = largeLoad.filter(function(item) {
                        return JSON.stringify(item).toLowerCase().indexOf(ft) !== -1;
                    });
                    $scope.setPagingData(data, page, pageSize, numTotal);
                }, function(error) {
                    $scope.addAlert('error',  error.data + " (Fehler " + error.status + ")");
                });
                
            } else {
                
                $scope.currReqUrl = $rootScope.basePath + "/oxrest/" + $scope.listVariation + "/" + mlist + "/" + page + "/" + pageSize + sorting;
                var responsePromise = OxRest.getPagedData($scope.listVariation, mlist, page, pageSize, sorting);
                // display waiting animation while processing....
                $scope.listTracker.addPromise(responsePromise);
                // whenever we are finished, process data...
                responsePromise.then(function(result) {
                    var aaData = '[';
                    var count = 0;
                    var numTotal = result.data.numObjects;
                    var numCurr = result.data.numCurr;
                    if ($rootScope.debug) {
                        console.log("numTotal: " + numTotal + " Page: " + page + " Pagesize: " + pageSize + " Order by: " + sb.join(" ") + " numCurr: " + numCurr);
                    }
                    $.each(result.data.result, function(idx, hit) {
                        aaData = aaData + JSON.stringify(hit);
                        ++count;
                        if (count < pageSize && count < numCurr) {
                            aaData = aaData + ',';
                        }
                    });
                    aaData = aaData + ']'
                    var largeLoad = $.evalJSON(aaData);
                    $scope.setPagingData(largeLoad, page, pageSize, numTotal);
                }, function(error) {
                    $scope.addAlert('error',  error.data + " (Fehler " + error.status + ")");
                });
            }
        }, 100);
        
        //Tell our 'list' tracker to also track our $timeout's 100ms promise.
        //The `tracker:` approach we did a few lines ago is just a shortcut for this.
        $scope.listTracker.addPromise(wait);
    };

    // load data
    $scope.getPagedDataAsync($scope.pagingOptions.pageSize, $scope.pagingOptions.currentPage);

    /**
     * Watch for change events of important view data
     */
    $scope.$watch('listtype', function(newVal, oldVal) {
        if ($rootScope.debug) {
            console.log("listtype changed: " + newVal.value);
        }
    }, true);

    $scope.$watch('pagingOptions', function(newVal, oldVal) {
        if (newVal !== oldVal) {
            if ($rootScope.debug) {
                console.log("pagingOptions changed: " + newVal.value);
            }
            $scope.getPagedDataAsync($scope.pagingOptions.pageSize, $scope.pagingOptions.currentPage, $scope.filterOptions.filterText, $scope.sortOptions);
        }
    }, true);
    // there seems to be a bug in the grid, see https://groups.google.com/forum/#!msg/angular/79oOVuXyVws/fhOOI7NrIzQJ
    // and https://github.com/angular-ui/ng-grid/pull/456
    //$scope.$watch('filterOptions', function (newVal, oldVal) {
    $scope.$watch('gridOptions.$gridScope.filterText', function(newVal, oldVal) {
        if (newVal !== oldVal) {
            if ($rootScope.debug) {
                console.log("Filtering: " + $scope.gridOptions.$gridScope.filterText);
            }
            //$scope.getPagedDataAsync($scope.pagingOptions.pageSize, $scope.pagingOptions.currentPage, $scope.filterOptions.filterText, $scope.sortOptions);
            $scope.getPagedDataAsync($scope.pagingOptions.pageSize, $scope.pagingOptions.currentPage, $scope.gridOptions.$gridScope.filterText, $scope.sortOptions);
        }
    }, true);
    $scope.$watch('sortOptions', function(newVal, oldVal) {
        if (newVal !== oldVal) {
            if ($rootScope.debug) {
                console.log("sortOptions directions changed:");
                console.log($scope.sortOptions);
            }
            $scope.getPagedDataAsync($scope.pagingOptions.pageSize, $scope.pagingOptions.currentPage, $scope.filterOptions.filterText, $scope.sortOptions);
        }
    }, true);

    /**
     * Row selection function, forwards to inspector view
     * @param {type} entity
     * @returns {undefined}
     */
    $scope.editRow = function(entity) {
        // remove "list" from oxarticlelist etc.
        var currObjectType = $scope.listtype.value.replace("list", "");
        $location.path('/inspector/' + currObjectType + '/' + entity.oxid)
    }

    /**
     * Saving data back to server after editing rows
     * @returns {undefined}
     */
    $scope.updateData = function() {
        if($rootScope.readOnly) {
            alert("Disabled in demo mode");
            return;
        }
        var mlist = $scope.listtype.value;
        $scope.currReqUrl = $rootScope.basePath + "/oxrest/" + $scope.listVariation + "/" + mlist;
        var responsePromise = OxRest.savePagedData($scope.listVariation, mlist, $scope.myData);
        // display waiting animation while processing....
        $scope.saveTracker.addPromise(responsePromise);
        // whenever we are finished, process data...
        responsePromise.then(function(result) {
            $scope.addAlert('success', localize.getLocalizedString('_MessageDataSaved_'));
        }, function(error) {
            $scope.addAlert('error',  localize.getLocalizedString('_MessageSaveError_') + " " + error.data + " (" + error.status + ")");
        });
    };

    /**
     * Column defs per object type
     */
    var editTemplate = ' <button id="editBtn{{$index}}" type="button" class="btn btn-primary btn-small" ng-click="editRow(row.entity)" >Details</button> ';
    $scope.myDefs = {
        'oxarticlelist':
                [
                    {field: 'oxid', displayName: 'OXID', width: "120", resizable: true},
                    {field: 'oxtitle', displayName: localize.getLocalizedString('_HeadingTitle_'), width: "210", enableCellEdit: true},
                    {field: 'oxartnum', displayName: 'Art.Nr.', width: "120", enableCellEdit: true},
                    {field: 'oxactive', displayName: 'Aktiv', width: "50", resizable: true, enableCellEdit: true},
                    {field: 'oxshopid', displayName: 'Shop-Id', width: "70", resizable: true, enableCellEdit: true},
                    {field: 'oxtimestamp', displayName: 'Zeitstempel', width: "150", resizable: true, enableCellEdit: true},
                    {field: 'oxvarselect', displayName: 'Variante', width: "120", enableCellEdit: true},
                    {field: 'oxparentid', displayName: 'Parent-Id', width: "120", resizable: true, enableCellEdit: true},
                    {field: 'oxtprice', displayName: 'UVP', width: "70", resizable: true, enableCellEdit: true},
                    {field: 'oxprice', displayName: 'Preis', cellClass: 'priceCell', headerClass: 'priceHeader', width: "80", enableCellEdit: true},
                    {displayName: 'Aktionen', cellTemplate: editTemplate, enableCellEdit: false}]
        ,
        'oxcategorylist':
                [
                    {field: 'oxid', displayName: 'OXID', width: "120", resizable: true, enableCellEdit: true},
                    {field: 'oxtitle', displayName: 'Titel', width: "120", enableCellEdit: true},
                    {field: 'oxactive', displayName: 'Aktiv', width: "50", resizable: true, enableCellEdit: true},
                    {field: 'oxshopid', displayName: 'Shop-Id', width: "70", resizable: true, enableCellEdit: true},
                    {field: 'oxtimestamp', displayName: 'Zeitstempel', width: "150", resizable: true, enableCellEdit: true},
                    {field: 'oxparentid', displayName: 'Parent-Id', width: "120", resizable: true, enableCellEdit: true},
                    {displayName: 'Aktionen', cellTemplate: editTemplate, enableCellEdit: false}]
        ,
        'oxuserlist':
                [
                    {field: 'oxid', displayName: 'OXID', width: "120", resizable: true, enableCellEdit: true},
                    {field: 'oxusername', displayName: 'Login', width: "130", resizable: true, enableCellEdit: true},
                    {field: 'oxfname', displayName: 'Vorname', width: "150", resizable: true, enableCellEdit: true},
                    {field: 'oxlname', displayName: 'Nachname', width: "150", resizable: true, enableCellEdit: true},
                    {field: 'oxtimestamp', displayName: 'Zeitstempel', width: "150", resizable: true, enableCellEdit: true},
                    {field: 'oxactive', displayName: 'Aktiv', width: "50", resizable: true, enableCellEdit: true},
                    {displayName: 'Aktionen', cellTemplate: editTemplate, enableCellEdit: false}]
        ,
        'oxcountrylist':
                [
                    {field: 'oxid', displayName: 'OXID', width: "120", resizable: true, enableCellEdit: true},
                    {field: 'oxtitle', displayName: 'Titel', width: "190", resizable: true, enableCellEdit: true},
                    {field: 'oxtimestamp', displayName: 'Zeitstempel', width: "150", resizable: true, enableCellEdit: true},
                    {field: 'oxactive', displayName: 'Aktiv', width: "50", resizable: true, enableCellEdit: true},
                    {displayName: 'Aktionen', cellTemplate: editTemplate, enableCellEdit: false}],
        'oxcontents':
                [
                    {field: 'oxid', displayName: 'OXID', width: "120", resizable: true, enableCellEdit: true},
                    {field: 'oxtitle', displayName: 'Titel', width: "190", resizable: true, enableCellEdit: true},
                    {field: 'oxloadid', displayName: 'Load-ID', width: "190", resizable: true, enableCellEdit: true},
                    {field: 'oxactive', displayName: 'Aktiv', width: "50", resizable: true, enableCellEdit: true},
                    {field: 'oxfolder', displayName: 'Ordner', width: "190", resizable: true, enableCellEdit: true},
                    {field: 'oxtimestamp', displayName: 'Zeitstempel', width: "150", resizable: true, enableCellEdit: true},
                    {field: 'oxcontent', displayName: 'Inhalt', width: "290", resizable: true, enableCellEdit: true},
                    {displayName: 'Aktionen', cellTemplate: editTemplate, enableCellEdit: false}],
        'oxorder':
                [
                    {field: 'oxid', displayName: 'OXID', width: "120", resizable: true, enableCellEdit: true},
                    {field: 'oxordernr', displayName: 'Bestellnr', width: "90", resizable: true, enableCellEdit: true},
                    {field: 'oxorderdate', displayName: 'Datum', width: "150", resizable: true, enableCellEdit: true},
                    {field: 'oxbillemail', displayName: 'Email', width: "170", resizable: true, enableCellEdit: true},
                    {field: 'oxbillfname', displayName: 'Vorname', width: "120", resizable: true, enableCellEdit: true},
                    {field: 'oxbilllname', displayName: 'Nachname', width: "120", resizable: true, enableCellEdit: true},
                    {field: 'oxtotalnetsum', displayName: 'Ges. Netto', width: "120", resizable: true, enableCellEdit: true},
                    {field: 'oxtotalbrutsum', displayName: 'Ges. Brutto', width: "120", resizable: true, enableCellEdit: true},
                    {field: 'oxtimestamp', displayName: 'Zeitstempel', width: "150", resizable: true, enableCellEdit: true},
                    {displayName: 'Aktionen', cellTemplate: editTemplate, enableCellEdit: false}]
    };
    
    // set columns per chosen type
    $scope.columnDefs = function(){return $scope.myDefs[$scope.listtype.value];};

    // define our ng-grid
    $scope.gridOptions = {
        data: 'myData',
        i18n: $scope.getGridLang(),
        enablePaging: true,
        enablePinning: true,
        enableCellSelection: true,
        enableCellEditOnFocus: true,
        keepLastSelected: true,
        showFooter: true,
        showFilter: true,
        showGroupPanel: true,
        totalServerItems: 'totalServerItems',
        pagingOptions: $scope.pagingOptions,
        filterOptions: $scope.filterOptions,
        columnDefs: 'columnDefs()',
        useExternalSorting: true,
        sortInfo: $scope.sortOptions,
        showColumnMenu: true,
        enableColumnResize: true,
        afterSelectionChange: function(theRow, evt) {
            var moxid = theRow.entity.oxid;
            //console.log("Selection changed for " + moxid);
            //$location.path('/inspector/' + $scope.listtype.value + '/' + moxid);
            //$scope.updateData();

        }
    };
}]);
