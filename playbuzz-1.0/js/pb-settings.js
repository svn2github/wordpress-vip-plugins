


//PbSettings will collect settings passing via url, as  query parameters and store
//them to local storage / session storage under STORAGE_NAME key.
// NOTE -
// 1. sessionStorage support windows 8 and above.
// 2. Wordpress require the user to allow cookies and local storage / session storage in order use the platform.



function PbSettings(storage, jQuery) {

    this.storage = storage;
    this.$ = jQuery;
    this.STORAGE_NAME = 'PLAYBUZZ';

    //collect settings from url
    this.settingsFromUrl = this.collectUrlSettings();

    //collect settings from storage
    this.settingsFromStorage = this.getFromStorage();

    //merge between settings sources.
    this.settings = this.collectSettings(this.settingsFromUrl, this.settingsFromStorage);

    //save settings
    this.putToStorage(this.settings);


    return this.getFromStorage();
}
/**
 * merge between settings sources.
 * @param settingsFromUrl
 * @param settingsFromStorage
 */
PbSettings.prototype.collectSettings = function (settingsFromUrl, settingsFromStorage) {

    return this.$.extend(settingsFromStorage, settingsFromUrl);

};

/**
 * Collect settings from url query parameters.
 * @returns {*}
 */
PbSettings.prototype.collectUrlSettings = function () {

    return this.$.queryParameters();

};


/**
 * collect settings from storage.
 */
PbSettings.prototype.getFromStorage = function () {

   var settings = JSON.parse(this.storage.getItem(this.STORAGE_NAME));

   return settings;

};

/**
 * store settings to storage.
 * @param settings
 */

PbSettings.prototype.putToStorage = function (settings) {

    var toStore = JSON.stringify(settings);

    this.storage.setItem(this.STORAGE_NAME, toStore);


};

/**
 * return settings or specific key in settings
 * @param key
 * @returns {*}
 */
PbSettings.prototype.get =  function (key) {
    return key ?  this.settings[key] : this.settings;
};


window.pbSettings = new PbSettings(window.sessionStorage, window.jQuery);