(function () {
	'use strict';

	var App = angular.module('treeApp', ['ui.tree', 'mgcrea.ngStrap', 'cgBusy', 'ngTagsInput', 'LocalStorageModule']);

	App.service('treeServer', AppServices.treeServer);
	App.service('treeInit', AppServices.treeInit);
	App.service('treeDragDrop', AppServices.treeDragDrop);
	App.service('treeNode', AppServices.treeNode);

	App.controller('modalCategoryNew', AppControllers.modalcategoryNew);
	App.controller('modalCategoryEdit', AppControllers.modalCategoryEdit);
	App.controller('categoryTreeEdit', AppControllers.categoryTreeEdit);

})();
