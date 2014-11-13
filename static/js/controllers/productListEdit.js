AppControllers.productListEdit = ['$scope', '$route', '$location', 'productServer', 'productFilterStorage', function ($scope, $route, $location, http, filter) {

	$scope.data = [];
	$scope.load = function (params) {
		params = params || [];
		var values = filter.post(params);
		$scope.changeUrl(values);
		var promise = http.loadList(values);
		promise.success(function (result) {
			$scope.data = result.data;
		});
		return promise;
	};

	$scope.changeUrl = function (values) {
		var url = $location.path() + '?';
		for (var key in values) {
			if (!values.hasOwnProperty(key)) {
				continue;
			}
			if (!values[key] || (values[key] && values[key].length == 0)) {
				continue;
			}
			url = url + key + '=' + values[key] + '&';
		}
		$location.url(url, false);
	};

	$scope.productListFilterChanged = function (fields) {
		return $scope.load(fields);
	};

}];