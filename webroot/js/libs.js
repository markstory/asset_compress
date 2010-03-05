/*
License:
	MIT-style license.
*/
if (window.App == undefined) {
	window.App = {};
}
if (window.basePath == undefined) {
	window.basePath = '/';
}

// set the url used to load additional js class files.
App.classUrl = window.basePath + 'asset_compress/js_files/join/'

/*
Fire Application events. Used to trigger logic for blocks of your application's Javascript
When combined with the automatic javascript includer on the server you can call

	App.Dispatcher.dispatch('users/index');

This will fire:

 - App.users.beforeAction (if it exists)
 - App.users.index (if it exists)

Will return the value from the called action or return false if the method was not found.
*/
App.Dispatcher = function () {
	var PATH_SEPARATOR = '/';

	return {
		dispatch: function (url) {
			var params = this._parseUrl(url);
			if (App[params.controller] === undefined || App[params.controller][params.action] === undefined) {
				return false;
			}
			if (typeof App[params.controller].beforeAction == 'function') {
				App[params.controller].beforeAction(params);
			}
			return App[params.controller][params.action](params);
		},
		
		_parseUrl: function (url) {
			var params = {};
			var urlParts = url.split(PATH_SEPARATOR);
			if (urlParts.length == 1) {
				urlParts[1] = 'index';
			}
			params.controller = urlParts[0];
			params.action = urlParts[1];
			return params;
		}
	}
}();

/*
Load class/resource files from the compressor.
Will check window[name] for classes to help prevent duplicates from being loaded.

Loads the files asynchrnonously by appending script tabs to <head>  If the last
argument is a function it will be called once the file has completed loading.

Example

	App.load('Template', 'OtherClass', function () { alert('files loaded'); });

Will load Template, and OtherClass through the asset_compressor and fire the function when complete.
*/
App.load = function () {

	function _appendScript(filename, callback) {
		var head = document.getElementsByTagName("head")[0];
		var script = document.createElement("script");
		script.src = filename;
		var done = false;

		script.onload = script.onreadystatechange = function () {
			if (!done && (!this.readyState || this.readyState == "loaded" || this.readyState == "complete") ) {
				done = true;
				callback();
			}
		}
		head.appendChild(script);
	}

	var args = Array.prototype.slice.call(arguments);

	var readyCallback = function () {};
	if (typeof args[args.length -1] == 'function') {
		readyCallback = args.pop();
	}
	for (var i = args.length; i; i--) {
		var className = args[i];
		if (window.className !== undefined) {
			delete args[i];
		}
	}
	var filename = App.classUrl + args.join('/');
	_appendScript(filename, readyCallback);
};

/*
Used to safely declare a controller namespace, so js files for actions can safely create their
controller object.

Example:

	App.makeController('users');
	App.users.edit = {
		...
	};

*/
App.makeController = function (name) {
	if (this[name] === undef) {
		this[name] = {};
		return this[name];
	}
	return this[name];
};
