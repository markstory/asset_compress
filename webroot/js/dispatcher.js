
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

