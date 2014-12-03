(function () {
	'use strict';

	var App = angular.module('treeApp', ['ui.tree', 'mgcrea.ngStrap', 'cgBusy', 'ngTagsInput', 'LocalStorageModule', 'ngRoute']);

	App.config(function ($routeProvider) {
		$routeProvider.
			when('/', {
				templateUrl: 'template/category.index',
				controller: 'categoryTreeEdit'
			}).
			when('/products', {
				templateUrl: 'template/product.index',
				controller: 'productListEdit',
				reloadOnSearch: false
			}).
			otherwise({
				redirectTo: '/'
			});
	});

	App.service('productServer', AppServices.productServer);
	App.service('treeServer', AppServices.treeServer);
	App.service('treeInit', AppServices.treeInit);
	App.service('treeDragDrop', AppServices.treeDragDrop);
	App.service('treeNode', AppServices.treeNode);
	App.service('productFilterStorage', AppServices.productFilterStorage);

	App.controller('modalCategoryNew', AppControllers.modalCategoryNew);
	App.controller('modalCategoryEdit', AppControllers.modalCategoryEdit);
	App.controller('categoryTreeEdit', AppControllers.categoryTreeEdit);
	App.controller('productListEdit', AppControllers.productListEdit);

	var ctrl = App.controller('productListFilter', AppControllers.productListFilter);
	ctrl.directive('selectListCallback', AppDirectives.selectListCallback);
	ctrl.directive('productFilterListSorting', AppDirectives.productFilterListSorting);

})();
