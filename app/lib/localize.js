'use strict';

/*
 * An AngularJS Localization Service
 *
 * Written by Jim Lavin
 * http://codingsmackdown.tv
 *
 * edited by Stefan Moises <stefan@rent-a-hero.de>
 */

angular.module('localization', []).
    factory('localize', ['$http', '$rootScope', '$window', '$filter', '$resource', function ($http, $rootScope, $window, $filter, $resource) {
    var localize = {
        // use the $window service to get the language of the user's browser
        language:$window.navigator.userLanguage || $window.navigator.language,
        // array to hold the localized resource string entries
        dictionary:[],
        // flag to indicate if the service hs loaded the resource file
        resourceFileLoaded:false,

        successCallback:function (data) {
            // store the returned array in the dictionary
            localize.dictionary = data;
            // set the flag that the resource are loaded
            localize.resourceFileLoaded = true;
            // broadcast that the file has been loaded
            $rootScope.$broadcast('localizeResourcesUpdates');
        },

        setLanguage: function(value) {
            localize.language = value;
            localize.initLocalizedResources();
        },
        getLanguage: function() {
            return localize.language;
        },

        initLocalizedResources:function () {
            
            // build the url to retrieve the localized resource file
            // be: TODO - fix for UNIT tests - gives "unexpected request..."!
            // be: try resource to bypass path problems with subdomains...
            var id = 'resources-locale_' + localize.language + '.js';
            var ml = $resource('i18n/:langId', {langId:'@id'});
            ml.query({langId:id}, function(data, headers){
                localize.successCallback(data);
            }, function(err){
                // first attempt failed, try default file!
                id = 'resources-locale_default.js'
                ml.query({langId:id}, function(data, headers){
                    localize.successCallback(data);
                });
            });           
        },

        getLocalizedString: function(value) {
            // default the result to an empty string
            var result = '';

            // make sure the dictionary has valid data
            if ((localize.dictionary !== []) && (localize.dictionary.length > 0)) {
                // use the filter service to only return those entries which match the value
                // and only take the first result
                var entry = $filter('filter')(localize.dictionary, function(element) {
                        return element.key === value;
                    }
                )[0];

                // set the result
                if(entry) {
                    result = entry.value;                	
                }
                else {
                    console.error("Missing localization string for: " + value + " in language: " + localize.language);
                }
            }
            // return the value to the call
            return result;
        }
    };

    // force the load of the resource file
    localize.initLocalizedResources();

    // return the local instance when called
    return localize;
} ]).
    filter('i18n', ['localize', function (localize) {
    return function (input) {
        return localize.getLocalizedString(input);
    };
}]).directive('i18n', ['localize', function(localize){
    var i18nDirective = {
        restrict:"EAC",
        updateText:function(elm, token){
            var values = token.split('|');
            if (values.length >= 1) {
                // construct the tag to insert into the element
                var tag = localize.getLocalizedString(values[0]);
                // update the element only if data was returned
                if ((tag !== null) && (tag !== undefined) && (tag !== '')) {
                    if (values.length > 1) {
                        for (var index = 1; index < values.length; index++) {
                            var target = '{' + (index - 1) + '}';
                            tag = tag.replace(target, values[index]);
                        }
                    }
                    // insert the text into the element
                    elm.html(tag);
                };
            }
        },

        link:function (scope, elm, attrs) {
            scope.$on('localizeResourcesUpdates', function() {
                i18nDirective.updateText(elm, attrs.i18n);
            });

            attrs.$observe('i18n', function (value) {
                i18nDirective.updateText(elm, attrs.i18n);
            });
        }
    };

    return i18nDirective;
}]);