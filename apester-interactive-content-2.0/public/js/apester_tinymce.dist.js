(function() {
  angular.module('apesterWpApp', [
    'apeTemplates',
    'ui.select',
    'ape.background',
  ])
    // config interaction box bg image
    .config(['abg_configProvider', function (abg_configProvider) {
      abg_configProvider.setImgixMediaFilterUrl('https://images.apester.com/');
    }])
    .filter('filterInteractionTitle', function() {
      return function (items, searchTerm) {
        var filtered = [];
        var SearchRegex = new RegExp(searchTerm, 'i');
        if (items) {
          for (var i = 0; i < items.length; i++) {
            var item = items[i];
            // search interaction titles without their wrapping html tags
            if (SearchRegex.test(item.title.replace(/<(?:.|\n)*?>/gm, ''))) {
              filtered.push(item);
            }
          }
        }
        return filtered;
      };
    })
    .filter('apePlainText', function() {
      return function(html) {
        html = html || '';
        return (html || '').replace(/(<([^>]+)>)/ig,' ').replace(/\s\s+/g, ' ').trim();
      };
    })
    .filter('apeEmphasis', function() {
      return function(val, maxLength) {
        if (val && val.length > maxLength) {
          val = val.substr(0, maxLength) + '...';
        }
        return val;
      };
    });
})();
(function() {
    angular.module('apesterWpApp')
      .constant('configuration', {
        displayBaseUrl: 'https://display.apester.com/',
        tokenBaseUrl: 'https://users.apester.com/publisher/token/',
        interactionBaseUrl: 'https://interaction.apester.com',
        interactionsByTokenUrl: '/interaction/all/token?',
        eventsBaseUrl: 'https://events.apester.com/event',
      });
})();

