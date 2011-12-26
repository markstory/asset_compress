/*
License:
	MIT-style license.
*/
if (window.AssetCompress == undefined) {
	window.AssetCompress = {};
}
if (window.basePath == undefined) {
	window.basePath = '/';
}

// Set the url used to load additional js class files.
if (AssetCompress.url == undefined) {
	AssetCompress.classUrl = window.basePath + 'asset_compress/js_files/get/'
}

/*
Load class/resource files from the compressor.
Will check window[name] for classes to help prevent duplicates from being loaded.

Loads the files asynchrnonously by appending script tabs to <head>  If the last
argument is a function it will be called once the file has completed loading.

Example

	App.load('Template', 'OtherClass', function () { alert('files loaded'); });

Will load Template, and OtherClass through the asset_compressor and fire the function when complete.
*/
AssetCompress.load = function () {

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

	var args = Array.prototype.slice.call(arguments),
		readyCallback = function () {},
		buildName = [],
		i, className,
		filename;

	if (typeof args[args.length -1] == 'function') {
		readyCallback = args.pop();
	}

	for (i = args.length; i--;) {
		className = args[i];
		buildName.push(className);
		if (window[className] !== undefined) {
			delete args[i];
		}
	}
	filename = AssetCompress.classUrl +
		AssetCompress.underscore(buildName.reverse().join('')) + '.js' +
		'?file[]=' + args.join('&file[]=');

	_appendScript(filename, readyCallback);
};
AssetCompress.underscore = function (camelCased) {
	return camelCased.replace(/([A-Z])(?=[a-z0-9])/g, '_$1', '_\1').toLowerCase().substring(1);
}

