App.controller('modalCategoryEdit', ['$scope', '$http', 'treeNode', function ($scope, $http, treeNode) {

	$http.get('rest/categories/item/' + treeNode.model.id)
		.then(function (res) {
			$scope.doUpdate(res.data);
		});

	$scope.save = function (category, btn) {
		ffCatApp.buttonLock(btn);
		category = (typeof category == 'undefined') ? {} : category;
		category._token = $('#category-token').val();
		$http.post(
			'category/update/' + $scope.category.id,
			category
		)
			.success(function (res) {
				if (!ffCatApp.showErrors(res, 'modalCategoryEdit', 'category')) {
					$scope.doUpdate(res);
				}
				ffCatApp.buttonUnlock(btn);
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
		treeNode.scope.setTreeAttributes(data);

	};

}]);