(function() {
  angular.module('apesterWpApp')
    .directive('dashboard', ['InteractionStatsService', 'InteractionService', 'TokensService', 'EventsService', '$window', function(InteractionStatsService, InteractionService, TokensService, EventsService, $window){
      return {
        restrict: 'E',
        templateUrl: 'dashboard.html',
        link: function(scope, element, attrs) {
          scope.pluginPath = $window.apester_plugin_path;
          scope.interactionFilter = {};
          scope.interactionSearchFilter = '';
          scope.interactionSortMethod = 'created';
          scope.isSortByDisabled = true; // set sort by ctr/date to disable until we get the data from server
          scope.interactionsCount = 0;

          scope.interactionsState = 'published';

          // expose sendEvent method to dashboard scope so all child scopes can use it, while only injecting EventsService once here
          scope.sendEvent = EventsService.sendEvent;

          var addStatsToInteractions = function(statisticsArray) {
            for (var i = 0; i < scope.interactions.length; i++) {
              var interaction = scope.interactions[i];
              interaction.ctr = statisticsArray[interaction.interactionId].ctr;
              interaction.views = statisticsArray[interaction.interactionId].views;
            }
          };

          function fetchInteractions(tokens) {
            InteractionService.getInteractions(tokens)
              .then(function(interactions) {
                scope.interactions = interactions;
                scope.interactionsCount = InteractionStatsService.beautifyNumber(interactions.length);
                fetchStats(scope.interactions);
                mapEngineIntoInteractions(scope.interactions);
              },
              function(error) {
                scope.isError = true;
                console.error('Error getting interaction: ', error);
              })
          }

          function fetchStats(interactions) {
            InteractionStatsService.fetchStatistics(interactions, {pretty: true}).then(function(stats) {
              scope.statistics = stats;
              addStatsToInteractions(stats);
              scope.isSortByDisabled = false;
            });
          }

          var mapIconClassByEngine = function(engine) {
            var map = {
              'multi-poll': 'icon-poll',
              'multi-trivia': 'icon-quiz',
              'personality-quiz': 'icon-personality',
              'video-trivia': 'icon-video',
              'video-poll': 'icon-video',
              'video-personality': 'icon-video',
              'contest-poll': 'icon-countdown',
              'journey': 'icon-rolling-story'
            };

            return map[engine];
          };

          /**
           * Adds 'engine' property & respectful 'iconClass' property to each interaction based on it's layoutId property
           * @param interactions [Array] - the interactions that are missing the engine property (e.g. interactions from server)
           */
          var mapEngineIntoInteractions = function(interactions) {
            InteractionService.getEngines().then(function(engines){
              // go over interactions
              for ( var i = 0; i < interactions.length; i++) {
                interactionLayoutId = interactions[i].layout;
                // go over engines
                for ( var j = 0; j < engines.length; j++) {
                  if (interactionLayoutId === engines[j].layoutId) {
                    var engineDirective = engines[j].directive;
                    interactions[i].engineFullName = engineDirective;
                    if (engineDirective.indexOf('-two') !== -1) {
                      interactions[i].engine = engineDirective.substring(0, engineDirective.length - 4);
                    }
                    else {
                      interactions[i].engine = engineDirective;
                    }
                    // add the icon class property to each interaction
                    interactions[i].iconClass = mapIconClassByEngine(interactions[i].engine);
                  }
                }
              }
            });
          };


          function init() {
            // get the apester channels tokens from Wordpress db
            scope.apesterTokens = TokensService.getPureTokens() || [];

            if (scope.apesterTokens.length === 0) {
              scope.isNoToken = true;

            }
            else {
              // init the tokens service - get channel data based on token
              TokensService.init(scope.apesterTokens);

              fetchInteractions(scope.apesterTokens);
            }
          }

          init();

        }
      }
    }]);
})();
(function($) {
  angular.module('apesterWpApp')
    .service('EventsService', ['TokensService', '$http', '$window', 'configuration', function(TokensService, $http, $window, configuration){
      
      var eventBaseProps = {
        event: '',
        properties: {
          pluginProvider: 'WordPress',
          pluginVersion: $window.apester_plugin_version,
          channelToken: TokensService.getPureTokens()[0] || '',
          phpVersion: window.php_version,
          wpVersion: window.wp_version
        },
        metadata: {
          referrer: encodeURIComponent(document.referrer),
          current_url: encodeURIComponent($window.location.href),
          screen_height: $window.screen.height.toString(),
          screen_width: $window.screen.width.toString(),
          language: $window.navigator.userLanguage || $window.navigator.language
        }
      };

      var sendEvent = function(eventDataObject) {
        // if no event name provided - stop execution
        if (!eventDataObject.event) {
          console.error('Event not sent - missing event name');
          return;
        }

        var eventData = angular.merge({}, eventBaseProps, eventDataObject);
        
        // make sure we remove all empty properties
        for (var prop in eventData.properties) {
          if (eventData.properties.hasOwnProperty(prop)) {
            if (eventData.properties[prop].trim() === '') {
              delete eventData.properties[prop];
            }
          }
        }

        $http({
          url: configuration.eventsBaseUrl,
          method: 'POST',
          contentType: 'application/json; charset=UTF-8',
          data: JSON.stringify(eventData),
          async: false
        });
      };

      return {
        sendEvent: sendEvent
      }
    }]);

})(jQuery);
(function() {
  angular.module('apesterWpApp')
    .service('InteractionService', ['$q', '$http', 'configuration', function($q, $http, configuration){
      
      var limit = '&limit=50';
      var dateFilterSuffix = '&fromDate=';

      var convertTokensToUrlParams = function(tokens) {
        var url = '';
        for (var i = 0; i < tokens.length; i++) {
          url += 'tokenId=' + tokens[i];
          if (i !== tokens.length - 1) {
            url +='&';
          }
        }
        return url;
      };

      var engines = [];

      /**
       * Return the payload from successful response
       * @param {object} response
       * @returns {*}
       */
      function returnPayload(response) {
        return response.data.payload;
      }

      var getEngines = function() {
        var result;
        /**
         * gets base engine layout
         */
        var getBaseEngine = function() {
          return $http({
            url: configuration.interactionBaseUrl + '/engine',
            method: 'GET',
            withCredentials: true
          }).then(returnPayload);
        };

        /**
         * gets all engines and caches them into the 'engines' propery
         */
        var getAllEngines = function() {
          return getBaseEngine().then(function(engines){
            var baseEngineId = engines[0].engineId;
            return $http({
              url: configuration.interactionBaseUrl + '/engine/' + baseEngineId + '/layout',
              method: 'GET'
            }).then(function(response) {
              // cache the result before we return
              engines = response.data.payload;
              return engines;
            });
          })
        };

        if (engines.length === 0) {
          result = getAllEngines();
        }
        else {
          result = $q.defer().resolve(engines);
        }
        return result;
      };

      var getInteractions = function(tokens) {
        // get interactions from 30 days back
        var nowDate = new Date();
        nowDate.setMonth(nowDate.getMonth() - 1);
        var dateString = encodeURIComponent(nowDate.toDateString());

        return $http({
          url: configuration.interactionBaseUrl + configuration.interactionsByTokenUrl + convertTokensToUrlParams(tokens) + limit + dateFilterSuffix + dateString,
          method: 'GET',
          withCredentials: true
        }).then(returnPayload);
      };

      return {
        getEngines: getEngines,
        getInteractions: getInteractions
      }
    }]);

})();
(function($) {
  angular.module('apesterWpApp')
    .service('RandomService', ['$q', '$http', 'TokensService', 'configuration', function($q, $http, TokensService, configuration){
      /**
       * Return the payload from successful response
       * @param {object} response
       * @returns {*}
       */
      function returnPayload(response) {
        return response.data.payload;
      }

      var getSingleChannelPlaylist = function(tokenFullObject) {
        return $http({
          url: configuration.displayBaseUrl + 'tokens/' + tokenFullObject.token + '/interactions/random',
          method: 'GET',
          cache: true
        }).then(function(response) {
          tokenFullObject.random = returnPayload(response);
          return tokenFullObject;
        }, function() {
          return;
        });
      };
      
      var getTokensPlaylistData = function(tokensFullDataList) {
        var promisesArr = [];
        for (var i=0 ;i < tokensFullDataList.length; i++) {
          promisesArr.push(getSingleChannelPlaylist(tokensFullDataList[i]));
        }

        return $q.all(promisesArr).then(function(response){
          // filter only tokens that returned with random interactions in playlist
          return response.filter(function(item){return item;})
        },
        function(e) {
          console.error(e);
        });
      };

      return {
        getTokensPlaylistData: getTokensPlaylistData
      }
    }]);

})(jQuery);
(function($) {
  angular.module('apesterWpApp')
    .service('TokensService', ['$q', '$http', '$window', 'configuration', 'Utils', function($q, $http, $window, configuration, Utils){
      
      // make sure the service data can only be initialized once
      var isInitialized = false;

      var apesterTokens = [];
      var tokensFullData = [];
      
      // get the apester channels tokens from Wordpress db (after we put them into global window variable 'apester_tokens' in 'tinymce.php' file
      var pureTokens = Object.keys($window.apester_tokens) || [];

      // make sure we convert a value that is not an array (e.g. just one token string from older version of the plugin)
      // into an array with that one value it contains
      pureTokens = $.isArray(pureTokens) ? pureTokens : [pureTokens];
      
      /**
       * Return the payload from successful response
       * @param {object} response
       * @returns {*}
       */
      function returnPayload(response) {
        return response.data.payload;
      }

      var getSingleTokenData = function(token) {
        return $http({
          url: configuration.tokenBaseUrl + token,
          method: 'GET',
          cache: true,
          withCredentials: true
        }).then(function(response) {
          var data = returnPayload(response);
          return !!data && {
            token: data.authToken,
            name: data.name
          };
        });
      };
      
      var getTokensFullData = function() {
        var promisesArr = [];
        for (var i=0 ;i < apesterTokens.length; i++) {
          promisesArr.push(getSingleTokenData(apesterTokens[i]));
        }

        return $q.all(promisesArr).then(function(responses){
            // filter only tokens that returned valid result
            return responses.filter(function(item){return item;})
          },
          function(e) {
            console.error(e);
          });
      };
  
      /**
       * returns a list of the channel tokens as they are being saved on the server side
       * @returns {*|Array}
       */
      var getPureTokens = function() {
        return Utils.getValidTokens(pureTokens);
      };

      var init = function(tokens) {
        apesterTokens = Utils.getValidTokens(tokens);
        if (!isInitialized) {
          getTokensFullData();
          isInitialized = true;
        }
      };

      return {
        init: init,
        getTokensFullData: getTokensFullData,
        getPureTokens: getPureTokens
      }
    }]);

})(jQuery);
(function() {
  angular.module('apesterWpApp')
    .service('Utils', ['$filter', function($filter) {

      /**
       * validate token on the client
       * @param token
       * @returns {boolean} isValid
       */
      var isValidToken = function(token) {
        return /^[0-9a-fA-F]{24}$/.test(token);
      };

      /**
       * filters valid tokens from a tokens array
       * @param tokens - array of {string} tokens
       * @returns {array} - valid tokens array
       */
      var getValidTokens = function (tokens) {
        return $filter('filter')(tokens, function(token){ return isValidToken(token); });
      };

      return {
        isValidToken: isValidToken,
        getValidTokens: getValidTokens
      }
    }]);

})();
(function() {
  angular.module('apesterWpApp')
    .directive('interactionBox', ['$filter', '$sce', '$timeout', function($filter, $sce, $timeout){
      return {
        restrict: 'E',
        replace: true,
        templateUrl: 'interactionBox.html',
        link: function(scope, element, attrs) {
          var JOURNEY_LAYOUT_ID = '574d5ff5bdeb9c513bdd738e';

          var isJourney = scope.interaction.layout === JOURNEY_LAYOUT_ID;
          scope.boxTitle = $sce.trustAsHtml($filter('apeEmphasis')($filter('apePlainText')(scope.interaction.title, ' '), 50));

          scope.playerPreviewUrl = 'https://renderer.apester.com/interaction/' + scope.interaction.interactionId + '?iframe_preview=true';

          scope.duplicatInteractionUrl = 'https://app.apester.com/editor/new?duplicate=' + scope.interaction.interactionId + '&isJourney=' + isJourney;

          // watch the modal open state - when opened, make sure to update the background image so the ape.background
          // library will update according to the view, otherwise it won't know the dimensions since modal is closed
          scope.$watch('isOpen', function(oldVal, newVal) {
            if (scope.isOpen) {
              $timeout(function () {
                scope.background = scope.interaction.data.backgroundImage || scope.interaction.image;
              }, 0);
            }
          });
        }
      }
    }]);

})();

