AppControllers.productListFilter = ['$scope', '$routeParams', '$location', '$modal', 'productFilterStorage', 'productServer', function ($scope, $routeParams, $location, $modal, storage, http) {


	$scope.fields = [
		'categories',
		'tags'
	];

	storage.set('tags', $routeParams.tags && $routeParams.tags.split(',') || null);
	storage.set('categories', $routeParams.categories && $routeParams.categories.split(',') || null);


	$scope.emitChanged = function () {
		return $scope.$parent.productListFilterChanged(this.fields);
	};
	$scope.emitChanged();


	$scope.showSelectCategoryForm = function () {
		$scope.showFilterForm('categories', 'Select a category');
	};

	$scope.showSelectTagsForm = function () {
		$scope.showFilterForm('tags', 'Check a tags');
	};

	$scope.showFilterForm = function(entity, title){
		$scope.selectListEntity = entity;
		$scope.modalEditForm = $modal({
			title: title,
			template: 'template/product.select-list-callback',
			scope: $scope,
			show: true
		});
	};

	$scope.getCollection = function(name, callback){
		switch (name){
			case 'categories':
				http.loadCategories(callback);
				break;
			case 'tags':
				http.loadTags(callback);
				break;
			default:
				callback(null);
				break;
		}
	};

	$scope.itemsSelectLoaded = function(name, list){
		storage.setChecked2List(name, list);
	};

	$scope.itemsSelectToggle = function (name, id, checked) {
		storage.replace(name, id, checked);
		return $scope.emitChanged();
	};



}];