AppControllers.productListEdit = ['$scope', '$route', '$location', 'productServer', 'productFilterStorage', function ($scope, $route, $location, http, filter) {

	$scope.data = [];
	$scope.parseBack = null;
	$scope.lastUrl = null;

	$scope.load = function (params) {

		params = params || [];
		var values = filter.post(params);
		var changed = $scope.changeUrl(values);

		if (changed) {
			var promise = http.loadList(values);
			promise.success(function (result) {
				$scope.data = result.data;
			});
			return promise;
		}

	};

	$scope.changeUrl = function (values) {
		var url = $location.path(),
			delimiter = '?';
		for (var key in values) {
			if (!values.hasOwnProperty(key)) {
				continue;
			}
			if (!values[key] || (values[key] && values[key].length == 0)) {
				continue;
			}
			if (values[key].length > 300) {
				throw "too long parameter value";
			}
			url = url + delimiter + key + '=' + values[key];
			delimiter = '&';
		}

		if (url != $scope.lastUrl) {
			$location.url(url);
			$scope.lastUrl = url;
			return true;
		}
		return false;
	};

	$scope.productListFilterChanged = function (fields, parseBack) {
		this.parseBack = parseBack || this.parseBack;
		return $scope.load(fields);
	};

	$scope.$on('$locationChangeSuccess', function () {

		console.log('$locationChangeSuccess');
		console.log('parseBack: ' + typeof $scope.parseBack);

		if (typeof $scope.parseBack === 'function') {
			console.log('parseBack!');
			$scope.parseBack($scope.parseUrl());
		}
	});

	$scope.parseUrl = function () {

		var hash = $location.url(),
			parameters = hash.split('?')[1],
			items = ( parameters && parameters.split('&') ) || [],
			params = {};

		for (var i = 0, qnt = items.length, part; i < qnt; i++) {
			part = items[i] && items[i].split('=');
			if (part) {
				params[part[0]] = part[1];
			}
		}

		return params;

	};

}];