AppControllers.modalCategoryNew = ['$scope', 'treeServer', 'treeNode', function ($scope, http, node) {

	$scope.category = {};
	angular.extend($scope, AppExtends.form({
		id: 'modalCategoryNew',
		model: 'category'
	}));

	$scope.save = function () {
		$scope.button().loading();
		$scope.category.parent_id = node.id();

		http.createCategory($scope.category, function (res) {
			if (!$scope.showErrors(res)) {
				node.newSubItem(res.category, res.extras);
				$scope.cleanup();
			}
			$scope.button().reset();
		});

	};

}];
