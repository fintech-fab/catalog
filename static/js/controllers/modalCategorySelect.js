AppControllers.modalCategorySelect = ['$scope', 'productServer', function ($scope, http) {

	$scope.categories = [];
	http.loadCategories(function (data) {
		$scope.categories = data;
		$scope.$emit('modalCategorySelectLoaded', $scope.categories);
	});

	$scope.toggleItem = function (id) {
		var node;
		for (var i = 0, qnt = this.categories.length; i < qnt; i++) {
			if (this.categories[i].id == id) {
				node = this.categories[i];
			}
		}
		node.checked = !node.checked;
		$scope.$emit('modalCategorySelectToggle', [node.id, node.checked]);
	};

}];