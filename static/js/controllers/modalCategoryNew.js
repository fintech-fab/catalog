AppControllers.modalcategoryNew = ['$scope', 'treeServer', 'treeNode', function ($scope, http, treeNode) {

	$scope.category = {};

	$scope.save = function (category, btn) {
		ffCatApp.buttonLock(btn);
		category = (typeof category == 'undefined') ? {} : category;
		category._token = $('#category-token').val();
		category.parent_id = treeNode.model.id;

		http.createCategory(category, function (res) {
			if (!ffCatApp.showErrors(res, 'modalCategoryNew', 'category')) {
				treeNode.getScope().newSubItem(res.category, res.extras);
				$scope.category = {};
			}
			ffCatApp.buttonUnlock(btn);
		});


	};

}];
