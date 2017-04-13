
(function (window) {

    var PORT = document.location.protocol.indexOf('https') > -1 ? "443" : "5044",
        DEV_URL = "//mng-dev-logstash.playbuzz.com:" + PORT,
        PROD_URL = "//mng-prd-logstash.playbuzz.com:" + PORT,
        SERVICE_NAME = "playbuzz",
        ENVIRONMENT = "dev",
        LEVEL = "error",
        URL = document.location.href,
        MESSAGE = "error";


    var OPTIONS = {
        'serviceName': SERVICE_NAME,
        'level' : LEVEL,
        'environment': ENVIRONMENT,
        'message': MESSAGE,
        'url': URL
    };


    /**
     * Polyfill for Object.assign
     * @param obj1
     * @param obj2
     * @docs - https://developer.mozilla.org/en/docs/Web/JavaScript/Reference/Global_Objects/Object/assign
     */
    function objectAssignPolyfill() {

        Object.assign = function(target, varArgs) { // .length of function is 2
            'use strict';
            if (target == null) { // TypeError if undefined or null
                throw new TypeError('Cannot convert undefined or null to object');
            }

            var to = Object(target);

            for (var index = 1; index < arguments.length; index++) {
                var nextSource = arguments[index];

                if (nextSource != null) { // Skip over if undefined or null
                    for (var nextKey in nextSource) {
                        // Avoid bugs when hasOwnProperty is shadowed
                        if (Object.prototype.hasOwnProperty.call(nextSource, nextKey)) {
                            to[nextKey] = nextSource[nextKey];
                        }
                    }
                }
            }
            return to;
        };

    }

    /**
     * deep merging 2 objects
     * @param obj1
     * @param obj2
     */
    function enrichObject(obj1, obj2) {

        if (typeof Object.assign != 'function') {

            objectAssignPolyfill();
        }

        return  Object.assign({}, obj1, obj2);

    }


    function request(url, options, callback) {

        var xmlHttp = new XMLHttpRequest();

        // true for asynchronous
        xmlHttp.open("POST", url, true);

        //Send the proper header information along with the request
        //xmlHttp.setRequestHeader("Content-type", "application/json");

        xmlHttp.onreadystatechange = function() {

            if (xmlHttp.readyState == 4){

                callback && callback(xmlHttp.responseText, xmlHttp.status);

            }

        };


        xmlHttp.send(JSON.stringify(options));

    }


    function PbLogger(options) {

        this.options = enrichObject(OPTIONS ,options || OPTIONS);
        
    }



    PbLogger.prototype.log = function (message, level) {

        var isProd = this.options.environment === "production";
        var url = isProd ? PROD_URL : DEV_URL;
        var _this = this;

        // enrich options object.
        this.options = enrichObject(this.options, {
            message: message,
            level: level
        });


        //send log to console if not production
        if(!isProd && window.console[level]){
            window.console[_this.options.level](_this.options.message);
        }

        //send log via request
        request(url, this.options, function (responseText, status) {
            if(status !== 200 && !isProd){
                console.error(responseText);
            }
        });

    };

    PbLogger.prototype.info = function (message) {

        this.log(message, 'info');
    };

    PbLogger.prototype.error = function (message) {

        this.log(message, 'error');
    };

    PbLogger.prototype.warn = function (message) {

        this.log(message, 'warn');
    };

    PbLogger.prototype.debug = function (message) {

        this.log(message, 'debug');
    };


    window.pbLogger  = PbLogger;


})(window);