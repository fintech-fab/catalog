AppControllers.productListEdit = ['$scope', 'productServer', 'productFilterStorage', function ($scope, http, filter) {

	$scope.data = [];
	$scope.load = function (params) {
		params = params || [];
		http.loadList(filter.post(params), function (result) {
			$scope.data = result.data;
		});
	};

	$scope.$on('productListFilterChanged', function (event, params) {
		$scope.load(params);
	});

}];