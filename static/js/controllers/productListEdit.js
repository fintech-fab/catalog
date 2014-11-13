AppControllers.productListEdit = ['$scope', '$route', '$location', 'productServer', 'productFilterStorage', function ($scope, $route, $location, http, filter) {

	$scope.data = [];
	$scope.load = function (params, callback) {
		params = params || [];

		var values = filter.post(params);
		$scope.changeUrl(values);
		http.loadList(values, function (result) {
			$scope.data = result.data;
			callback();
		});
	};

	$scope.changeUrl = function (values) {
		var url = $location.path() + '?';
		for (var key in values) {
			if (values[key].length == 0) {
				continue;
			}
			url = url + key + '=' + values[key] + '&';
		}
		$location.url(url, false);
	};

	$scope.$on('productListFilterChanged', function (event, args) {
		$scope.load(args[0], args[1]);
	});

}];