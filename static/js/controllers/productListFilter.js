AppControllers.productListFilter = ['$scope', '$routeParams', '$modal', 'productFilterStorage', function ($scope, $routeParams, $modal, storage) {


	$scope.fields = [
		'categories'
	];


	$scope.createDefaultFilter = function () {
		$routeParams.cid && storage.initList('categories', $routeParams.cid);
	};
	$scope.createDefaultFilter();


	$scope.emitChanged = function () {
		this.$emit('productListFilterChanged', this.fields);
	};
	$scope.emitChanged();


	$scope.showSelectCategoryForm = function () {

		$scope.modalEditForm = $modal({
			title: 'Select a category',
			contentTemplate: 'template/category.select',
			template: 'template/category.modal_right',
			scope: $scope,
			show: true
		});

	};

	$scope.$on('modalCategorySelectToggle', function (event, args) {
		var
			nodeId = args[0],
			checked = args[1];
		storage.replace('categories', nodeId, checked);
		$scope.emitChanged();
	});

	$scope.$on('modalCategorySelectLoaded', function (event, list) {
		storage.setChecked2List('categories', list);
	});

}];