(function() {
    angular.module('apesterWpApp')
      .directive('interactionStats', ['$filter', function($filter){
          return {
              restrict: 'E',
              replace: true,
              templateUrl: 'interactionStats.html',
              link: function(scope, element, attrs) {
              }
          }
      }]);
    
})();
(function() {
  angular.module('apesterWpApp')
    .service('InteractionStatsService', ['$q', '$http', '$window', function($q, $http, $window){
      /**
       * BI base URL
       * @type {string}
       */
      var baseUrl = ($window.location.protocol) + '//gcp-analytics.apester.com/api/interaction/';
      var factors = [
          'views',
          'ctr',
          'circulation',
          'social',
          'votingStage'
        ];
      var interactionsStatistics = {};

      var buildConfigObject = function(interactions){

        function extractMinDate(interactions){
          var  unixDateArray = interactions.map(function(interaction){
            return new Date(interaction.created).getTime();
          });
          var min;
          try {
            min = unixDateArray.reduce(function(minimal, current){
              if(minimal > current){
                return current ;
              }
              return minimal;
            });
          }
          catch ( e ) {
            min = new Date('2016-06-12').getTime();
          }

          return min;
        }
        var ret = {};

        ret.metrics = ['clicked_social', 'clicked_other_interaction','interaction_loaded', 'interaction_started', 'interaction_vote_stage'];
        ret.interactionIds = interactions.map(function(interaction){ return interaction.interactionId;});
        ret.to = (new Date()).getTime();
        ret.from = extractMinDate(interactions);

        return ret;
      };

      var getStatisticsData = function(configObject) {
        var query = '?' + 'from=' + configObject.from + '&to=' + configObject.to + '&ids=' + configObject.interactionIds;

        return $http.get(baseUrl + configObject.metrics.join(',') + query).then(function(res){
          return res;
        });
      };

      /**
       * Creates an interaction cell within the stats object.
       * @param interactionId
       */
      function initiateInteractionStat(interactionId) {
        if (!interactionsStatistics[interactionId]) {
          interactionsStatistics[interactionId] = {};
        }
      }

      /**
       * Gather data from many months and put it into one number.
       * @param data
       * @param property
       */
      function normalizeStats(data, property) {
        Object.keys(data).forEach(function(key) {
          var interaction = data[key];

          var total = 0;
          Object.keys(interaction).forEach(function(month) {
            total += interaction[month];
          });

          initiateInteractionStat(key);
          interactionsStatistics[key][property] = total;
        });
      }

      /**
       * Adds zeros where fields are empty.
       */
      function padWithZeros(interactions, interactionsStatistics) {
        interactions.forEach(function(interaction) {
          var id = interaction.interactionId;
          initiateInteractionStat(id);

          factors.forEach(function(factor) {
            if (!interactionsStatistics[id][factor]) {
              interactionsStatistics[id][factor] = 0;
            }
          });

        });
      }

      /**
       * Beautifies a single number.
       * @param value
       * @returns {*}
       */
      function beautifyNumber(value) {

        var powers = [
          {key: 'q', value: Math.pow(10,15)},
          {key: 't', value: Math.pow(10,12)},
          {key: 'b', value: Math.pow(10,9)},
          {key: 'm', value: Math.pow(10,6)},
          {key: 'k', value: Math.pow(10,3)}
        ];

        // Return the value if it's below our lowest power.
        if (value < powers[powers.length - 1].value) {
          return value;
        }

        // Find the maximal power that our value is bigger than.
        var num;
        for(var i = 0; i < powers.length; i++) {
          if(value >= powers[i].value) {
            num = value / powers[i].value;
            break;
          }
        }

        // Avoid a decimal point when it's 0.
        if (num % 1 < 0.1) {
          return num.toFixed() + powers[i].key;
        }

        // Show only one decimal point.
        return num.toFixed(1) + powers[i].key;
      }

      /**
       * Optionally beautify results.
       */
      function beautify(interactions, interactionsStatistics) {

        /**
         * Takes two numbers and makes a percentage form them.
         * @param num
         * @param total
         */
        function toPercent(num, total) {
          if (num === 0 && total === 0) {
            return 0;
          }

          var percent = num / total * 100;

          if (percent > 100) {
            percent = 100;
          }

          return Math.ceil(percent);
        }


        // For each cell in the stats, beautify it.
        interactions.forEach(function(interaction) {
          var id = interaction.interactionId;
          var data = interactionsStatistics[id];

          // patch to calc countdown CTR
          if (data.votingStage) {
            data.ctr = toPercent(data.ctr, data.votingStage);
          }
          else {
            data.ctr = toPercent(data.ctr, data.views);
          }

          // For each key of the cell.
          factors.forEach(function(factor) {

            if (factor === 'ctr') {
              return;
            }

            data[factor + 'Readable'] = beautifyNumber(data[factor]);
          });
        });
      }

      var changeDataFormatFromApesterToMixpanel = function(data, existObject , eventName) {
        data = data.data.payload.data;
        Object.keys(data).forEach( function(interactionId) {
          existObject[interactionId] = {'2015-12-01': data[interactionId][eventName]};
        });
      };

      return {
        fetchStatistics: function(interactions, options) {
          var deferred = $q.defer(),
            promises = [];

          // Filter interactions that we already have data on.
          interactions = interactions.filter(function(interaction) {
            return !interactionsStatistics[interaction.interactionId];
          });

          // If we already have all interactions cached, just return the stats.
          if (interactions.length === 0) {
            deferred.resolve(interactionsStatistics);
            return deferred.promise;
          }

          var apesterDataConfig = buildConfigObject(interactions);


          getStatisticsData(apesterDataConfig).then(function(results){
            var newResults = [{},{},{},{}, {}];

            changeDataFormatFromApesterToMixpanel(results, newResults[0],'interaction_loaded');
            changeDataFormatFromApesterToMixpanel(results, newResults[1],'interaction_started');
            changeDataFormatFromApesterToMixpanel(results, newResults[2],'clicked_other_interaction');
            changeDataFormatFromApesterToMixpanel(results, newResults[3],'clicked_social');
            changeDataFormatFromApesterToMixpanel(results, newResults[4],'interaction_vote_stage');
            results = newResults;

            // Compute the raw data from many months to one total number.
            factors.forEach(function(factor, i) {
              normalizeStats(results[i], factor);
            });
            // Add zeros where we didn't get data.
            padWithZeros(interactions, interactionsStatistics);

            beautify(interactions, interactionsStatistics);

            deferred.resolve(interactionsStatistics);
          }, function(err) {
            deferred.reject(err);
          });

          return deferred.promise;
        },
        beautifyNumber: beautifyNumber
      }
    }]);

})();
(function() {
    angular.module('apesterWpApp')
      .directive('apeActionItems', [function(){
          return {
              restrict: 'E',
              replace: true,
              templateUrl: 'action-items.html',
              link: function(scope, element, attrs) {
                scope.embedInteraction = function(interaction) {
                  scope.insertNormalShortCode(interaction.interactionId);
                  scope.sendEvent({
                    event: 'wordpress_embed_interaction_clicked',
                    properties: {
                      interactionId: interaction.interactionId,
                      channelId: interaction.publisherId,
                      channelToken: ''// send event, but with empty channelToken so that prop gets removed from the final event (it's irrelevant)
                    }
                  });
                };
              }
          }
      }]);
    
})();
(function() {
  angular.module('apesterWpApp')
    .directive('apeHeader', [function(){
      return {
        restrict: 'E',
        replace: true,
        templateUrl: 'header.html',
        link: function( scope, element, attrs ) {
        }
      }
    }]);

})();
(function() {
    angular.module('apesterWpApp')
      .directive('searchBox', [function(){
          return {
              restrict: 'E',
              replace: true,
              templateUrl: 'search.html',
              link: function(scope, element, attrs) {
                  var elem = element.find('input');

                  scope.filterByTitle = function(stringToFind) {
                      scope.interactionFilter.title
                  };
    
                  scope.focusOrsearchInteractions = function($event) {
                      $event.preventDefault();
                      if (!scope.focus) {
                          elem[0].focus();
                      } else {
                          scope.searchInteractions();
                      }
                  }
              }
          }
      }]);
    
})();
(function() {
  angular.module('apesterWpApp')
    .directive('apeNavbar', ['TokensService', 'RandomService', function(TokensService, RandomService){
      return {
        restrict: 'E',
        replace: true,
        templateUrl: 'ape-navbar.html',
        link: function(scope, element, attrs) {
          scope.tokensWithPlaylist = [];

          // get channel names for each token
          TokensService.getTokensFullData().then(function(response){
            // filter only tokens that have items in playlist
            RandomService.getTokensPlaylistData(response).then(function(randomResponse) {
              scope.tokensWithPlaylist = randomResponse;
            });
          });
        }
      }
    }]);
  
})();
(function() {
    angular.module('apesterWpApp')
      .directive('channelSelector', ['TokensService', 'RandomService', '$http', function(TokensService, RandomService, $http){
          return {
              restrict: 'E',
              replace: true,
              templateUrl: 'channel-selector.html',
              link: function(scope, element, attrs) {
                scope.placeholder = 'Embed manual playlist';

                scope.embedRandomMedia = function() {
                  scope.insertRandomShortCode(scope.tokensWithPlaylist[0].token);
                };

                scope.embedSelectedRandomMedia = function(selectedToken, $select) {
                  // after resetting the $select.selected the change event will call this function again,
                  // so we check if we received 'selectedToken'
                  if (selectedToken) {
                    scope.insertRandomShortCode(selectedToken);
                    // reset selected option so we keep persenting the placeholder after any change
                    delete $select.selected;
                  }
                };
              }
          }
      }]);
    
})();
(function() {
  angular.module('apesterWpApp')
    .directive('apeDataFilter', [function(){
      return {
        restrict: 'E',
        replace: true,
        templateUrl: 'dataFilters.html',
        link: function(scope, element, attrs) {
          var filters = {};
          var SORT_BY = "Sort By ";

          /**
           * @desc
           * calls when scroll reach the bottom of publisher list
           */
          scope.fetchPublishers = function() {
          };

          scope.interactionFilters = [
            {
              'name': 'All Engines',
              'layout': '',
              'classes': 'all-engines'
            },
            {
              'name': 'Poll',
              'classes': 'engine-filter ic icon-poll',
              'layout': 'multi-poll'
            },
            {
              'name': 'Quiz',
              'classes': 'engine-filter ic icon-quiz',
              'layout': 'multi-trivia'
            },
            {
              'name': 'Personality',
              'classes': 'engine-filter ic icon-personality',
              'layout': 'personality-quiz'
            },
            {
              'name': 'Video',
              'classes': 'engine-filter ic icon-video',
              'layout': 'video-poll'
            },
            {
              'name': 'Countdown',
              'classes': 'engine-filter ic icon-countdown',
              'layout': 'contest-poll'
            },
            {
              'name':'Journey',
              'classes':'engine-filter ic icon-rolling-story',
              'layout': 'journey'
            }

          ];

          scope.changeEngineFilter = function(engine) {
            scope.interactionFilter.engine = engine;
          };
          
          scope.changeSortFilter = function(sortMethod) {
            scope.interactionSortMethod = sortMethod;
          };

          function init() {
            scope.fetchPublishers();
            scope.interactionsState = 'Published';
            scope.interactionsStates = ['Published', 'Saved', 'Archived'];

            scope.sortOptions = [
              {
                label: SORT_BY + 'CTR',
                sortMethod: 'ctr'
              }, 
              {
                label: SORT_BY + 'Date',
                sortMethod: 'created'
              }, 
              {
                label: SORT_BY + 'Views',
                sortMethod: 'views'
              }];
            scope.sortOption = scope.sortOptions[1];
            scope.interactionSortMethod = scope.sortOption.sortMethod;
            scope.publishers = [];
            scope.interactionsLayout = scope.interactionFilters[0];
          }

          init();
        }
      }
    }]);

})();
(function() {
  angular.module('apesterWpApp')
    .directive('apePlaylistStatus', [function(){
      return {
        restrict: 'E',
        replace: true,
        templateUrl: 'playlist-status.html',
        link: function(scope, element, attrs) {
          scope.isIncludePlaylist = true;

          var isTinyMceEditorExists = function () {
            return !!tinymce && !!tinymce.activeEditor;
          };

          var getArticleContent = function () {
            return tinymce.activeEditor.getContent();
          };

          scope.$watch("isOpen", function (newIsOpen) {
            var articleContent;

            if (!newIsOpen) { return; }

            if (isTinyMceEditorExists()) {
              articleContent = getArticleContent();
              scope.isIncludePlaylist = articleContent.indexOf('[apester-exclude-playlist]') === -1;
            }
          });

          scope.togglePlaylistStatus = function () {
            scope.isIncludePlaylist = !scope.isIncludePlaylist;
            
            if (!scope.isIncludePlaylist) {
              insertExcludePlaylistShortCode();
            } else {
              removeExpludePlaylistShorcodeFromEditor();
            }
          };

          var insertExcludePlaylistShortCode = function () {
            if (isTinyMceEditorExists()) {
              tinymce.activeEditor.insertContent('[apester-exclude-playlist]');
            }
          };

          var removeExpludePlaylistShorcodeFromEditor = function () {
            var articleContent;

            if (isTinyMceEditorExists()) {
              articleContent = getArticleContent();
              tinymce.activeEditor.setContent(articleContent.replace(/\[apester-exclude-playlist\]/g, ''));
            }
          };
        }
      }
    }]);

})();
(function(jQuery) {

  var ngApp;

  angular.module('apesterWpApp')
    .directive('apesterModal', ['EventsService', function(EventsService) {
      return {
        restrict: 'E',
        templateUrl: 'apester-modal.html',
        link: function(scope, element, attrs) {

          var wpEditor;
          scope.isOpen = false;

          scope.insertNormalShortCode = function(interactionId) {
            if (tinymce && tinymce.activeEditor) {
              tinymce.activeEditor.insertContent('[interaction id="' + interactionId + '"]');
            }
            scope.isOpen = false;
          };
          
          scope.insertResultsShortCode = function(interactionId) {
            if (tinymce && tinymce.activeEditor) {
              tinymce.activeEditor.insertContent('[interaction id="' + interactionId + '" results="true"]');
            }
            scope.isOpen = false;
          };
          
          scope.insertRandomShortCode = function(channelToken) {
            if (tinymce && tinymce.activeEditor) {
              tinymce.activeEditor.insertContent('[apester-playlist channelToken="' + channelToken + '"]');
              EventsService.sendEvent({ event: 'wordpress_use_playlist', properties: { channelToken: channelToken } });
            }
            scope.isOpen = false;
          };

          scope.closeModal = function() {
            scope.isOpen = false;
          };

          tinymce.PluginManager.add('apester_btn', function (editor, url) {

            var ngApp;
            wpEditor = editor;

            var pluginPath = url.substr(0, url.lastIndexOf('/'));

            // Add Button to TinyMCE Visual Editor Toolbar
            editor.addButton('apester_btn', {
              title: 'Apester',
              cmd: 'apesterLogic',
              icon: 'apester-btn', // apply an icon class (only applies if no image is passed)
              image: pluginPath + '/img/ape-icon.svg'
            });

            editor.addCommand('apesterLogic', function() {
              scope.isOpen = true;
              EventsService.sendEvent({ event: 'wordpress_apester_button_clicked' });
              scope.$apply(); // must be present since the current callback is being called outside of angular's context
            });

          });
        }
      }
    }]);

  // insert angular app element for manual bootstrap
  jQuery('body').append('<div id="apesterWpApp"><apester-modal></apester-modal></div>');

  angular.element(function() {
    return (function(){
      ngApp = angular.bootstrap(document.getElementById('apesterWpApp'), ['apesterWpApp']);
    })()
  });
})(jQuery);