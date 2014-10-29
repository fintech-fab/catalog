AppControllers.modalCategoryEdit = ['$scope', 'treeServer', 'treeNode', function ($scope, http, node) {

	$scope.category = {};

	angular.extend($scope, AppExtends.form({
		id: 'modalCategoryEdit',
		model: 'category'
	}));

	$scope.button().loading();
	http.getById(node.id(), function (res) {
		$scope.doUpdate(res.data);
		$scope.button().reset();
	});

	$scope.save = function () {
		$scope.button().loading();
		$scope.token();

		http.updateCategory(node.id(), $scope.category, function (res) {
			if (!$scope.showErrors(res)) {
				$scope.doUpdate(res);
			}
			$scope.button().reset();
		});

	};

	$scope.doUpdate = function (res) {

		$scope.types = res.types;
		$scope.category = res.category;
		$scope.extras = res.extras;
		$scope.category.category_tags = $scope.extras.tags;

		var data = jQuery.extend({}, res.category);
		data.symlink = res.extras.symlink;
		data.category_type_id = res.extras.category_type_id;
		data.type = res.extras.type;
		node.apply(data);

	};

}];
